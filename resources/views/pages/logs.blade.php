@extends('cockpit::layout')

@section('content')
<div
    class="space-y-6"
    x-data="{
        autoRefresh: false,
        refreshInterval: null,

        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.refreshInterval = setInterval(() => window.location.reload(), 10000);
                Alpine.store('toasts').info('Auto-refresh enabled (10s)');
            } else {
                clearInterval(this.refreshInterval);
                Alpine.store('toasts').info('Auto-refresh disabled');
            }
        },

        expanded: {},
        toggle(i) { this.expanded[i] = !this.expanded[i]; },

        async clearLogs() {
            if (!confirm('Clear the entire log file?')) return;
            const res = await cockpitFetch('{{ route('cockpit.logs.clear') }}', { method: 'DELETE' });
            const data = await res.json();
            if (data.success) {
                Alpine.store('toasts').success('Logs cleared');
                setTimeout(() => window.location.reload(), 800);
            }
        },
    }"
    x-init="
        // Stop auto-refresh when leaving the page
        window.addEventListener('beforeunload', () => { if (refreshInterval) clearInterval(refreshInterval); });
    "
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-100">Logs</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ number_format($total) }} entries
                @if($exists)
                    · {{ number_format($fileSize / 1024, 1) }} KB
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                @click="toggleAutoRefresh()"
                :class="autoRefresh ? 'bg-emerald-700 border-emerald-600 text-emerald-100' : 'bg-gray-800 border-gray-700 text-gray-300'"
                class="flex items-center gap-2 px-3 py-2 text-sm border rounded-lg transition-colors hover:opacity-90"
            >
                <svg class="w-4 h-4" :class="autoRefresh ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Auto-refresh
            </button>

            <a
                href="{{ route('cockpit.logs.download') }}"
                class="flex items-center gap-2 px-3 py-2 text-sm bg-gray-800 hover:bg-gray-700 border border-gray-700 text-gray-300 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download
            </a>

            <button
                @click="clearLogs()"
                class="flex items-center gap-2 px-3 py-2 text-sm bg-red-900/40 hover:bg-red-900/60 border border-red-800 text-red-300 rounded-lg transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear logs
            </button>
        </div>
    </div>

    {{-- Level Filter --}}
    <div class="flex flex-wrap gap-1.5">
        @php
            $levels = ['all', 'DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];
            $levelColors = [
                'all'       => 'bg-gray-700 text-gray-200',
                'DEBUG'     => 'bg-gray-700 text-gray-300',
                'INFO'      => 'bg-blue-900/60 text-blue-300',
                'NOTICE'    => 'bg-cyan-900/60 text-cyan-300',
                'WARNING'   => 'bg-amber-900/60 text-amber-300',
                'ERROR'     => 'bg-red-900/60 text-red-300',
                'CRITICAL'  => 'bg-red-800/80 text-red-200',
                'ALERT'     => 'bg-orange-900/60 text-orange-300',
                'EMERGENCY' => 'bg-pink-900/60 text-pink-300',
            ];
        @endphp

        @foreach($levels as $lvl)
            <a
                href="{{ route('cockpit.logs') }}?level={{ $lvl }}&page=1"
                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors
                    {{ $level === $lvl
                        ? $levelColors[$lvl] . ' border-transparent ring-2 ring-indigo-500'
                        : 'bg-gray-900 border-gray-700 text-gray-400 hover:border-gray-600 hover:text-gray-200'
                    }}"
            >
                {{ $lvl === 'all' ? 'All' : $lvl }}
            </a>
        @endforeach
    </div>

    {{-- Truncation warning --}}
    @if($truncated)
        <div class="flex items-center gap-3 px-4 py-3 bg-amber-900/20 border border-amber-700/50 rounded-xl text-sm text-amber-300">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Log file is large — only the last {{ number_format(config('cockpit.log_max_read_bytes') / (1024 * 1024), 0) }} MB are shown.
            Increase <code class="font-mono">COCKPIT_LOG_MAX_BYTES</code> or download the full file.
        </div>
    @endif

    {{-- File missing --}}
    @if(!$exists)
        <div class="flex flex-col items-center justify-center py-16 bg-gray-900 border border-gray-800 rounded-xl text-center">
            <svg class="w-10 h-10 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-gray-500">Log file not found at <code class="font-mono text-gray-400">{{ config('cockpit.log_file') }}</code></p>
        </div>
    @elseif(count($entries) === 0)
        <div class="flex flex-col items-center justify-center py-16 bg-gray-900 border border-gray-800 rounded-xl text-center">
            <svg class="w-10 h-10 text-emerald-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-gray-500">No log entries for the selected level.</p>
        </div>
    @else
        {{-- Log Entries --}}
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <div class="divide-y divide-gray-800/50">
                @foreach($entries as $i => $entry)
                    @php
                        $bgLevel = match($entry['level']) {
                            'ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT' => 'hover:bg-red-950/20',
                            'WARNING' => 'hover:bg-amber-950/20',
                            default => 'hover:bg-gray-800/30',
                        };
                        $levelBadge = match($entry['level']) {
                            'DEBUG'     => 'bg-gray-800 text-gray-400',
                            'INFO'      => 'bg-blue-900/50 text-blue-300',
                            'NOTICE'    => 'bg-cyan-900/50 text-cyan-300',
                            'WARNING'   => 'bg-amber-900/50 text-amber-300',
                            'ERROR'     => 'bg-red-900/50 text-red-300',
                            'CRITICAL'  => 'bg-red-800/70 text-red-200',
                            'ALERT'     => 'bg-orange-900/50 text-orange-300',
                            'EMERGENCY' => 'bg-pink-900/50 text-pink-300',
                            default     => 'bg-gray-800 text-gray-400',
                        };
                    @endphp
                    <div
                        class="transition-colors {{ $bgLevel }}"
                        x-data="{ open: false }"
                    >
                        <button
                            @click="open = !open"
                            class="w-full flex items-start gap-3 px-4 py-3 text-left"
                        >
                            <span class="text-xs px-1.5 py-0.5 rounded font-mono font-medium shrink-0 mt-0.5 {{ $levelBadge }}">
                                {{ $entry['level'] }}
                            </span>
                            <span class="text-xs font-mono text-gray-500 shrink-0">
                                {{ $entry['date'] }}
                            </span>
                            <span class="text-xs text-gray-300 flex-1 font-mono leading-relaxed">
                                {{ $entry['message'] }}
                            </span>
                            @if(!empty($entry['context']))
                                <svg
                                    class="w-4 h-4 text-gray-500 shrink-0 mt-0.5 transition-transform"
                                    :class="open ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            @endif
                        </button>

                        @if(!empty($entry['context']))
                            <div x-show="open" x-cloak class="px-4 pb-3">
                                <pre class="text-xs font-mono text-gray-400 bg-gray-950 border border-gray-800 rounded-lg p-3 overflow-x-auto scrollbar-thin max-h-48 overflow-y-auto">{{ $entry['context'] }}</pre>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            @if($totalPages > 1)
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-800">
                    <p class="text-xs text-gray-500">
                        Page {{ $page }} of {{ $totalPages }} · {{ number_format($total) }} entries
                    </p>
                    <div class="flex gap-1.5">
                        @if($page > 1)
                            <a
                                href="{{ route('cockpit.logs') }}?level={{ $level }}&page={{ $page - 1 }}"
                                class="px-3 py-1.5 text-xs rounded-md bg-gray-800 text-gray-300 hover:bg-gray-700 transition-colors"
                            >
                                Previous
                            </a>
                        @endif

                        @foreach(range(max(1, $page - 2), min($totalPages, $page + 2)) as $p)
                            <a
                                href="{{ route('cockpit.logs') }}?level={{ $level }}&page={{ $p }}"
                                class="px-3 py-1.5 text-xs rounded-md transition-colors
                                    {{ $p === $page
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-800 text-gray-300 hover:bg-gray-700' }}"
                            >
                                {{ $p }}
                            </a>
                        @endforeach

                        @if($page < $totalPages)
                            <a
                                href="{{ route('cockpit.logs') }}?level={{ $level }}&page={{ $page + 1 }}"
                                class="px-3 py-1.5 text-xs rounded-md bg-gray-800 text-gray-300 hover:bg-gray-700 transition-colors"
                            >
                                Next
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
