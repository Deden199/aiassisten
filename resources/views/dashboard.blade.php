@extends('layouts.app')

@section('title', 'Dashboard — AI Assistant')

@section('header')
  <div x-data="{ openNew:false }" class="flex items-center justify-between">
    <div>
      <h2 class="font-semibold text-xl text-gray-800">Welcome back 👋</h2>
      <p class="text-sm text-gray-500">Create projects, generate summaries, mindmaps, and slides.</p>
    </div>
    <button @click="openNew=true"
      class="px-4 py-2 rounded-xl bg-gradient-to-r from-violet-600 via-fuchsia-600 to-rose-600 text-white font-semibold shadow hover:opacity-95 transition">
      + New Project
    </button>

    {{-- Modal New Project --}}
    <div x-cloak x-show="openNew" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
      <div @click.outside="openNew=false"
           class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-black/5">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold">Create a New Project</h3>
          <button @click="openNew=false" class="p-1 rounded hover:bg-gray-100">✕</button>
        </div>

        <form class="mt-4 grid gap-4" action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="text" name="title" placeholder="Project title"
                 class="block w-full rounded-xl border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-400" required autofocus>
          <input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
                 class="block w-full rounded-xl border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-400">
          <div class="grid grid-cols-2 gap-3">
            <select name="language" class="block w-full rounded-xl border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-400">
              <option value="en">English</option>
              <option value="id">Indonesian</option>
              <option value="ar">Arabic (RTL)</option>
              <option value="es">Spanish</option>
              <option value="fr">French</option>
            </select>
            <div class="text-xs text-gray-500 self-center">PDF/DOCX/PPTX/TXT up to 10MB. Private storage.</div>
          </div>

          <div class="flex items-center justify-end gap-2 mt-2">
            <button type="button" @click="openNew=false" class="px-4 py-2 rounded-xl border">Cancel</button>
            <button class="px-4 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
              Create project
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8 space-y-8">

    {{-- Alerts --}}
    @if (session('ok'))
      <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3">
        {{ session('ok') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3">
        {{ $errors->first() }}
      </div>
    @endif

    {{-- Usage cards --}}
    <div class="grid gap-6 md:grid-cols-3">
      <div class="rounded-2xl bg-gradient-to-br from-violet-50 to-white border p-6 shadow-sm">
        <div class="text-sm text-gray-600">Credits</div>
        <div class="mt-1 text-3xl font-extrabold text-violet-700">{{ auth()->user()->credits ?? 0 }}</div>
        <div class="mt-1 text-xs text-gray-500">Resets monthly</div>
      </div>
      <div class="rounded-2xl bg-gradient-to-br from-fuchsia-50 to-white border p-6 shadow-sm">
        <div class="text-sm text-gray-600">Tasks this month</div>
        <div class="mt-1 text-3xl font-extrabold text-fuchsia-700">
          {{-- placeholder; wire up later --}}
          {{ \App\Models\AiTask::where('tenant_id', auth()->user()->tenant_id ?? null)->where('user_id', auth()->id())->whereMonth('created_at', now()->month)->count() }}
        </div>
        <div class="mt-1 text-xs text-gray-500">Queued / Completed</div>
      </div>
      <div class="rounded-2xl bg-gradient-to-br from-rose-50 to-white border p-6 shadow-sm">
        <div class="text-sm text-gray-600">Estimated cost</div>
        <div class="mt-1 text-3xl font-extrabold text-rose-700">
          ${{ number_format((\App\Models\UsageLog::where('tenant_id', auth()->user()->tenant_id ?? null)->sum('cost_cents') ?? 0)/100, 2) }}
        </div>
        <div class="mt-1 text-xs text-gray-500">Capped by tenant limit</div>
      </div>
    </div>

    {{-- Projects --}}
    <div class="rounded-2xl border bg-white p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Your projects</h3>
        <div class="text-sm text-gray-500">{{ $projects->total() }} total</div>
      </div>

      @if ($projects->count() === 0)
        <div class="text-center py-14">
          <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-violet-100 to-fuchsia-100 flex items-center justify-center text-2xl">📄</div>
          <h4 class="mt-4 text-lg font-semibold">No projects yet</h4>
          <p class="mt-1 text-gray-600">Click <span class="font-medium">New Project</span> to get started.</p>
        </div>
      @else
        <div class="grid gap-4">
            @foreach ($projects as $p)
            @php
              $statusColor = match($p->status) {
                'queued' => 'bg-amber-100 text-amber-800',
                'running' => 'bg-blue-100 text-blue-800',
                'ready' => 'bg-emerald-100 text-emerald-800',
                'failed' => 'bg-rose-100 text-rose-800',
                default => 'bg-gray-100 text-gray-800'
              };
              $summaryTask = $p->tasks->where('type','summarize')->sortByDesc('created_at')->first();
              $mindmapTask = $p->tasks->where('type','mindmap')->sortByDesc('created_at')->first();
              $slidesTask = $p->tasks->where('type','slides')->sortByDesc('created_at')->first();
              $slidesVersion = $slidesTask?->versions->sortByDesc('created_at')->first();
            @endphp
            <div class="rounded-xl border p-4 hover:bg-gray-50 transition flex flex-col">
              <div class="flex flex-col md:flex-row md:items-center justify-between">
              <div class="space-y-1">
                <div class="font-medium text-gray-900">{{ $p->title }}</div>
                <div class="text-sm text-gray-500">
                  {{ $p->source_filename ?? 'No file' }} · Lang: {{ strtoupper($p->language) }}
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs {{ $statusColor }}">
                  • {{ ucfirst($p->status) }}
                </span>
              </div>

              <div class="flex items-center gap-2 mt-3 md:mt-0">
                <form action="{{ route('tasks.summarize', $p) }}" method="POST">@csrf
                  <button class="px-3 py-2 rounded-lg border hover:bg-violet-50">Summary</button>
                </form>
                <form action="{{ route('tasks.mindmap', $p) }}" method="POST">@csrf
                  <button class="px-3 py-2 rounded-lg border hover:bg-fuchsia-50">Mindmap</button>
                </form>
                <form action="{{ route('tasks.slides', $p) }}" method="POST">@csrf
                  <button class="px-3 py-2 rounded-lg border hover:bg-rose-50">Slides</button>
                </form>
                @if($slidesVersion && $slidesVersion->file_path)
                  <a href="{{ route('versions.download', $slidesVersion) }}" class="px-3 py-2 rounded-lg border hover:bg-emerald-50">Download PPTX</a>
                @endif
                <form action="{{ route('projects.destroy', $p) }}" method="POST" onsubmit="return confirm('Delete project?')">
                  @csrf @method('DELETE')
                  <button class="px-3 py-2 rounded-lg border text-rose-600 hover:bg-rose-50">Delete</button>
                </form>
              </div>
              </div>
              <div class="mt-4 space-y-2">
                @if($summaryTask)
                  <x-task-result :project="$p" :task="$summaryTask" />
                @endif
                @if($mindmapTask)
                  <x-task-result :project="$p" :task="$mindmapTask" />
                @endif
                @if($slidesTask)
                  <x-task-result :project="$p" :task="$slidesTask" />
                @endif
              </div>
            </div>
          @endforeach
        </div>
        <div class="mt-4">{{ $projects->links() }}</div>
      @endif
    </div>
  </div>
@endsection
