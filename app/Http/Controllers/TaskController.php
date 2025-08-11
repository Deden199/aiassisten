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

        // ⬇️ KUNCI: kalau AJAX / minta JSON, balikin JSON (jangan redirect)
        if ($r->expectsJson() || $r->ajax()) {
            return response()->json([
                'ok'       => true,
                'task_id'  => $task->id,
                'status'   => $task->status,
                'message'  => $task->message,
                'poll_url' => route('tasks.show', [$project, $task]),
            ], 201);
        }

        return back()->with('ok', ucfirst($type).' queued.');
    }

    // Polling status
    public function show(Request $r, AiProject $project, AiTask $task)
    {
        abort_unless(
            $project &&
            $project->tenant_id === $r->user()->tenant_id &&
            $project->user_id === $r->user()->id &&
            $task->project_id === $project->id,
            403
        );

        $versions = $task->versions()->latest()->get();
        $downloadUrl = null;

        // kalau slides & sudah ada file, kirim link unduh
        if ($task->type === 'slides') {
            $v = $versions->first();
            if ($v && $v->file_path) {
                $downloadUrl = route('versions.download', $v);
            }
        }

        return response()->json([
            'status'       => $task->status,
            'message'      => $task->message,
            'versions'     => $versions->makeVisible('payload'),
            'download_url' => $downloadUrl,
        ]);
    }

    public function summarize(Request $r, AiProject $project)
    {
        return $this->makeTask($r, $project, 'summarize', $r->input('locale','en'));
    }

    public function mindmap(Request $r, AiProject $project)
    {
        abort_unless($project->tenant_id === $r->user()->tenant_id && $project->user_id === $r->user()->id, 403);

        if ($r->isMethod('get')) {
            return view('tasks.mindmap', ['project' => $project]);
        }

        return $this->makeTask($r, $project, 'mindmap', $r->input('locale', 'en'));
    }

    public function slides(Request $r, AiProject $project)
    {
        return $this->makeTask($r, $project, 'slides', $r->input('locale','en'));
    }

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
