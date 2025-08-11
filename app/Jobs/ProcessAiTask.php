<?php

namespace App\Jobs;

use App\Models\AiTask;
use App\Models\AiTaskVersion;
use App\Models\UsageLog;
use App\Models\UsageCounter;
use App\Services\AiProvider;
use App\Services\DocumentParser;
use App\Support\TextChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Exceptions\DocumentParseException;
use Throwable;

class ProcessAiTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public AiTask $task, public string $locale, public bool $useCache = true)
    {
    }

    public function handle(AiProvider $provider, DocumentParser $parser): void
    {
        $this->task->update([
            'status'  => 'running',
            'message' => 'Processing...',
        ]);

        $project = $this->task->project;

        // Ambil sumber teks dari field atau file (cek exists supaya gak throw)
        $text = $project->source_text ?? '';
        if (!$text && $project->source_disk && $project->source_path) {
            $disk = $project->source_disk;
            $path = $project->source_path;
            if (Storage::disk($disk)->exists($path)) {
                try {
                    $text = $parser->parse($disk, $path);
                    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                } catch (DocumentParseException $e) {
                    Log::error('Document parse error', ['path' => $path, 'message' => $e->getMessage()]);
                    $this->task->update([
                        'status'  => 'failed',
                        'message' => 'Document parse error',
                    ]);
                    return;
                }
            }
        }

        $chunks = TextChunker::chunk($text);
        if (empty($chunks)) {
            $chunks = [''];
        }

        $inputTokens = 0;
        $outputTokens = 0;
        $costCents = 0;

        if ($this->task->type === 'slides') {
            $slides = [];
            $theme = $project->slideTemplate ? $project->slideTemplate->toArray() : \App\Models\SlideTemplate::defaultTheme();
            $requireBullets = data_get($theme, 'rules.require_bullets', true);
            $payloadChunks = [];

            foreach ($chunks as $index => $chunk) {
                $result = ($this->useCache ? $provider : $provider->withoutCache())
                    ->generate($project, $this->task->type, $this->locale, $chunk);

                if (!empty($result['error']) || !empty($result['raw']['error'])) {
                    Log::error('AI provider error', $result['error'] ?? ['raw_error' => $result['raw']['error'] ?? null]);
                    $this->task->update([
                        'status'  => 'failed',
                        'message' => 'Provider error',
                    ]);
                    return;
                }

                $piece = AiProvider::extractContent($result);
                $payloadChunks[] = [
                    'index'   => $index,
                    'chunk'   => $chunk,
                    'content' => $piece,
                    'raw'     => $result['raw'] ?? [],
                ];

                if (!is_string($piece) || $piece === '') {
                    $this->task->update([
                        'status'  => 'failed',
                        'message' => 'invalid json',
                    ]);
                    return;
                }

                try {
                    $decoded = json_decode($piece, true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable $e) {
                    $this->task->update([
                        'status'  => 'failed',
                        'message' => 'invalid json',
                    ]);
                    return;
                }

                if (isset($decoded['theme']) && is_array($decoded['theme'])) {
                    $theme = array_replace_recursive($theme, $decoded['theme']);
                }

                $slidesData = $decoded['slides'] ?? [];
                if (empty($slidesData)) {
                    $heading = trim(strtok($chunk, "\n")) ?: ($project->source_filename ? pathinfo($project->source_filename, PATHINFO_FILENAME) : ($project->title ?? 'Slide'));
                    $slides[] = [
                        'title' => $heading,
                        'bullets' => $this->fallbackBullets($chunk),
                        'notes' => null,
                        'background' => null,
                        'colors' => [],
                    ];
                } else {
                    foreach ($slidesData as $s) {
                        $bullets = $s['bullets'] ?? $s['bullet_points'] ?? [];
                        if (is_string($bullets)) {
                            $bullets = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $bullets))));
                        }
                        if ($requireBullets && (!is_array($bullets) || count(array_filter($bullets)) === 0)) {
                            $bullets = $this->fallbackBullets($chunk);
                        }

                        $slides[] = [
                            'title'   => $s['title'] ?? '',
                            'bullets' => is_array($bullets) ? array_values(array_filter($bullets)) : [],
                            'notes'   => $s['notes'] ?? $s['speaker_notes'] ?? null,
                            'background' => $s['background'] ?? null,
                            'colors' => $s['colors'] ?? [],
                        ];
                    }
                }

                $inputTokens  += (int) ($result['input_tokens']  ?? 0);
                $outputTokens += (int) ($result['output_tokens'] ?? 0);
                $costCents    += (int) ($result['cost_cents']    ?? 0);
            }

            $combinedJson = json_encode(['theme' => $theme, 'slides' => $slides], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $this->task->update([
                'status'        => 'done',
                'message'       => 'Generated via provider.',
                'input_tokens'  => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost_cents'    => $costCents,
            ]);

            AiTaskVersion::create([
                'id'      => (string) Str::uuid(),
                'task_id' => $this->task->id,
                'locale'  => $this->locale,
                'payload' => [
                    'content' => $combinedJson,
                    'chunks'  => $payloadChunks,
                ],
            ]);

            UsageLog::create([
                'tenant_id'  => $this->task->tenant_id,
                'user_id'    => $this->task->user_id,
                'task_id'    => $this->task->id,
                'event'      => 'task.completed',
                'cost_cents' => $costCents,
                'tokens_in'  => $inputTokens,
                'tokens_out' => $outputTokens,
            ]);

            $totalTokens = $inputTokens + $outputTokens;

            if ($totalTokens > 0 && Schema::hasColumn('users', 'usage_tokens') && $project->user) {
                $project->user->increment('usage_tokens', (int) $totalTokens);
            }
            if ($costCents > 0 && Schema::hasColumn('users', 'usage_cost_cents') && $project->user) {
                $project->user->increment('usage_cost_cents', (int) $costCents);
            }

            if ($totalTokens > 0 && Schema::hasColumn('tenants', 'usage_tokens') && $project->tenant) {
                $project->tenant->increment('usage_tokens', (int) $totalTokens);
            }
            if ($costCents > 0 && Schema::hasColumn('tenants', 'usage_cost_cents') && $project->tenant) {
                $project->tenant->increment('usage_cost_cents', (int) $costCents);
            }

            $subscription = $project->tenant->subscription ?? null;
            $counter = UsageCounter::currentFor(
                $project->user,
                $subscription?->current_period_start,
                $subscription?->current_period_end
            );
            $counter->increment('tokens_used', (int) $totalTokens);
            $counter->increment('requests_used');
            $counter->increment('cost_cents', (int) $costCents);

            return;
        }

        $payloadChunks = [];
        $summaries = [];
        $mindmap = [];

        foreach ($chunks as $index => $chunk) {
            $result = ($this->useCache ? $provider : $provider->withoutCache())
                ->generate($project, $this->task->type, $this->locale, $chunk);

            if (!empty($result['error']) || !empty($result['raw']['error'])) {
                Log::error('AI provider error', $result['error'] ?? ['raw_error' => $result['raw']['error'] ?? null]);
                $this->task->update([
                    'status'  => 'failed',
                    'message' => 'Provider error',
                ]);
                return;
            }

            $piece = AiProvider::extractContent($result);

            if (is_string($piece) && $piece !== '') {
                try {
                    $decoded = json_decode($piece, true, 512, JSON_THROW_ON_ERROR);

                    $pieceSummary = data_get($decoded, 'summary');
                    if (is_string($pieceSummary) && $pieceSummary !== '') {
                        $summaries[] = trim($pieceSummary);
                    }

                    $pieceMindmap = data_get($decoded, 'mindmap');
                    if (is_array($pieceMindmap)) {
                        foreach ($pieceMindmap as $item) {
                            if (is_string($item) && trim($item) !== '') {
                                $mindmap[] = trim($item);
                            }
                        }
                    }
                } catch (Throwable $e) {
                    // ignore invalid json pieces
                }
            }

            $payloadChunks[] = [
                'index'   => $index,
                'chunk'   => $chunk,
                'content' => $piece,
                'raw'     => $result['raw'] ?? [],
            ];

            $inputTokens  += (int) ($result['input_tokens']  ?? 0);
            $outputTokens += (int) ($result['output_tokens'] ?? 0);
            $costCents    += (int) ($result['cost_cents']    ?? 0);
        }

        $mindmap = array_values(array_unique(array_filter($mindmap)));
        $summaries = array_values(array_filter($summaries));

        $combined = [];
        if (!empty($summaries)) {
            $combined['summary'] = trim(implode("\n\n", $summaries));
        }
        if (!empty($mindmap)) {
            $combined['mindmap'] = $mindmap;
        }

        if (empty($combined)) {
            $this->task->update([
                'status'  => 'failed',
                'message' => 'invalid json',
            ]);
            return;
        }

        $combinedContent = json_encode($combined, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->task->update([
            'status'        => 'done', // <— konsisten dengan UI (queued|running|done|failed)
            'message'       => 'Generated via provider.',
            'input_tokens'  => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_cents'    => $costCents,
        ]);

        AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $this->task->id,
            'locale'  => $this->locale,
            'payload' => [
                'content' => $combinedContent, // <— dipakai UI buat preview
                'chunks'  => $payloadChunks,
            ],
        ]);

        UsageLog::create([
            'tenant_id'  => $this->task->tenant_id,
            'user_id'    => $this->task->user_id,
            'task_id'    => $this->task->id,
            'event'      => 'task.completed',
            'cost_cents' => $costCents,
            'tokens_in'  => $inputTokens,
            'tokens_out' => $outputTokens,
        ]);

        $totalTokens = $inputTokens + $outputTokens;

        if ($totalTokens > 0 && Schema::hasColumn('users', 'usage_tokens') && $project->user) {
            $project->user->increment('usage_tokens', (int) $totalTokens);
        }
        if ($costCents > 0 && Schema::hasColumn('users', 'usage_cost_cents') && $project->user) {
            $project->user->increment('usage_cost_cents', (int) $costCents);
        }

        if ($totalTokens > 0 && Schema::hasColumn('tenants', 'usage_tokens') && $project->tenant) {
            $project->tenant->increment('usage_tokens', (int) $totalTokens);
        }
        if ($costCents > 0 && Schema::hasColumn('tenants', 'usage_cost_cents') && $project->tenant) {
            $project->tenant->increment('usage_cost_cents', (int) $costCents);
        }

        $subscription = $project->tenant->subscription ?? null;
        $counter = UsageCounter::currentFor(
            $project->user,
            $subscription?->current_period_start,
            $subscription?->current_period_end
        );
        $counter->increment('tokens_used', (int) $totalTokens);
        $counter->increment('requests_used');
        $counter->increment('cost_cents', (int) $costCents);
    }

    private function fallbackBullets(string $text): array
    {
        $sentences = preg_split('/[\.!?]\s+/', strip_tags($text));
        $sentences = array_values(array_filter(array_map('trim', $sentences)));
        return array_slice($sentences, 0, 6);
    }
}
