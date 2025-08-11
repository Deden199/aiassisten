<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlideTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SlideTemplateController extends Controller
{
    public function index(Request $r)
    {
        $templates = SlideTemplate::select('id','name')->where('tenant_id', $r->user()->tenant_id)->get();
        return view('admin.slide-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.slide-templates.form', ['template' => new SlideTemplate()]);
    }

    public function store(Request $r)
    {
        $data = $this->validateData($r);
        $data['tenant_id'] = $r->user()->tenant_id;
        SlideTemplate::create($data);
        return redirect()->route('admin.slide-templates.index')->with('ok', 'Template created');
    }

    public function edit(SlideTemplate $slideTemplate)
    {
        return view('admin.slide-templates.form', ['template' => $slideTemplate]);
    }

    public function update(Request $r, SlideTemplate $slideTemplate)
    {
        $data = $this->validateData($r, $slideTemplate->id);
        $slideTemplate->update($data);
        return redirect()->route('admin.slide-templates.index')->with('ok', 'Template updated');
    }

    public function destroy(SlideTemplate $slideTemplate)
    {
        $slideTemplate->delete();
        return back()->with('ok', 'Template deleted');
    }

    public function duplicate(SlideTemplate $slideTemplate)
    {
        $copy = $slideTemplate->replicate();
        $copy->id = (string) Str::uuid();
        $copy->name = $slideTemplate->name.' Copy';
        $copy->save();
        return back()->with('ok', 'Template duplicated');
    }

    private function validateData(Request $r, ?string $ignoreId = null): array
    {
        $tenantId = $r->user()->tenant_id;
        $hex = ['regex:/^#([A-Fa-f0-9]{6})$/'];
        $nameRule = 'unique:slide_templates,name';
        if ($ignoreId) {
            $nameRule .= ',' . $ignoreId . ',id,tenant_id,' . $tenantId;
        } else {
            $nameRule .= ',NULL,id,tenant_id,' . $tenantId;
        }

        return $r->validate([
            'name' => ['required','string','max:120',$nameRule],
            'palette.background' => $hex,
            'palette.primary' => $hex,
            'palette.secondary' => $hex,
            'palette.accent' => $hex,
            'font.family' => ['nullable','string','max:120'],
            'font.title_size' => ['required','integer','min:1'],
            'font.body_size' => ['required','integer','min:1'],
            'font.title_weight' => ['nullable','in:normal,bold'],
            'font.body_weight' => ['nullable','in:normal,bold'],
            'layout.title.x' => ['required','integer','min:0'],
            'layout.title.y' => ['required','integer','min:0'],
            'layout.title.w' => ['required','integer','min:1'],
            'layout.title.h' => ['required','integer','min:1'],
            'layout.title.align' => ['required','in:left,center,right'],
            'layout.bullets.x' => ['required','integer','min:0'],
            'layout.bullets.y' => ['required','integer','min:0'],
            'layout.bullets.w' => ['required','integer','min:1'],
            'layout.bullets.h' => ['required','integer','min:1'],
            'layout.bullets.line_spacing' => ['nullable','numeric','min:0'],
            'layout.bullets.indent' => ['nullable','integer','min:0'],
            'background_default.type' => ['required','in:solid,gradient,image'],
            'background_default.color' => ['nullable'] + $hex,
            'background_default.gradient.from' => ['nullable'] + $hex,
            'background_default.gradient.to' => ['nullable'] + $hex,
            'background_default.image_url' => ['nullable','url'],
            'rules.slides_min' => ['required','integer','min:1'],
            'rules.slides_max' => ['required','integer','min:1','gte:rules.slides_min'],
            'rules.require_bullets' => ['boolean'],
            'rules.use_gradient' => ['boolean'],
        ]);
    }
}
