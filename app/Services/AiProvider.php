<?php

namespace App\Services;

use App\Models\AiProject;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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

    public function generate(AiProject $project, string $type, string $locale = 'en'): array
    {
        $text = $project->source_text ?? '';
        if (!$text && $project->source_disk && $project->source_path) {
            $text = (string) Storage::disk($project->source_disk)->get($project->source_path);
        }

        $prompt = "Using locale {$locale}, generate a {$type} in JSON for the following text:\n\n{$text}";

        if ($this->provider === 'anthropic' && !env('ANTHROPIC_API_KEY')) {
            return ['raw' => ['error' => 'missing anthropic api key'], 'input_tokens' => 0, 'output_tokens' => 0, 'cost_cents' => 0];
        }

        if ($this->provider === 'openai' && !env('OPENAI_API_KEY')) {
            return ['raw' => ['error' => 'missing openai api key'], 'input_tokens' => 0, 'output_tokens' => 0, 'cost_cents' => 0];
        }

        if ($this->provider === 'anthropic') {
            $response = Http::withHeaders([
                'x-api-key' => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
            ])->post(self::ANTHROPIC_ENDPOINT, [
                'model' => $this->model,
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);
        } else {
            $response = Http::withToken(env('OPENAI_API_KEY'))
                ->post(self::OPENAI_ENDPOINT, [
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
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
