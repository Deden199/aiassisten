<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\AiProject;
use App\Services\DocumentParser;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = AiProject::where('tenant_id', $request->user()->tenant_id)
            ->where('user_id', $request->user()->id)
            ->with('tasks.versions')
            ->latest()->paginate(8);

        return view('dashboard', compact('projects'));
    }

    public function store(Request $r, DocumentParser $parser)
    {
        $r->validate([
            'title' => ['required','string','max:160'],
            'file'  => ['nullable','file','mimes:pdf,txt,doc,docx,ppt,pptx','max:10240'], // 10MB
            'language' => ['nullable','string','max:10'],
        ]);

        $path = null; $disk = 'private'; $filename = null; $text = '';
        if ($r->hasFile('file')) {
            $filename = $r->file('file')->getClientOriginalName();
            $path = $r->file('file')->store("uploads/{$r->user()->tenant_id}", $disk);
            $text = $parser->parse($disk, $path);
        }

        AiProject::create([
            'id'              => (string) Str::uuid(),
            'tenant_id'       => $r->user()->tenant_id,
            'user_id'         => $r->user()->id,
            'title'           => $r->string('title'),
            'source_filename' => $filename,
            'source_disk'     => $disk,
            'source_path'     => $path,
            'language'        => $r->input('language', $r->user()->locale ?? 'en'),
            'source_text'     => $text,
            'status'          => 'ready',
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
