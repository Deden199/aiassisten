<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Jobs\ProcessAiTask;
use App\Models\{AiProject, AiTask};

class TaskController extends Controller
{
    private function makeTask(Request $r, AiProject $project, string $type, string $locale = 'en')
    {
        abort_unless($project->tenant_id === $r->user()->tenant_id && $project->user_id === $r->user()->id, 403);

        $task = AiTask::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $r->user()->tenant_id,
            'user_id'       => $r->user()->id,
            'project_id'    => $project->id,
            'type'          => $type,
            'status'        => 'queued',
            'message'       => 'Queued for processing.',
            'input_tokens'  => 0,
            'output_tokens' => 0,
            'cost_cents'    => 0,
        ]);

        ProcessAiTask::dispatch($task, $locale);

        return back()->with('ok', ucfirst($type).' queued.');
    }

    public function summarize(Request $r, AiProject $project) { return $this->makeTask($r, $project, 'summarize', $r->input('locale','en')); }
    public function mindmap(Request $r, AiProject $project)   { return $this->makeTask($r, $project, 'mindmap',   $r->input('locale','en')); }
    public function slides(Request $r, AiProject $project)    { return $this->makeTask($r, $project, 'slides',    $r->input('locale','en')); }
}
