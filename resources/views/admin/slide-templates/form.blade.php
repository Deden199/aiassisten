@extends('layouts.app')

@section('header')
<h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $template->exists ? 'Edit' : 'Create' }} Template</h2>
@endsection

@section('content')
<div class="py-6">
  <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-sm rounded-lg" x-data='@js([
          "palette" => [
              "background" => old("palette.background", data_get($template, "palette.background", "#000000")),
              "primary"   => old("palette.primary", data_get($template, "palette.primary", "#000000")),
              "secondary" => old("palette.secondary", data_get($template, "palette.secondary", "#000000")),
              "accent"    => old("palette.accent", data_get($template, "palette.accent", "#000000")),
          ],
          "bg" => [
              "type"  => old("background_default.type", data_get($template, "background_default.type", "solid")),
              "color" => old("background_default.color", data_get($template, "background_default.color", "#ffffff")),
              "from"  => old("background_default.gradient.from", data_get($template, "background_default.gradient.from", "#ffffff")),
              "to"    => old("background_default.gradient.to", data_get($template, "background_default.gradient.to", "#ffffff")),
              "image_url" => old("background_default.image_url", data_get($template, "background_default.image_url")),
          ],
      ])'>
      <form method="POST" action="{{ $template->exists ? route('admin.slide-templates.update',$template) : route('admin.slide-templates.store') }}">
        @csrf
        @if($template->exists) @method('PUT') @endif
        <div class="grid gap-4">
          <input type="text" name="name" value="{{ old('name',$template->name) }}" placeholder="Name" class="border rounded px-3 py-2" required>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2">
              <span class="text-sm w-24">Background</span>
              <input type="color" name="palette[background]" x-model="palette.background" value="{{ old('palette.background',data_get($template,'palette.background','#000000')) }}" class="border rounded px-3 py-2 flex-1" required>
            </label>
            <label class="flex items-center gap-2">
              <span class="text-sm w-24">Primary</span>
              <input type="color" name="palette[primary]" x-model="palette.primary" value="{{ old('palette.primary',data_get($template,'palette.primary','#000000')) }}" class="border rounded px-3 py-2 flex-1" required>
            </label>
            <label class="flex items-center gap-2">
              <span class="text-sm w-24">Secondary</span>
              <input type="color" name="palette[secondary]" x-model="palette.secondary" value="{{ old('palette.secondary',data_get($template,'palette.secondary','#000000')) }}" class="border rounded px-3 py-2 flex-1" required>
            </label>
            <label class="flex items-center gap-2">
              <span class="text-sm w-24">Accent</span>
              <input type="color" name="palette[accent]" x-model="palette.accent" value="{{ old('palette.accent',data_get($template,'palette.accent','#000000')) }}" class="border rounded px-3 py-2 flex-1" required>
            </label>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <input type="text" name="font[family]" value="{{ old('font.family',data_get($template,'font.family')) }}" placeholder="Font family" class="border rounded px-3 py-2">
            <input type="number" name="font[title_size]" value="{{ old('font.title_size',data_get($template,'font.title_size')) }}" placeholder="Title size" class="border rounded px-3 py-2" required>
            <input type="number" name="font[body_size]" value="{{ old('font.body_size',data_get($template,'font.body_size')) }}" placeholder="Body size" class="border rounded px-3 py-2" required>
            <select name="font[title_weight]" class="border rounded px-3 py-2">
              <option value="normal" @selected(old('font.title_weight',data_get($template,'font.title_weight'))=='normal')>normal</option>
              <option value="bold" @selected(old('font.title_weight',data_get($template,'font.title_weight'))=='bold')>bold</option>
            </select>
            <select name="font[body_weight]" class="border rounded px-3 py-2">
              <option value="normal" @selected(old('font.body_weight',data_get($template,'font.body_weight'))=='normal')>normal</option>
              <option value="bold" @selected(old('font.body_weight',data_get($template,'font.body_weight'))=='bold')>bold</option>
            </select>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <input type="number" name="layout[title][x]" value="{{ old('layout.title.x',data_get($template,'layout.title.x')) }}" placeholder="title x" class="border rounded px-3 py-2" required>
            <input type="number" name="layout[title][y]" value="{{ old('layout.title.y',data_get($template,'layout.title.y')) }}" placeholder="title y" class="border rounded px-3 py-2" required>
            <input type="number" name="layout[title][w]" value="{{ old('layout.title.w',data_get($template,'layout.title.w')) }}" placeholder="title w" class="border rounded px-3 py-2" required>
            <input type="number" name="layout[title][h]" value="{{ old('layout.title.h',data_get($template,'layout.title.h')) }}" placeholder="title h" class="border rounded px-3 py-2" required>
            <select name="layout[title][align]" class="border rounded px-3 py-2">
              <option value="left" @selected(old('layout.title.align',data_get($template,'layout.title.align'))=='left')>left</option>
              <option value="center" @selected(old('layout.title.align',data_get($template,'layout.title.align'))=='center')>center</option>
              <option value="right" @selected(old('layout.title.align',data_get($template,'layout.title.align'))=='right')>right</option>
            </select>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <input type="number" name="layout[bullets][x]" value="{{ old('layout.bullets.x',data_get($template,'layout.bullets.x')) }}" placeholder="bullets x" class="border rounded px-3 py-2" required>
            <input type="number" name="layout[bullets][y]" value="{{ old('layout.bullets.y',data_get($template,'layout.bullets.y')) }}" placeholder="bullets y" class="border rounded px-3 py-2" required>
            <input type="number" name="layout[bullets][w]" value="{{ old('layout.bullets.w',data_get($template,'layout.bullets.w')) }}" placeholder="bullets w" class="border rounded px-3 py-2" required>
            <input type="number" name="layout[bullets][h]" value="{{ old('layout.bullets.h',data_get($template,'layout.bullets.h')) }}" placeholder="bullets h" class="border rounded px-3 py-2" required>
            <input type="number" step="0.01" name="layout[bullets][line_spacing]" value="{{ old('layout.bullets.line_spacing',data_get($template,'layout.bullets.line_spacing')) }}" placeholder="line spacing" class="border rounded px-3 py-2">
            <input type="number" name="layout[bullets][indent]" value="{{ old('layout.bullets.indent',data_get($template,'layout.bullets.indent')) }}" placeholder="indent" class="border rounded px-3 py-2">
          </div>
          <div class="grid gap-2">
            <label class="block">
              <span class="text-sm">Background type</span>
              <select name="background_default[type]" x-model="bg.type" class="border rounded px-3 py-2 w-full">
                <option value="solid" @selected(old('background_default.type',data_get($template,'background_default.type'))=='solid')>solid</option>
                <option value="gradient" @selected(old('background_default.type',data_get($template,'background_default.type'))=='gradient')>gradient</option>
                <option value="image" @selected(old('background_default.type',data_get($template,'background_default.type'))=='image')>image</option>
              </select>
            </label>
            <label class="block" x-show="bg.type === 'solid'" x-cloak>
              <span class="text-sm">Color</span>
              <input type="color" name="background_default[color]" x-model="bg.color" value="{{ old('background_default.color',data_get($template,'background_default.color')) }}" class="border rounded px-3 py-2 w-full">
            </label>
            <div class="grid grid-cols-2 gap-2" x-show="bg.type === 'gradient'" x-cloak>
              <label class="block">
                <span class="text-sm">From</span>
                <input type="color" name="background_default[gradient][from]" x-model="bg.from" value="{{ old('background_default.gradient.from',data_get($template,'background_default.gradient.from')) }}" class="border rounded px-3 py-2 w-full">
              </label>
              <label class="block">
                <span class="text-sm">To</span>
                <input type="color" name="background_default[gradient][to]" x-model="bg.to" value="{{ old('background_default.gradient.to',data_get($template,'background_default.gradient.to')) }}" class="border rounded px-3 py-2 w-full">
              </label>
            </div>
            <label class="block" x-show="bg.type === 'image'" x-cloak>
              <span class="text-sm">Image URL</span>
              <input type="url" name="background_default[image_url]" x-model="bg.image_url" placeholder="image url" value="{{ old('background_default.image_url',data_get($template,'background_default.image_url')) }}" class="border rounded px-3 py-2 w-full">
            </label>
          </div>
          <div class="mt-4">
            <h3 class="font-medium mb-2">Preview</h3>
            <div class="w-full h-48 border rounded relative" x-bind:style="
                bg.type==='gradient' ? `background:linear-gradient(to bottom right, ${bg.from}, ${bg.to})` :
                bg.type==='image' && bg.image_url ? `background:url(${bg.image_url}) center/cover no-repeat` :
                `background:${bg.color || palette.background}`
            ">
              <h4 class="p-4 text-2xl font-bold" x-bind:style="`color:${palette.primary}`">Title</h4>
              <ul class="px-8" x-bind:style="`color:${palette.secondary}`">
                <li>First point</li>
                <li>Second point</li>
              </ul>
              <div class="absolute bottom-0 left-0 w-full h-2" x-bind:style="`background:${palette.accent}`"></div>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2">
            <input type="number" name="rules[slides_min]" value="{{ old('rules.slides_min',data_get($template,'rules.slides_min',5)) }}" placeholder="slides min" class="border rounded px-3 py-2" required>
            <input type="number" name="rules[slides_max]" value="{{ old('rules.slides_max',data_get($template,'rules.slides_max',10)) }}" placeholder="slides max" class="border rounded px-3 py-2" required>
            <label class="flex items-center space-x-2"><input type="checkbox" name="rules[require_bullets]" value="1" @checked(old('rules.require_bullets',data_get($template,'rules.require_bullets',true)))> <span>Require bullets</span></label>
            <label class="flex items-center space-x-2"><input type="checkbox" name="rules[use_gradient]" value="1" @checked(old('rules.use_gradient',data_get($template,'rules.use_gradient',false)))> <span>Use gradient</span></label>
          </div>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <a href="{{ route('admin.slide-templates.index') }}" class="px-4 py-2 border rounded">Cancel</a>
          <button class="px-4 py-2 rounded bg-indigo-600 text-white">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
