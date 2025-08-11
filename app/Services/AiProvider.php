<?php

namespace App\Services;

use App\Models\AiProject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AiProvider
{
    protected string $provider;
    protected string $model;
    protected bool $useCache = true;

    private const OPENAI_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const ANTHROPIC_ENDPOINT = 'https://api.anthropic.com/v1/messages';

    private const OPENAI_PRICING = [
        'gpt-4o-mini' => ['input' => 0.15 / 1_000_000, 'output' => 0.60 / 1_000_000],
    ];

    private const ANTHROPIC_PRICING = [
        'claude-3-haiku-20240307' => ['input' => 0.25 / 1_000_000, 'output' => 1.25 / 1_000_000],
    ];

    public function __construct()
    {
        $this->provider = env('AI_PROVIDER', 'openai');
        $this->model = env('AI_MODEL', $this->provider === 'anthropic' ? 'claude-3-haiku-20240307' : 'gpt-4o-mini');
    }

    public function withoutCache(): static
    {
        $this->useCache = false;
        return $this;
    }

    public function chat(AiProject $project, string $locale, array $messages): array
    {
        if ($this->provider === 'anthropic' && !env('ANTHROPIC_API_KEY')) {
            return [
                'error'        => ['status' => null, 'body' => 'missing anthropic api key'],
                'raw'          => ['error' => 'missing anthropic api key'],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        if ($this->provider === 'openai' && !env('OPENAI_API_KEY')) {
            return [
                'error'        => ['status' => null, 'body' => 'missing openai api key'],
                'raw'          => ['error' => 'missing openai api key'],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        try {
            if ($this->provider === 'anthropic') {
                $response = Http::withHeaders([
                    'x-api-key'       => env('ANTHROPIC_API_KEY'),
                    'anthropic-version' => '2023-06-01',
                ])->connectTimeout((int) env('AI_CONNECT_TIMEOUT', 30))
                    ->timeout((int) env('AI_HTTP_TIMEOUT', 300))
                    ->retry((int) env('AI_HTTP_RETRY', 2), (int) env('AI_HTTP_RETRY_MS', 1500))
                    ->post(self::ANTHROPIC_ENDPOINT, [
                        'model'     => $this->model,
                        'max_tokens' => 1024,
                        'messages'  => $messages,
                    ]);
            } else {
                $response = Http::withToken(env('OPENAI_API_KEY'))
                    ->connectTimeout((int) env('AI_CONNECT_TIMEOUT', 30))
                    ->timeout((int) env('AI_HTTP_TIMEOUT', 300))
                    ->retry((int) env('AI_HTTP_RETRY', 2), (int) env('AI_HTTP_RETRY_MS', 1500))
                    ->post(self::OPENAI_ENDPOINT, [
                        'model'    => $this->model,
                        'messages' => $messages,
                    ]);
            }
        } catch (Throwable $e) {
            Log::error('AI provider request exception', ['exception' => $e]);

            return [
                'error'        => ['status' => null, 'body' => $e->getMessage()],
                'raw'          => [],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        if (!$response->successful()) {
            Log::error('AI provider request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'error'        => ['status' => $response->status(), 'body' => $response->json() ?? $response->body()],
                'raw'          => $response->json() ?? [],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        $data = $response->json();
        $usage = $data['usage'] ?? [];

        $input = $usage['prompt_tokens'] ?? ($usage['input_tokens'] ?? 0);
        $output = $usage['completion_tokens'] ?? ($usage['output_tokens'] ?? 0);
        $cost_cents = $this->calculateCost($this->model, $input, $output);

        return [
            'raw' => $data,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cost_cents' => $cost_cents,
        ];
    }

    public function generate(AiProject $project, string $type, string $locale = 'en', ?string $text = null): array
    {
        $text = $text ?? ($project->source_text ?? '');
        if (!$text && $project->source_disk && $project->source_path) {
            $text = (string) Storage::disk($project->source_disk)->get($project->source_path);
        }

        $text = mb_convert_encoding($text ?? '', 'UTF-8', 'UTF-8');
        $sanitizedText = str_replace(['{{', '}}'], ['{', '}'], $text);

        if ($type === 'slides') {
            $template = $project->slideTemplate ? $project->slideTemplate->toArray() : \App\Models\SlideTemplate::defaultTheme();
            $theme = [
                'palette' => $template['palette'] ?? [],
                'font' => $template['font'] ?? [],
                'layout' => $template['layout'] ?? [],
                'background_default' => $template['background_default'] ?? [],
                'rules' => $template['rules'] ?? [],
            ];
            $slidesMin = $theme['rules']['slides_min'] ?? 5;
            $slidesMax = $theme['rules']['slides_max'] ?? 10;
            $requireBullets = $theme['rules']['require_bullets'] ?? true;
            $themeJson = json_encode([
                'theme' => [
                    'palette' => $theme['palette'],
                    'font' => $theme['font'],
                    'layout' => $theme['layout'],
                    'background_default' => $theme['background_default'],
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$prompt = <<<EOT
You are a senior presentation designer. OUTPUT MUST BE ONE VALID JSON OBJECT ONLY (no markdown, no prose).

GOALS:
- Ubah materi menjadi pitch-deck/slide deck yang visual & ringkas, berbahasa {{locale}}.
- Variasikan layout, gunakan warna kontras, dan beri saran gambar relevan (URL publik).
- Jangan ulang-ulang kalimat; tiap slide harus punya nilai baru.

ALLOWED LAYOUTS:
- "cover"               // big title + subtitle
- "title-bullets"       // title + 3–6 bullets
- "image-right"         // title + bullets (kiri), image (kanan)
- "image-left"          // title + bullets (kanan), image (kiri)
- "two-column"          // title + 2 kolom bullet (3–5 item/kolom)
- "quote"               // quote besar + author
- "stat"                // angka besar + deskripsi singkat
- "section-break"       // divider antar bab

BACKGROUND OPTIONS per slide:
{ "type":"solid|gradient|image", "color":"#HEX", "gradient":{ "from":"#HEX","to":"#HEX" }, "image_url": "https://..." }

COLORS per slide (opsional): { "title":"#HEX", "bullets":"#HEX", "accent":"#HEX" }

THEME DEFAULTS (may override per slide):
{{theme_json}}

REQUIREMENTS:
- Buat {{slides_min}}–{{slides_max}} slides. Campurkan minimal 4 jenis layout.
- Bahasa: {{locale}}. Judul singkat & punchy (≤60 karakter).
- Bullets ringkas (maks 12 kata/point), tanpa numbering manual.
- Untuk layout image-left/right, sediakan "image_url" (HTTPS, aman & relevan topik).
- Untuk "stat", isi "stat_value" (mis. "99.95%") + "subtitle" singkat.
- Untuk "quote", isi "quote" & "author".
- Opsional: "notes" (speaker notes) maksimal 3 baris per slide, ringkas actionable.
- JANGAN sertakan markdown, code fence, atau teks di luar JSON.
- Jangan cantumkan data sensitif/pribadi. Hindari URL yang membutuhkan login.

SCHEMA STRICT:
{
  "theme": {
    "palette": { "background":"#HEX","primary":"#HEX","secondary":"#HEX","accent":"#HEX" },
    "font": { "family":"Inter", "title_size":44, "body_size":22, "title_weight":"bold", "body_weight":"normal" },
    "layout": {
      "title":  { "x":60, "y":40,  "w":860, "h":100, "align":"left" },
      "bullets":{ "x":60, "y":160, "w":860, "h":340, "line_spacing":1.25, "indent":16, "size":22 },
      "background_default": { "color":"#0B1220" }
    }
  },
  "slides": [
    {
      "layout": "title-bullets|cover|image-right|image-left|two-column|quote|stat|section-break",
      "title": "string (≤60 chars)",
      "subtitle": "string optional",
      "bullets": ["string","string","string"]  // omit for cover/quote/stat/section-break
      "col1": ["string","string"]               // only for two-column
      "col2": ["string","string"],              // only for two-column
      "stat_value": "string",                   // only for stat
      "quote": "string",                        // only for quote
      "author": "string",                       // only for quote
      "image_url": "https://...",               // only for image-left/right (optional but recommended)
      "background": { "type":"solid|gradient|image", "color":"#HEX", "gradient":{ "from":"#HEX","to":"#HEX" }, "image_url":"https://..." },
      "colors": { "title":"#HEX", "bullets":"#HEX", "accent":"#HEX" },
      "notes": ["max 3 short lines", "why it matters", "what to do next"]
    }
  ]
}

STYLE GUIDELINES:
- Gunakan tone profesional & jelas. Hindari jargon berlebihan.
- Ubah paragraf panjang menjadi bullets tajam & non-berulang.
- Prioritaskan insight/action daripada kutipan literal dari sumber.
- Pakai variasi warna (palette) agar tiap slide terasa hidup, tapi konsisten.

SOURCE MATERIAL (summarize & transform, do not copy verbatim unless short terms):
{{source_text}}

LANGUAGE: {$locale}
Return JSON only.
SOURCE:
{$sanitizedText}
EOT;

            $prompt = strtr($prompt, [
                '{{locale}}' => $locale,
                '{{slides_min}}' => (string) $slidesMin,
                '{{slides_max}}' => (string) $slidesMax,
                '{{theme_json}}' => $themeJson,
                '{{source_text}}' => $sanitizedText,
            ]);

        } else {
            $prompt = "Using locale {$locale}, generate a {$type} in JSON for the following text:\n\n{$sanitizedText}";
        }

        $cacheKey = 'ai:' . hash('sha256', $prompt);
        if ($this->useCache && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $this->useCache = true;
            return $cached;
        }

        if (preg_match('/{{[^}]+}}/', $prompt)) {
            throw new \RuntimeException('Unresolved prompt placeholders');
        }

        if ($this->provider === 'anthropic' && !env('ANTHROPIC_API_KEY')) {
            return [
                'error'        => ['status' => null, 'body' => 'missing anthropic api key'],
                'raw'          => ['error' => 'missing anthropic api key'],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        if ($this->provider === 'openai' && !env('OPENAI_API_KEY')) {
            return [
                'error'        => ['status' => null, 'body' => 'missing openai api key'],
                'raw'          => ['error' => 'missing openai api key'],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        try {
            if ($this->provider === 'anthropic') {
                $response = Http::withHeaders([
                    'x-api-key'       => env('ANTHROPIC_API_KEY'),
                    'anthropic-version' => '2023-06-01',
                ])->connectTimeout((int) env('AI_CONNECT_TIMEOUT', 30))
                    ->timeout((int) env('AI_HTTP_TIMEOUT', 300))
                    ->retry((int) env('AI_HTTP_RETRY', 2), (int) env('AI_HTTP_RETRY_MS', 1500))
                    ->post(self::ANTHROPIC_ENDPOINT, [
                        'model'     => $this->model,
                        'max_tokens' => 1024,
                        'messages'  => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);
            } else {
                $response = Http::withToken(env('OPENAI_API_KEY'))
                    ->connectTimeout((int) env('AI_CONNECT_TIMEOUT', 30))
                    ->timeout((int) env('AI_HTTP_TIMEOUT', 300))
                    ->retry((int) env('AI_HTTP_RETRY', 2), (int) env('AI_HTTP_RETRY_MS', 1500))
                    ->post(self::OPENAI_ENDPOINT, [
                        'model'          => $this->model,
                        'response_format' => ['type' => 'json_object'],
                        'messages'       => [
                            ['role' => 'user', 'content' => $prompt],
                        ],
                    ]);
            }
        } catch (Throwable $e) {
            Log::error('AI provider request exception', ['exception' => $e]);

            return [
                'error'        => ['status' => null, 'body' => $e->getMessage()],
                'raw'          => [],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        if (!$response->successful()) {
            Log::error('AI provider request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'error'        => ['status' => $response->status(), 'body' => $response->json() ?? $response->body()],
                'raw'          => $response->json() ?? [],
                'input_tokens' => 0,
                'output_tokens' => 0,
                'cost_cents'   => 0,
            ];
        }

        $data = $response->json();
        $usage = $data['usage'] ?? [];

        $input = $usage['prompt_tokens'] ?? ($usage['input_tokens'] ?? 0);
        $output = $usage['completion_tokens'] ?? ($usage['output_tokens'] ?? 0);
        $cost_cents = $this->calculateCost($this->model, $input, $output);

        $result = [
            'raw' => $data,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cost_cents' => $cost_cents,
        ];

        if ($this->useCache) {
            Cache::put($cacheKey, $result, now()->addMinutes((int) env('AI_CACHE_TTL', 60)));
        }

        $this->useCache = true;

        return $result;
    }

    public static function extractContent(array $result): ?string
    {
        $data = $result['raw'] ?? $result;

        if (isset($data['content'])) {
            if (is_string($data['content'])) {
                return trim($data['content']);
            }
            if (is_array($data['content']) && isset($data['content'][0]['text']) && is_string($data['content'][0]['text'])) {
                return trim($data['content'][0]['text']);
            }
        }

        if (isset($data['text']) && is_string($data['text'])) {
            return trim($data['text']);
        }

        if (isset($data['choices'][0]['message']['content']) && is_string($data['choices'][0]['message']['content'])) {
            $content = trim($data['choices'][0]['message']['content']);
            if (!self::isValidJson($content) && preg_match('/\{.*\}/s', $content, $m) && self::isValidJson($m[0])) {
                return trim($m[0]);
            }
            return $content;
        }

        if (isset($data['choices'][0]['text']) && is_string($data['choices'][0]['text'])) {
            $content = trim($data['choices'][0]['text']);
            if (!self::isValidJson($content) && preg_match('/\{.*\}/s', $content, $m) && self::isValidJson($m[0])) {
                return trim($m[0]);
            }
            return $content;
        }

        return null;
    }

    private static function isValidJson(string $json): bool
    {
        try {
            json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function calculateCost(string $model, int $input, int $output): int
    {
        $pricing = $this->provider === 'anthropic'
            ? (self::ANTHROPIC_PRICING[$model] ?? null)
            : (self::OPENAI_PRICING[$model] ?? null);

        if (!$pricing) {
            return 0;
        }

        $dollars = $input * $pricing['input'] + $output * $pricing['output'];

        return (int) round($dollars * 100);
    }
}
