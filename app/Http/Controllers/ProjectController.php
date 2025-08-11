<?php

namespace App\Http\Controllers;

use App\Models\AiProject;
use App\Models\SlideTemplate;
use App\Services\DocumentParser;
use App\Exceptions\DocumentParseException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = AiProject::select('id', 'title', 'language', 'status', 'source_filename', 'created_at')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->with([
                'tasks' => fn ($q) => $q->select('id', 'project_id', 'type', 'status', 'created_at')
                    ->latest()->limit(1)
                    ->with([
                        'versions' => fn ($q) => $q->select('id', 'task_id', 'file_path', 'file_disk', 'created_at')
                            ->latest()->limit(1),
                    ]),
            ])
            ->latest()
            ->paginate(8);

        return view('dashboard', [
            'projects' => $projects,
            'templates' => SlideTemplate::orderBy('name')->get(['id','name']),
        ]);
    }

    public function store(Request $r, DocumentParser $parser)
    {
        $r->validate([
            'title' => ['required', 'string', 'max:160'],
            'file' => ['nullable', 'file', 'mimes:pdf,txt,doc,docx,ppt,pptx', 'max:10240'], // 10MB
            'language' => ['nullable', 'string', 'max:10'],
            'slide_template_id' => ['nullable','uuid','exists:slide_templates,id'],
        ]);

        $path = null;
        $disk = 'private';
        $filename = null;
        $text = '';
        if ($r->hasFile('file')) {
            $filename = $r->file('file')->getClientOriginalName();
            $path = $r->file('file')->store("uploads/{$r->user()->tenant_id}", $disk);

            try {
                $text = $parser->parse($disk, $path);
            } catch (DocumentParseException $e) {
                return back()->withErrors(['file' => 'Unable to parse the uploaded document.']);
            }
        }

        AiProject::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $r->user()->tenant_id,
            'user_id' => $r->user()->id,
            'title' => $r->string('title'),
            'source_filename' => $filename,
            'source_disk' => $disk,
            'source_path' => $path,
            'language' => $r->input('language', $r->user()->locale ?? 'en'),
            'source_text' => $text,
            'status' => 'ready',
            'slide_template_id' => $r->input('slide_template_id'),
        ]);

        return back()->with('ok', 'Project created.');
    }

    public function destroy(Request $r, AiProject $project)
    {
        abort_unless($project->tenant_id === $r->user()->tenant_id && $project->user_id === $r->user()->id, 403);
        $project->delete();

        return back()->with('ok', 'Project deleted.');
    }
}
