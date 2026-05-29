@extends('cockpit::layout')

@section('content')
<div
    class="space-y-6"
    x-data="{
        running: null,
        tasks: @json($tasks),

        async runTask(index) {
            if (this.running !== null) return;
            this.running = index;
            try {
                const res = await cockpitFetch('{{ route('cockpit.schedule.run') }}', {
                    method: 'POST',
                    body: JSON.stringify({ index }),
                });
                const data = await res.json();
                if (data.success) {
                    Alpine.store('toasts').success(data.message || 'Task executed');
                } else {
                    Alpine.store('toasts').error(data.message || 'Task failed');
                }
            } catch (e) {
                Alpine.store('toasts').error('Network error: ' + e.message);
            } finally {
                this.running = null;
            }
        },
    }"
>

    {{-- Header --}}
    <div>
        <h2 class="text-xl font-semibold text-gray-100">Schedule</h2>
        <p class="text-sm text-gray-500 mt-0.5">
            {{ count($tasks) }} scheduled {{ Str::plural('task', count($tasks)) }}
        </p>
    </div>

    @if(empty($tasks))
        <div class="flex flex-col items-center justify-center py-20 bg-gray-900 border border-gray-800 rounded-xl text-center">
            <svg class="w-10 h-10 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm text-gray-500">No scheduled tasks registered.</p>
            <p class="text-xs text-gray-600 mt-1">
                Define tasks in <code class="font-mono">app/Console/Kernel.php</code> or
                <code class="font-mono">routes/console.php</code>
            </p>
        </div>
    @else
        <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Expression</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Command</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Next Run</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Flags</th>
                        <th class="px-4 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="task in tasks" :key="task.index">
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                            {{-- Cron Expression --}}
                            <td class="px-4 py-3">
                                <code
                                    class="text-xs font-mono text-amber-300 bg-amber-900/20 px-2 py-0.5 rounded"
                                    x-text="task.expression"
                                ></code>
                            </td>

                            {{-- Command --}}
                            <td class="px-4 py-3 max-w-xs">
                                <code
                                    class="text-xs font-mono text-gray-200 block truncate"
                                    x-text="task.command"
                                    :title="task.command"
                                ></code>
                            </td>

                            {{-- Description --}}
                            <td class="px-4 py-3 hidden md:table-cell max-w-xs">
                                <span class="text-xs text-gray-400 truncate block" x-text="task.description || '—'"></span>
                            </td>

                            {{-- Next Run --}}
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <span
                                    class="text-xs font-mono text-gray-400"
                                    x-text="task.next_run ?? '—'"
                                ></span>
                            </td>

                            {{-- Flags --}}
                            <td class="px-4 py-3 hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    <template x-if="task.without_overlapping">
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-indigo-900/40 text-indigo-300 font-mono">no-overlap</span>
                                    </template>
                                    <template x-if="task.runs_in_background">
                                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-800 text-gray-400 font-mono">background</span>
                                    </template>
                                </div>
                            </td>

                            {{-- Run Button --}}
                            <td class="px-4 py-3">
                                <button
                                    @click="runTask(task.index)"
                                    :disabled="running !== null"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-indigo-600/30 hover:bg-indigo-600/50 border border-indigo-700 text-indigo-300 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                                >
                                    <template x-if="running === task.index">
                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </template>
                                    <template x-if="running !== task.index">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                        </svg>
                                    </template>
                                    Run now
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
