<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl leading-tight">Projects</h2>
  </x-slot>

  <div class="p-6 space-y-6">
    {{-- Header & CTA --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="text-sm text-gray-500">
        {{ $projects->total() }} total
      </div>
      <a href="{{ route('dashboard') }}#new-project"
         class="inline-flex items-center px-3 py-2 rounded-lg bg-violet-600 text-white hover:bg-violet-700">
        + New Project
      </a>
    </div>

    {{-- List projects --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @forelse ($projects as $p)
        @php($latestTask = optional($p->tasks)->first())
        @php($latestVersion = optional($latestTask?->versions)->first())
        <div class="border rounded-xl p-4 bg-white/70 dark:bg-gray-900/40 shadow-sm">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="font-medium truncate">{{ $p->title ?? 'Untitled' }}</div>
              <div class="text-xs text-gray-500 mt-0.5">
                {{ $p->created_at?->format('Y-m-d H:i') }}
              </div>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full
              {{ $p->status === 'ready' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
              {{ ucfirst($p->status) }}
            </span>
          </div>

          <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
            @if($latestTask)
              <span>Last task: {{ ucfirst($latestTask->type) }} ({{ $latestTask->status }})</span>
              @if($latestVersion?->file_path)
                <a href="{{ route('versions.download', $latestVersion) }}" class="underline">Download</a>
              @endif
            @else
              <span>No tasks yet</span>
            @endif
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <form method="POST" action="{{ route('projects.destroy', $p) }}"
                  onsubmit="return confirm('Delete this project?')">
              @csrf @method('DELETE')
              <button class="text-xs px-2 py-1 rounded bg-rose-50 text-rose-600 hover:bg-rose-100">
                Delete
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="col-span-full text-sm text-gray-500">No projects yet.</div>
      @endforelse
    </div>

    {{ $projects->links() }}
  </div>
</x-app-layout>
