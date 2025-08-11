<?php

namespace App\Jobs;

use App\Models\AiTask;
use App\Models\AiTaskVersion;
use App\Models\UsageLog;
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

    public function __construct(public AiTask $task, public string $locale)
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

            foreach ($chunks as $chunk) {
                $result = $provider->generate($project, $this->task->type, $this->locale, $chunk);

                if (!empty($result['error']) || !empty($result['raw']['error'])) {
                    Log::error('AI provider error', $result['error'] ?? ['raw_error' => $result['raw']['error'] ?? null]);
                    $this->task->update([
                        'status'  => 'failed',
                        'message' => 'Provider error',
                    ]);
                    return;
                }

                $piece = AiProvider::extractContent($result);

                $decoded = [];
                if (is_string($piece) && $piece !== '') {
                    try {
                        $decoded = json_decode($piece, true, 512, JSON_THROW_ON_ERROR);
                    } catch (Throwable $e) {
                        $decoded = [];
                    }
                }

                $slidesData = $decoded['slides'] ?? (is_array($decoded) ? $decoded : []);
                foreach ($slidesData as $s) {
                    $bullets = $s['bullets'] ?? $s['bullet_points'] ?? [];
                    if (is_string($bullets)) {
                        $bullets = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $bullets))));
                    }

                    $slides[] = [
                        'title'   => $s['title'] ?? '',
                        'bullets' => is_array($bullets) ? array_values($bullets) : [],
                        'notes'   => $s['notes'] ?? $s['speaker_notes'] ?? null,
                    ];
                }

                $inputTokens  += (int) ($result['input_tokens']  ?? 0);
                $outputTokens += (int) ($result['output_tokens'] ?? 0);
                $costCents    += (int) ($result['cost_cents']    ?? 0);
            }

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
                'payload' => $slides,
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

            return;
        }

        $payloadChunks = [];
        $contents = [];

        foreach ($chunks as $index => $chunk) {
            $result = $provider->generate($project, $this->task->type, $this->locale, $chunk);

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
                $contents[] = trim($piece);
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

        $combinedContent = trim(implode("\n\n", array_filter($contents)));

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
    }

    public function failed(Throwable $e): void
    {
        $this->task->update([
            'status'  => 'failed',
            'message' => $e->getMessage(),
        ]);
    }
}
