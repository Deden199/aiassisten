<?php

namespace App\Jobs;

use App\Models\AiTask;
use App\Models\AiTaskVersion;
use App\Models\UsageLog;
use App\Services\AiProvider;
use App\Support\TextChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ProcessAiTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public AiTask $task, public string $locale)
    {
    }

    public function handle(AiProvider $provider): void
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
                $text = (string) Storage::disk($disk)->get($path);
            }
        }

        $chunks = TextChunker::chunk($text);
        if (empty($chunks)) {
            $chunks = [''];
        }

        $payloadChunks = [];
        $contents = [];
        $inputTokens = 0;
        $outputTokens = 0;
        $costCents = 0;

        foreach ($chunks as $index => $chunk) {
            $result = $provider->generate($project, $this->task->type, $this->locale, $chunk);

            $piece = $result['content'] ?? $result['text'] ?? null;
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

        // Gabungkan content untuk preview UI (summary/mindmap). Slides bisa di-export saat download.
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

        // Akumulasi usage — amanin kalau kolom belum dimigrasi (biar job gak FAIL)
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
