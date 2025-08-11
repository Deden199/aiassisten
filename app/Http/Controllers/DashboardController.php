<?php

namespace App\Http\Controllers;

use App\Models\AiProject;
use App\Models\SlideTemplate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $projects = AiProject::select('id','title','language','status','source_filename','created_at')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->with([
                'tasks' => fn ($q) => $q->select('id','project_id','type','status','created_at')
                    ->latest()->limit(1)
                    ->with([
                        'versions' => fn ($q) => $q->select('id','task_id','file_path','file_disk','created_at')
                            ->latest()->limit(1),
                    ]),
            ])
            ->latest()
            ->paginate(6); // dashboard cukup sedikit, biar ringan

        $templates = SlideTemplate::orderBy('name')->get(['id','name']);

        return view('dashboard', compact('projects','templates'));
    }
}
