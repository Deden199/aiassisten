@extends('layouts.app')

@section('header')
  <div class="flex items-center justify-between">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
    <div class="flex gap-2">
      <a href="{{ route('admin.tenants.index') }}" class="px-3 py-2 text-sm rounded-lg border">Tenants</a>
      <a href="{{ route('admin.plans.index') }}" class="px-3 py-2 text-sm rounded-lg border">Plans</a>
      <a href="{{ route('admin.licenses.index') }}" class="px-3 py-2 text-sm rounded-lg border">Manage Licenses</a>
      <a href="{{ route('admin.slide-templates.create') }}" class="px-3 py-2 text-sm rounded-lg bg-indigo-600 text-white">New Template</a>
    </div>
  </div>
@endsection

@section('content')
@php
  $stat = $stats ?? ['tenants'=>0,'users'=>0,'plans'=>0,'subs'=>0,'licenses'=>0];
  $licenseDist = $licenseDistribution ?? ['valid'=>0,'grace'=>0,'none'=>0,'expired'=>0];
  $revenue = $revenueMonthly ?? ['labels'=>[],'values'=>[]];
  $newUsers = $newUsersWeekly ?? ['labels'=>[],'values'=>[]];
  $top = $topTenants ?? [];
  $recent = $recentSubscriptions ?? [];
@endphp

<div class="py-8">
  <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
      @php
        $cards = [
          ['label'=>'Tenants','value'=>$stat['tenants'],'icon'=>'buildings'],
          ['label'=>'Users','value'=>$stat['users'],'icon'=>'users'],
          ['label'=>'Plans','value'=>$stat['plans'],'icon'=>'plans'],
          ['label'=>'Subscriptions','value'=>$stat['subs'],'icon'=>'subs'],
          ['label'=>'Licenses','value'=>$stat['licenses'],'icon'=>'key'],
        ];
      @endphp
      @foreach($cards as $c)
      <div class="rounded-2xl border bg-white p-4 shadow-sm">
        <div class="flex items-center gap-3">
          <div class="p-2 rounded-xl bg-gray-100">
            @switch($c['icon'])
              @case('buildings')<svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="1.5" d="M3 21h18M5 21V7a2 2 0 0 1 2-2h4v16m6 0V4a1 1 0 0 0-1-1h-5"/></svg>@break
              @case('users')    <svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="1.5" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/><path stroke="currentColor" stroke-width="1.5" d="M4 21a8 8 0 1 1 16 0"/></svg>@break
              @case('plans')    <svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="1.5" d="M4 7h16M4 12h16M4 17h16"/></svg>@break
              @case('subs')     <svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="1.5" d="M3 6h18M3 12h18M3 18h18"/></svg>@break
              @case('key')      <svg class="w-5 h-5 text-gray-700" viewBox="0 0 24 24" fill="none"><path stroke="currentColor" stroke-width="1.5" d="M21 7a4 4 0 1 1-7.446 2.001L6 16.5V21h4.5l7.5-7.554A4 4 0 0 1 21 7Z"/></svg>@break
            @endswitch
          </div>
          <div>
            <div class="text-2xl font-bold">{{ number_format($c['value']) }}</div>
            <div class="text-[12px] text-gray-500">{{ $c['label'] }}</div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
      <form method="GET" class="flex items-center gap-2">
        <select name="range" class="border rounded-xl px-3 py-2">
          @foreach(['7d'=>'Last 7 days','30d'=>'Last 30 days','90d'=>'Last 90 days','ytd'=>'Year to date'] as $k=>$v)
            <option value="{{ $k }}" @selected(request('range','30d')===$k)>{{ $v }}</option>
          @endforeach
        </select>
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search tenants/users/plan"
               class="border rounded-xl px-3 py-2 w-64">
        <button class="px-3 py-2 rounded-xl bg-gray-900 text-white">Apply</button>
      </form>

      <div class="flex gap-2">
        <a href="{{ route('admin.users.index') }}" class="px-3 py-2 border rounded-xl">Users</a>
        <a href="{{ route('admin.tenants.index') }}" class="px-3 py-2 border rounded-xl">Tenants</a>
        <a href="{{ route('admin.plans.index') }}" class="px-3 py-2 border rounded-xl">Plans</a>
        <a href="{{ route('admin.subscriptions.index') }}" class="px-3 py-2 border rounded-xl">Subscriptions</a>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 rounded-2xl border bg-white p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Monthly revenue</h3>
          <span class="text-xs text-gray-500">{{ count($revenue['labels']) }} months</span>
        </div>
        <canvas id="revChart" height="120"></canvas>
      </div>
      <div class="rounded-2xl border bg-white p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">License status</h3>
          <span class="text-xs text-gray-500">current distribution</span>
        </div>
        <canvas id="licChart" height="120"></canvas>
        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600">
          @foreach(['valid','grace','none','expired'] as $k)
            <div class="flex items-center justify-between"><span class="capitalize">{{ $k }}</span><span>{{ $licenseDist[$k] ?? 0 }}</span></div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="rounded-2xl border bg-white p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Recent subscriptions</h3>
          <a href="{{ route('admin.subscriptions.index') }}" class="text-xs text-indigo-600">View all</a>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="text-left text-gray-500">
              <tr>
                <th class="py-2 pr-4">Tenant</th>
                <th class="py-2 pr-4">Plan</th>
                <th class="py-2 pr-4">Amount</th>
                <th class="py-2">Status</th>
              </tr>
            </thead>
            <tbody>
            @forelse($recent as $s)
              <tr class="border-t">
                <td class="py-2 pr-4">{{ $s['tenant'] }}</td>
                <td class="py-2 pr-4">{{ $s['plan'] }}</td>
                <td class="py-2 pr-4">{{ $s['amount_formatted'] }}</td>
                <td class="py-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px]
                    {{ $s['status']=='active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                    {{ $s['status'] }}
                  </span>
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="py-6 text-center text-gray-500">No data</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="rounded-2xl border bg-white p-4">
        <div class="flex items-center justify-between mb-2">
          <h3 class="font-semibold">Top tenants (usage)</h3>
          <a href="{{ route('admin.tenants.index') }}" class="text-xs text-indigo-600">View all</a>
        </div>
        <ul class="divide-y">
          @forelse($top as $t)
            <li class="py-2 flex items-center justify-between">
              <div>
                <div class="font-medium">{{ $t['name'] }}</div>
                <div class="text-xs text-gray-500">Users: {{ $t['users'] }} • Projects: {{ $t['projects'] ?? 0 }}</div>
              </div>
              <div class="text-right">
                <div class="font-semibold">{{ $t['usage_cost_formatted'] }}</div>
                <div class="text-xs text-gray-500">{{ $t['usage_tokens'] }} tokens</div>
              </div>
            </li>
          @empty
            <li class="py-6 text-center text-gray-500">No data</li>
          @endforelse
        </ul>
      </div>
    </div>

  </div>
</div>

<script>
  if (!window.Alpine) {
    const s = document.createElement('script');
    s.src = 'https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js';
    s.defer = true;
    document.head.appendChild(s);
  }

  (function bootCharts(){
    const init = () => {
      try {
        const rev = @js($revenue);
        const lic = @js($licenseDist);

        const rctx = document.getElementById('revChart');
        if (rctx && window.Chart) {
          new Chart(rctx, {
            type: 'line',
            data: { labels: rev.labels, datasets: [{ label: 'Revenue', data: rev.values, tension: 0.3, fill: false }] },
            options: { responsive: true, plugins:{ legend:{ display:false }}, scales:{ y:{ beginAtZero:true } } }
          });
        }

        const lctx = document.getElementById('licChart');
        if (lctx && window.Chart) {
          new Chart(lctx, {
            type: 'doughnut',
            data: { labels: ['valid','grace','none','expired'], datasets: [{ data: [lic.valid||0, lic.grace||0, lic.none||0, lic.expired||0] }] },
            options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
          });
        }
      } catch(e) { console.error('Chart init failed', e); }
    };

    if (window.Chart) {
      init();
    } else {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
      s.defer = true;
      s.onload = init;
      document.head.appendChild(s);
    }
  })();
</script>
@endsection
