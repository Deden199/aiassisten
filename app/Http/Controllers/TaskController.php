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

        $task = AiTask::create([
            'id'         => (string) Str::uuid(),
            'tenant_id'  => $r->user()->tenant_id,
            'user_id'    => $r->user()->id,
            'project_id' => $project->id,
            'type'       => $type,
            'status'     => 'succeeded',
            'message'    => 'Demo output generated.',
            'input_tokens'  => 500,
            'output_tokens' => 350,
            'cost_cents'    => 2, // dummy $0.02
        ]);

        $payload = match ($type) {
            'summarize' => [
                'abstract' => 'A concise, 150-word abstract about the uploaded material (demo).',
                'bullets'  => ['Key point one','Key point two','Key point three','Key point four'],
                'glossary' => [['term'=>'Example','def'=>'Demo definition']]
            ],
            'mindmap' => [
                'title' => $project->title,
                'nodes' => [
                    ['id'=>'n1','text'=>'Main Idea','children'=>[
                        ['id'=>'n2','text'=>'Branch A'],
                        ['id'=>'n3','text'=>'Branch B']
                    ]]
                ]
            ],
            'slides' => [
                'slides' => [
                    ['title'=>$project->title, 'bullets'=>['Subtitle / course','Author'], 'notes'=>'Welcome slide'],
                    ['title'=>'Overview','bullets'=>['Objective','Scope','Method'], 'notes'=>'Keep it brief']
                ]
            ],
            default => []
        };

        AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $task->id,
            'locale'  => $locale,
            'payload' => json_encode($payload),
        ]);

        return back()->with('ok', ucfirst($type).' generated (demo).');
    }

    public function summarize(Request $r, AiProject $project) { return $this->makeTask($r, $project, 'summarize', $r->input('locale','en')); }
    public function mindmap(Request $r, AiProject $project)   { return $this->makeTask($r, $project, 'mindmap',   $r->input('locale','en')); }
    public function slides(Request $r, AiProject $project)    { return $this->makeTask($r, $project, 'slides',    $r->input('locale','en')); }
}
