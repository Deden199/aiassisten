@extends('layouts.app')

@section('title', 'Dashboard — AI Assistant')

@section('header')
  <div x-data="{ openNew:false }" class="flex flex-col md:flex-row md:items-center justify-between gap-3">
    <div>
      <h2 class="font-semibold text-xl text-gray-800">Welcome back 👋</h2>
      <p class="text-sm text-gray-500">Create projects, generate summaries, mindmaps, and slides.</p>
    </div>
    <button @click="openNew=true"
      class="px-4 py-2 rounded-xl bg-gradient-to-r from-violet-600 via-fuchsia-600 to-rose-600 text-white font-semibold shadow hover:opacity-95 transition whitespace-nowrap">
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
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <select name="language" class="block w-full rounded-xl border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-400">
              <option value="en">English</option>
              <option value="id">Indonesian</option>
              <option value="ar">Arabic (RTL)</option>
              <option value="es">Spanish</option>
              <option value="fr">French</option>
            </select>
            <div class="text-xs text-gray-500 self-center">PDF/DOCX/PPTX/TXT up to 10MB. Private storage.</div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 mt-2">
            <button type="button" @click="openNew=false" class="px-4 py-2 rounded-xl border w-full sm:w-auto">Cancel</button>
            <button class="px-4 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition w-full sm:w-auto">
              Create project
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('content')
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

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

    {{-- ===== Satu Alpine instance (stabil & responsif) ===== --}}
    <div x-data="taskRunner">

      {{-- Active tasks list (global) --}}
      <div class="rounded-2xl border bg-white p-6 shadow-sm mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" fill="none"/></svg>
            <h3 class="text-sm font-semibold">Active tasks</h3>
          </div>
          <span class="text-xs text-gray-500" x-text="visibleList().length + ' running/recent'"></span>
        </div>

        <template x-if="visibleList().length === 0">
          <p class="mt-3 text-sm text-gray-500">No active tasks.</p>
        </template>

        <div class="mt-4 space-y-4" x-show="visibleList().length > 0">
          <template x-for="it in visibleList()" :key="it.key">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <div class="text-sm">
                  <span class="font-medium" x-text="it.title || 'Project'"></span>
                  <span class="text-gray-500">—</span>
                  <span class="uppercase text-gray-700" x-text="it.type"></span>
                </div>
                <span class="px-2 py-0.5 text-xs rounded-full" :class="badgeClass(it.status)" x-text="it.status"></span>
              </div>
              <div class="w-full h-2 bg-gray-100 rounded overflow-hidden">
                <div class="h-full w-1/2 bg-blue-400 animate-[indeterminate_1.2s_ease_infinite]"></div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <style>
      @keyframes indeterminate{0%{transform:translateX(-100%)}50%{transform:translateX(0%)}100%{transform:translateX(100%)}}
      </style>

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
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                  <div class="space-y-1 min-w-0">
                    <div class="font-medium text-gray-900 truncate">{{ $p->title }}</div>
                    <div class="text-sm text-gray-500 truncate">
                      {{ $p->source_filename ?? 'No file' }} · Lang: {{ strtoupper($p->language) }}
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs {{ $statusColor }}">
                      • {{ ucfirst($p->status) }}
                    </span>
                  </div>

                  <!-- Action buttons -->
                  <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 w-full">
                    <button
                      @click="run('{{ $p->id }}','summarize','{{ route('tasks.summarize', $p) }}','{{ e($p->title) }}')"
                      :disabled="isPending('{{ $p->id }}','summarize')"
                      class="w-full sm:w-auto px-3 py-2 rounded-lg border hover:bg-violet-50 flex items-center gap-2 disabled:opacity-50">
                      <svg x-show="isPending('{{ $p->id }}','summarize')" class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      </svg>
                      <span x-text="isPending('{{ $p->id }}','summarize') ? 'Queuing…' : 'Summary'"></span>
                    </button>

                    <button
                      @click="run('{{ $p->id }}','mindmap','{{ route('tasks.mindmap', $p) }}','{{ e($p->title) }}')"
                      :disabled="isPending('{{ $p->id }}','mindmap')"
                      class="w-full sm:w-auto px-3 py-2 rounded-lg border hover:bg-fuchsia-50 flex items-center gap-2 disabled:opacity-50">
                      <svg x-show="isPending('{{ $p->id }}','mindmap')" class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      </svg>
                      <span x-text="isPending('{{ $p->id }}','mindmap') ? 'Queuing…' : 'Mindmap'"></span>
                    </button>

                    <button
                      @click="run('{{ $p->id }}','slides','{{ route('tasks.slides', $p) }}','{{ e($p->title) }}')"
                      :disabled="isPending('{{ $p->id }}','slides')"
                      class="w-full sm:w-auto px-3 py-2 rounded-lg border hover:bg-rose-50 flex items-center gap-2 disabled:opacity-50">
                      <svg x-show="isPending('{{ $p->id }}','slides')" class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      </svg>
                      <span x-text="isPending('{{ $p->id }}','slides') ? 'Queuing…' : 'Slides'"></span>
                    </button>

                    @if($slidesVersion && $slidesVersion->file_path)
                      <a href="{{ route('versions.download', $slidesVersion) }}" class="w-full sm:w-auto px-3 py-2 rounded-lg border hover:bg-emerald-50 text-center">Download PPTX</a>
                    @endif

                    <form action="{{ route('projects.destroy', $p) }}" method="POST" onsubmit="return confirm('Delete project?')" class="w-full sm:w-auto">
                      @csrf @method('DELETE')
                      <button class="w-full sm:w-auto px-3 py-2 rounded-lg border text-rose-600 hover:bg-rose-50">Delete</button>
                    </form>
                  </div>
                </div>

                <div class="mt-4 space-y-2">
                  @if($summaryTask) <x-task-result :project="$p" :task="$summaryTask" /> @endif
                  @if($mindmapTask) <x-task-result :project="$p" :task="$mindmapTask" /> @endif
                  @if($slidesTask)  <x-task-result :project="$p" :task="$slidesTask" />  @endif
                </div>

                {{-- Inline progress untuk project ini --}}
                <div class="mt-3 space-y-2"
                     x-show="isPending('{{ $p->id }}','summarize') || isPending('{{ $p->id }}','mindmap') || isPending('{{ $p->id }}','slides')">
                  <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium">Processing…</span>
                    <span class="px-2 py-0.5 rounded-full"
                          :class="badgeClass(status('{{ $p->id }}'))"
                          x-text="status('{{ $p->id }}') || 'running'"></span>
                  </div>
                  <div class="w-full h-2 bg-gray-100 rounded overflow-hidden">
                    <div class="h-full w-1/2 bg-blue-400 animate-[indeterminate_1.2s_ease_infinite]"></div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <div class="mt-4">{{ $projects->links() }}</div>
        @endif
      </div>

      {{-- Toast --}}
      <div x-show="toast.show" x-transition.opacity class="fixed bottom-4 right-4 z-50">
        <div class="px-4 py-3 rounded-xl shadow-lg text-white"
             :class="toast.type==='error' ? 'bg-rose-600' : (toast.type==='success' ? 'bg-emerald-600' : 'bg-gray-900')"
             x-text="toast.msg"></div>
      </div>

    </div>
  </div>
@endsection

