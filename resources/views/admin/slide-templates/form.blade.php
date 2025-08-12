@extends('layouts.app')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800 leading-tight">
    {{ $template->exists ? 'Edit' : 'Create' }} Template
  </h2>
@endsection

@section('content')
@php
    // Initial state for the form & preview (server -> client)
    $initial = [
        'palette' => [
            'background' => old('palette.background', data_get($template,'palette.background','#0f172a')),
            'primary'    => old('palette.primary',    data_get($template,'palette.primary','#111827')),
            'secondary'  => old('palette.secondary',  data_get($template,'palette.secondary','#334155')),
            'accent'     => old('palette.accent',     data_get($template,'palette.accent','#6366f1')),
        ],
        'bg' => [
            'type'      => old('background_default.type',  data_get($template,'background_default.type','solid')),
            'color'     => old('background_default.color', data_get($template,'background_default.color','#ffffff')),
            'from'      => old('background_default.gradient.from', data_get($template,'background_default.gradient.from','#f8fafc')),
            'to'        => old('background_default.gradient.to',   data_get($template,'background_default.gradient.to','#e5e7eb')),
            'image_url' => old('background_default.image_url',     data_get($template,'background_default.image_url','')),
        ],
        'font' => [
            'family'       => old('font.family',       data_get($template,'font.family','Inter, system-ui, sans-serif')),
            'title_size'   => (int) old('font.title_size', data_get($template,'font.title_size',32)),
            'body_size'    => (int) old('font.body_size',  data_get($template,'font.body_size',16)),
            'title_weight' => old('font.title_weight', data_get($template,'font.title_weight','bold')),
            'body_weight'  => old('font.body_weight',  data_get($template,'font.body_weight','normal')),
        ],
        'layout' => [
            'title' => [
                'x' => (float) old('layout.title.x', data_get($template,'layout.title.x',10)),
                'y' => (float) old('layout.title.y', data_get($template,'layout.title.y',10)),
                'w' => (float) old('layout.title.w', data_get($template,'layout.title.w',80)),
                'h' => (float) old('layout.title.h', data_get($template,'layout.title.h',20)),
                'align' => old('layout.title.align', data_get($template,'layout.title.align','left')),
            ],
            'bullets' => [
                'x' => (float) old('layout.bullets.x', data_get($template,'layout.bullets.x',10)),
                'y' => (float) old('layout.bullets.y', data_get($template,'layout.bullets.y',35)),
                'w' => (float) old('layout.bullets.w', data_get($template,'layout.bullets.w',80)),
                'h' => (float) old('layout.bullets.h', data_get($template,'layout.bullets.h',55)),
                'line_spacing' => (float) old('layout.bullets.line_spacing', data_get($template,'layout.bullets.line_spacing',1.4)),
                'indent'       => (int)   old('layout.bullets.indent',       data_get($template,'layout.bullets.indent',0)),
            ],
        ],
        'rules' => [
            'slides_min'      => (int) old('rules.slides_min', data_get($template,'rules.slides_min',5)),
            'slides_max'      => (int) old('rules.slides_max', data_get($template,'rules.slides_max',10)),
            'require_bullets' => (bool) old('rules.require_bullets', data_get($template,'rules.require_bullets',true)),
            'use_gradient'    => (bool) old('rules.use_gradient', data_get($template,'rules.use_gradient',false)),
        ],
    ];
@endphp

<div class="py-6">
  <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow-sm rounded-lg"
         x-data="templateForm(@js($initial))">

      {{-- Validation errors --}}
      @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">
          <strong class="font-medium">Please fix the following:</strong>
          <ul class="list-disc pl-5">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ $template->exists ? route('admin.slide-templates.update',$template) : route('admin.slide-templates.store') }}">
        @csrf
        @if($template->exists) @method('PUT') @endif

        <div class="grid gap-6">

          {{-- BASIC --}}
          <div>
            <label class="block text-sm font-medium text-gray-700">Template name</label>
            <input type="text" name="name" value="{{ old('name',$template->name) }}"
                   placeholder="e.g. Minimal Indigo"
                   class="mt-1 w-full border rounded px-3 py-2" required>
            <p class="mt-1 text-xs text-gray-500">Internal name shown in the template list.</p>
          </div>

          {{-- PALETTE --}}
          <div>
            <h3 class="font-medium text-gray-900 mb-2">Color palette</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
              <div>
                <label class="block text-xs text-gray-600">Background</label>
                <input type="color" name="palette[background]" x-model="palette.background"
                       class="w-full h-10 border rounded">
                <p class="mt-1 text-[11px] text-gray-500">Decorative background elements.</p>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Primary (title)</label>
                <input type="color" name="palette[primary]" x-model="palette.primary"
                       class="w-full h-10 border rounded">
                <p class="mt-1 text-[11px] text-gray-500">Headline color.</p>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Secondary (body)</label>
                <input type="color" name="palette[secondary]" x-model="palette.secondary"
                       class="w-full h-10 border rounded">
                <p class="mt-1 text-[11px] text-gray-500">Body/bullets color.</p>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Accent (divider)</label>
                <input type="color" name="palette[accent]" x-model="palette.accent"
                       class="w-full h-10 border rounded">
                <p class="mt-1 text-[11px] text-gray-500">Accent bar at the bottom.</p>
              </div>
            </div>
          </div>

          {{-- TYPOGRAPHY --}}
          <div>
            <h3 class="font-medium text-gray-900 mb-2">Typography</h3>
            <div class="grid md:grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-600">Font family</label>
                <input type="text" name="font[family]" x-model="font.family"
                       placeholder="Inter, system-ui, sans-serif"
                       class="mt-1 w-full border rounded px-3 py-2">
                <p class="mt-1 text-[11px] text-gray-500">CSS font stack (fallbacks allowed).</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs text-gray-600">Title size (px)</label>
                  <input type="number" name="font[title_size]" x-model.number="font.title_size"
                         min="10" max="96" step="1"
                         class="mt-1 w-full border rounded px-3 py-2" required>
                </div>
                <div>
                  <label class="block text-xs text-gray-600">Body size (px)</label>
                  <input type="number" name="font[body_size]" x-model.number="font.body_size"
                         min="8" max="48" step="1"
                         class="mt-1 w-full border rounded px-3 py-2" required>
                </div>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Title weight</label>
                <select name="font[title_weight]" x-model="font.title_weight" class="mt-1 w-full border rounded px-3 py-2">
                  <option value="normal">normal</option>
                  <option value="bold">bold</option>
                  <option value="600">600</option>
                  <option value="700">700</option>
                  <option value="800">800</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Body weight</label>
                <select name="font[body_weight]" x-model="font.body_weight" class="mt-1 w-full border rounded px-3 py-2">
                  <option value="normal">normal</option>
                  <option value="bold">bold</option>
                  <option value="400">400</option>
                  <option value="500">500</option>
                </select>
              </div>
            </div>
          </div>

          {{-- LAYOUT --}}
          <div>
            <h3 class="font-medium text-gray-900 mb-2">Layout (percentage of slide width/height)</h3>
            <div class="grid md:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-600">Title X (%)</label>
                <input type="number" name="layout[title][x]" x-model.number="layout.title.x"
                       min="0" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Title Y (%)</label>
                <input type="number" name="layout[title][y]" x-model.number="layout.title.y"
                       min="0" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Title W (%)</label>
                <input type="number" name="layout[title][w]" x-model.number="layout.title.w"
                       min="1" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Title H (%)</label>
                <input type="number" name="layout[title][h]" x-model.number="layout.title.h"
                       min="1" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Title align</label>
                <select name="layout[title][align]" x-model="layout.title.align" class="mt-1 w-full border rounded px-3 py-2">
                  <option value="left">left</option>
                  <option value="center">center</option>
                  <option value="right">right</option>
                </select>
              </div>
            </div>

            <div class="mt-3 grid md:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-600">Bullets X (%)</label>
                <input type="number" name="layout[bullets][x]" x-model.number="layout.bullets.x"
                       min="0" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Bullets Y (%)</label>
                <input type="number" name="layout[bullets][y]" x-model.number="layout.bullets.y"
                       min="0" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Bullets W (%)</label>
                <input type="number" name="layout[bullets][w]" x-model.number="layout.bullets.w"
                       min="1" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Bullets H (%)</label>
                <input type="number" name="layout[bullets][h]" x-model.number="layout.bullets.h"
                       min="1" max="100" step="0.1"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Line spacing</label>
                <input type="number" name="layout[bullets][line_spacing]" x-model.number="layout.bullets.line_spacing"
                       step="0.05" min="1" max="3"
                       class="mt-1 w-full border rounded px-3 py-2">
                <p class="mt-1 text-[11px] text-gray-500">Distance between bullet lines.</p>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Indent (px)</label>
                <input type="number" name="layout[bullets][indent]" x-model.number="layout.bullets.indent"
                       step="1" min="0"
                       class="mt-1 w-full border rounded px-3 py-2">
                <p class="mt-1 text-[11px] text-gray-500">Left padding for bullets.</p>
              </div>
            </div>
          </div>

          {{-- BACKGROUND --}}
          <div>
            <h3 class="font-medium text-gray-900 mb-2">Background</h3>
            <div class="grid md:grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-600">Type</label>
                <select name="background_default[type]" x-model="bg.type" class="mt-1 w-full border rounded px-3 py-2">
                  <option value="solid">solid</option>
                  <option value="gradient">gradient</option>
                  <option value="image">image</option>
                </select>
              </div>
              <div x-show="bg.type==='solid'">
                <label class="block text-xs text-gray-600">Color</label>
                <input type="color" name="background_default[color]" x-model="bg.color"
                       class="mt-1 w-full h-10 border rounded">
              </div>
              <div x-show="bg.type==='gradient'">
                <label class="block text-xs text-gray-600">From</label>
                <input type="color" name="background_default[gradient][from]" x-model="bg.from"
                       class="mt-1 w-full h-10 border rounded">
              </div>
              <div x-show="bg.type==='gradient'">
                <label class="block text-xs text-gray-600">To</label>
                <input type="color" name="background_default[gradient][to]" x-model="bg.to"
                       class="mt-1 w-full h-10 border rounded">
              </div>
              <div class="md:col-span-2" x-show="bg.type==='image'">
                <label class="block text-xs text-gray-600">Image URL</label>
                <input type="url" name="background_default[image_url]" x-model="bg.image_url"
                       placeholder="https://example.com/background.jpg"
                       class="mt-1 w-full border rounded px-3 py-2">
                <p class="mt-1 text-[11px] text-gray-500">Cover & center.</p>
              </div>
            </div>
          </div>

          {{-- RULES --}}
          <div>
            <h3 class="font-medium text-gray-900 mb-2">Content rules</h3>
            <div class="grid md:grid-cols-4 gap-3">
              <div>
                <label class="block text-xs text-gray-600">Slides min</label>
                <input type="number" name="rules[slides_min]" x-model.number="rules.slides_min"
                       min="1" max="100"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div>
                <label class="block text-xs text-gray-600">Slides max</label>
                <input type="number" name="rules[slides_max]" x-model.number="rules.slides_max"
                       min="1" max="200"
                       class="mt-1 w-full border rounded px-3 py-2" required>
              </div>
              <div class="flex items-center gap-2 pt-6">
                <input type="hidden" name="rules[require_bullets]" value="0">
                <input type="checkbox" name="rules[require_bullets]" value="1" x-model="rules.require_bullets">
                <span class="text-sm">Require bullets</span>
              </div>
              <div class="flex items-center gap-2 pt-6">
                <input type="hidden" name="rules[use_gradient]" value="0">
                <input type="checkbox" name="rules[use_gradient]" value="1" x-model="rules.use_gradient">
                <span class="text-sm">Prefer gradient</span>
              </div>
            </div>
          </div>

          {{-- LIVE PREVIEW --}}
          <div class="mt-2">
            <h3 class="font-medium mb-2">Live preview</h3>
            <div class="relative w-full border rounded overflow-hidden"
                 style="aspect-ratio: 16/9"
                 :style="bgStyle()">

              {{-- Title box --}}
              <div :style="boxStyle(layout.title)">
                <div class="p-3 h-full flex items-center"
                     :style="titleStyle()">
                  <div class="w-full" :style="`text-align:${layout.title.align}`">
                    <div>Presentation Title</div>
                  </div>
                </div>
              </div>

              {{-- Bullets box --}}
              <div :style="boxStyle(layout.bullets)">
                <ul class="h-full px-6 list-disc"
                    :style="bulletStyle() + `;padding-left:${(layout.bullets.indent||0)}px`">
                  <li>First point that demonstrates the style</li>
                  <li>Second point with a little more text</li>
                  <li>Third point to check wrapping</li>
                </ul>
              </div>

              {{-- Accent bar --}}
              <div class="absolute bottom-0 left-0 w-full h-2" :style="`background:${palette.accent}`"></div>
            </div>
            <p class="mt-2 text-[11px] text-gray-500">
              16:9 live preview. Title and bullets boxes follow your layout percentages.
            </p>
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

{{-- Alpine bootstrap (safeguard): load Alpine if not already present, then define templateForm() --}}
<script>
  if (!window.Alpine) {
    const s = document.createElement('script');
    s.src = 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js';
    s.defer = true;
    document.head.appendChild(s);
  }
  // Alpine data factory
  window.templateForm = function(initial) {
    return {
      ...initial,
      // Computed styles
      bgStyle() {
        if (this.rules.use_gradient) this.bg.type = 'gradient';
        if (this.bg.type === 'gradient') {
          return `background:linear-gradient(135deg, ${this.bg.from}, ${this.bg.to})`;
        }
        if (this.bg.type === 'image' && this.bg.image_url) {
          return `background-image:url('${this.bg.image_url}');background-size:cover;background-position:center;background-color:${this.palette.background}`;
        }
        return `background:${this.bg.color}`;
      },
      titleStyle() {
        return `color:${this.palette.primary};font-family:${this.font.family||'inherit'};font-weight:${this.font.title_weight};font-size:${this.font.title_size}px;`;
      },
      bulletStyle() {
        return `color:${this.palette.secondary};font-family:${this.font.family||'inherit'};font-weight:${this.font.body_weight};font-size:${this.font.body_size}px;line-height:${this.layout.bullets.line_spacing||1.4};`;
      },
      boxStyle(rect) {
        const l = Number(rect.x||0), t = Number(rect.y||0), w = Number(rect.w||50), h = Number(rect.h||20);
        return `position:absolute;left:${l}%;top:${t}%;width:${w}%;height:${h}%;`;
      }
    }
  }
</script>
@endsection
