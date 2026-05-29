@extends('cockpit::layout')

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <h2 class="text-xl font-semibold text-gray-100">Dashboard</h2>
        <p class="text-sm text-gray-500 mt-0.5">Application overview</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Pending Jobs --}}
        <a href="{{ route('cockpit.jobs') }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-4 hover:border-gray-700 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending Jobs</span>
                <div class="w-8 h-8 rounded-lg bg-blue-900/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-100">{{ number_format($stats['pending_jobs']) }}</p>
        </a>

        {{-- Failed Jobs --}}
        <a href="{{ route('cockpit.jobs') }}" class="block bg-gray-900 border border-gray-800 rounded-xl p-4 hover:border-gray-700 transition-colors">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Failed Jobs</span>
                <div class="w-8 h-8 rounded-lg {{ $stats['failed_jobs'] > 0 ? 'bg-red-900/40' : 'bg-gray-800' }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $stats['failed_jobs'] > 0 ? 'text-red-400' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold {{ $stats['failed_jobs'] > 0 ? 'text-red-400' : 'text-gray-100' }}">
                {{ number_format($stats['failed_jobs']) }}
            </p>
        </a>

        {{-- Cache --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Cache</span>
                <div class="w-8 h-8 rounded-lg {{ $appInfo['cache_status'] ? 'bg-emerald-900/40' : 'bg-red-900/40' }} flex items-center justify-center">
                    <svg class="w-4 h-4 {{ $appInfo['cache_status'] ? 'text-emerald-400' : 'text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-semibold {{ $appInfo['cache_status'] ? 'text-emerald-400' : 'text-red-400' }}">
                {{ $appInfo['cache_status'] ? 'Online' : 'Unreachable' }}
            </p>
            <p class="text-xs text-gray-500 mt-0.5">{{ $appInfo['cache_driver'] }}</p>
        </div>

        {{-- Queue driver --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-900/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
            </div>
            <p class="text-sm font-semibold text-gray-100">{{ $appInfo['queue_driver'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">driver</p>
        </div>
    </div>

    {{-- Bottom Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- App Info --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <h3 class="text-sm font-semibold text-gray-300 mb-4">Application Info</h3>
            <dl class="space-y-2.5">
                @foreach([
                    ['Laravel version',  $appInfo['laravel_version']],
                    ['PHP version',      $appInfo['php_version']],
                    ['Environment',      $appInfo['environment']],
                    ['Debug mode',       $appInfo['debug'] ? 'Enabled' : 'Disabled'],
                    ['Timezone',         $appInfo['timezone']],
                    ['Database',         $appInfo['db_driver']],
                    ['Config cached',    $appInfo['config_cached'] ? 'Yes' : 'No'],
                    ['Routes cached',    $appInfo['routes_cached'] ? 'Yes' : 'No'],
                ] as [$label, $value])
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-800/60 last:border-0">
                        <dt class="text-xs text-gray-500">{{ $label }}</dt>
                        <dd class="text-xs font-mono font-medium text-gray-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Recent Errors --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-300">Recent Errors</h3>
                <a href="{{ route('cockpit.logs') }}?level=ERROR" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
                    View logs →
                </a>
            </div>

            @if(empty($recentErrors))
                <div class="flex flex-col items-center justify-center py-8 text-center">
                    <svg class="w-8 h-8 text-emerald-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-gray-500">No recent errors</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($recentErrors as $error)
                        <div class="p-3 bg-gray-800/60 rounded-lg border border-gray-700/50">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs px-1.5 py-0.5 rounded font-mono bg-red-900/50 text-red-300">
                                    {{ $error['level'] }}
                                </span>
                                <span class="text-xs text-gray-500 font-mono">{{ $error['date'] }}</span>
                            </div>
                            <p class="text-xs text-gray-300 font-mono leading-relaxed">{{ $error['message'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gray-300 mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-3">
            @foreach([
                ['route' => 'cockpit.routes',   'label' => 'Browse Routes',    'color' => 'indigo'],
                ['route' => 'cockpit.commands',  'label' => 'Run a Command',    'color' => 'violet'],
                ['route' => 'cockpit.jobs',      'label' => 'Inspect Jobs',     'color' => 'blue'],
                ['route' => 'cockpit.logs',      'label' => 'Read Logs',        'color' => 'amber'],
                ['route' => 'cockpit.schedule',  'label' => 'View Schedule',    'color' => 'teal'],
                ['route' => 'cockpit.events',    'label' => 'List Events',      'color' => 'pink'],
            ] as $action)
                <a
                    href="{{ route($action['route']) }}"
                    class="px-4 py-2 text-sm font-medium rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-gray-100 border border-gray-700 hover:border-gray-600 transition-colors"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>

</div>
@endsection
