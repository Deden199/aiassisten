<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\{AiProject, AiTask, AiTaskVersion};

class TaskController extends Controller
{
    private function makeTask(Request $r, AiProject $project, string $type, string $locale = 'en')
    {
        abort_unless($project->tenant_id === $r->user()->tenant_id && $project->user_id === $r->user()->id, 403);

        $result = app(\App\Services\AiProvider::class)->generate($project, $type, $locale);

        $task = AiTask::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $r->user()->tenant_id,
            'user_id'       => $r->user()->id,
            'project_id'    => $project->id,
            'type'          => $type,
            'status'        => 'succeeded',
            'message'       => 'Generated via provider.',
            'input_tokens'  => $result['input_tokens'] ?? 0,
            'output_tokens' => $result['output_tokens'] ?? 0,
            'cost_cents'    => $result['cost_cents'] ?? 0,
        ]);

        AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $task->id,
            'locale'  => $locale,
            'payload' => $result['raw'] ?? [],
        ]);

        return back()->with('ok', ucfirst($type).' generated.');
    }

    public function summarize(Request $r, AiProject $project) { return $this->makeTask($r, $project, 'summarize', $r->input('locale','en')); }
    public function mindmap(Request $r, AiProject $project)   { return $this->makeTask($r, $project, 'mindmap',   $r->input('locale','en')); }
    public function slides(Request $r, AiProject $project)    { return $this->makeTask($r, $project, 'slides',    $r->input('locale','en')); }
}
