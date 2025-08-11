<?php

namespace App\Services;

use App\Models\AiProject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AiProvider
{
    protected string $provider;
    protected string $model;

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

    public function generate(AiProject $project, string $type, string $locale = 'en', ?string $text = null): array
    {
        $text = $text ?? ($project->source_text ?? '');
        if (!$text && $project->source_disk && $project->source_path) {
            $text = (string) Storage::disk($project->source_disk)->get($project->source_path);
        }

        $text = mb_convert_encoding($text ?? '', 'UTF-8', 'UTF-8');

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
You are a presentation designer. Return a **single valid JSON object** only (no extra text, no markdown).

REQUIRED THEME (override defaults):
{$themeJson}

REQUIREMENTS:
- Create {$slidesMin}-{$slidesMax} slides.
- Each slide MUST include:
  - "title": short string
  - "bullets": 3–6 concise bullets (no numbering)
  - "background": { "type":"solid|gradient|image", "color":"#HEX", "gradient":{ "from":"#HEX", "to":"#HEX" }, "image_url": null|url }
  - "colors": { "title":"#HEX", "bullets":"#HEX", "accent":"#HEX" }
- Use the default background if slide background is not specified.
- Language: {$locale}.
- Output **JSON only**.

SCHEMA:
{
  "theme": {
    "palette": { "background":"#0B1220","primary":"#60A5FA","secondary":"#A78BFA","accent":"#34D399" },
    "font": { "family": null, "title_size": 44, "body_size": 24, "title_weight": "bold", "body_weight": "normal" },
    "layout": {
      "title": { "x":30,"y":30,"w":900,"h":80,"align":"left" },
      "bullets": { "x":40,"y":130,"w":900,"h":400,"line_spacing":1.25,"indent":10 }
    },
    "background_default": { "type":"gradient","color":"#0B1220","gradient":{"from":"#0EA5E9","to":"#111827"},"image_url": null }
  },
  "slides": [
    {
      "title": "string",
      "bullets": ["string","string","string"],
      "notes": "optional",
      "background": { "type":"solid","color":"#0B1220","gradient":{"from":"#...","to":"#..."},"image_url": null },
      "colors": { "title":"#FFFFFF","bullets":"#D1D5DB","accent":"#34D399" }
    }
  ]
}

SOURCE:
{$text}
EOT;
        } else {
            $prompt = "Using locale {$locale}, generate a {$type} in JSON for the following text:\n\n{$text}";
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

        return [
            'raw' => $data,
            'input_tokens' => $input,
            'output_tokens' => $output,
            'cost_cents' => $cost_cents,
        ];
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
