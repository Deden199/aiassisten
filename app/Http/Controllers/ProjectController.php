<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use App\Models\AiProject; use Illuminate\Support\Str;

class ProjectController extends Controller {
    public function store(Request $r){
        $r->validate(['title'=>'required|string|max:160']);
        $p = AiProject::create([
            'id'=>(string)Str::uuid(),
            'tenant_id'=>$r->user()->tenant_id,
            'user_id'=>$r->user()->id,
            'title'=>$r->string('title'),
            'status'=>'draft'
        ]);
        return response()->json($p, 201);
    }
}