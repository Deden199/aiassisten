<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use App\Models\{AiProject,AiTask}; use Illuminate\Support\Str;

class TaskController extends Controller {
    public function summarize(Request $r, AiProject $project){
        abort_unless($project->tenant_id === $r->user()->tenant_id, 403);
        $task = AiTask::create([
            'id'=>(string)Str::uuid(),
            'tenant_id'=>$r->user()->tenant_id,
            'user_id'=>$r->user()->id,
            'project_id'=>$project->id,
            'type'=>'summarize',
            'status'=>'queued'
        ]);
        // dispatch(new GenerateSummaryJob($task->id, $r->input('locale','en')));
        return response()->json(['task'=>$task]);
    }
}