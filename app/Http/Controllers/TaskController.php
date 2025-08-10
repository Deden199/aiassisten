<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessAiTask;
use App\Models\{AiProject, AiTask, AiTaskVersion};
use App\Services\PptxExporter;

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

    public function show(Request $r, AiTask $task)
    {
        $project = $r->route('project');

        abort_unless(
            $project &&
            $project->tenant_id === $r->user()->tenant_id &&
            $project->user_id === $r->user()->id &&
            $task->project_id === $project->id,
            403
        );

        return [
            'status'   => $task->status,
            'message'  => $task->message,
            'versions' => $task->versions()->latest()->get()->makeVisible('payload'),
        ];
    }

    public function summarize(Request $r, AiProject $project) { return $this->makeTask($r, $project, 'summarize', $r->input('locale','en')); }
    public function mindmap(Request $r, AiProject $project)
    {
        abort_unless($project->tenant_id === $r->user()->tenant_id && $project->user_id === $r->user()->id, 403);

        if ($r->isMethod('get')) {
            return view('tasks.mindmap', ['project' => $project]);
        }

        return $this->makeTask($r, $project, 'mindmap', $r->input('locale', 'en'));
    }
    public function slides(Request $r, AiProject $project)    { return $this->makeTask($r, $project, 'slides',    $r->input('locale','en')); }

    public function download(Request $r, AiTaskVersion $version, PptxExporter $exporter)
    {
        $project = $version->task->project;
        abort_unless($project->tenant_id === $r->user()->tenant_id && $project->user_id === $r->user()->id, 403);

        if (!$version->file_path) {
            $exporter->export($version);
            $version->refresh();
        }

        return Storage::disk($version->file_disk)->download($version->file_path, 'slides.pptx');
    }
}
