<?php

namespace App\Jobs;

use App\Models\AiTask;
use App\Models\AiTaskVersion;
use App\Services\AiProvider;
use App\Support\TextChunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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
        $project = $this->task->project;
        $text = $project->source_text ?? '';
        if (!$text && $project->source_disk && $project->source_path) {
            $text = (string) Storage::disk($project->source_disk)->get($project->source_path);
        }

        $chunks = TextChunker::chunk($text);
        if (empty($chunks)) {
            $chunks = [''];
        }

        $payload = [];
        $inputTokens = $outputTokens = $costCents = 0;

        foreach ($chunks as $index => $chunk) {
            $result = $provider->generate($project, $this->task->type, $this->locale, $chunk);
            $payload[] = [
                'index' => $index,
                'chunk' => $chunk,
                'raw'   => $result['raw'] ?? [],
            ];
            $inputTokens += $result['input_tokens'] ?? 0;
            $outputTokens += $result['output_tokens'] ?? 0;
            $costCents += $result['cost_cents'] ?? 0;
        }

        $this->task->update([
            'status'        => 'succeeded',
            'message'       => 'Generated via provider.',
            'input_tokens'  => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_cents'    => $costCents,
        ]);

        AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $this->task->id,
            'locale'  => $this->locale,
            'payload' => $payload,
        ]);
    }

    public function failed(Throwable $e): void
    {
        $this->task->update([
            'status'  => 'failed',
            'message' => $e->getMessage(),
        ]);
    }
}
