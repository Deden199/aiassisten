<?php

namespace App\Jobs;

use App\Models\AiTask;
use App\Models\AiTaskVersion;
use App\Services\AiProvider;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
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
        $result = $provider->generate($project, $this->task->type, $this->locale);

        $this->task->update([
            'status'        => 'succeeded',
            'message'       => 'Generated via provider.',
            'input_tokens'  => $result['input_tokens'] ?? 0,
            'output_tokens' => $result['output_tokens'] ?? 0,
            'cost_cents'    => $result['cost_cents'] ?? 0,
        ]);

        AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $this->task->id,
            'locale'  => $this->locale,
            'payload' => $result['raw'] ?? [],
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
