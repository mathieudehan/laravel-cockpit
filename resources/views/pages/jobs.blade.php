@extends('cockpit::layout')

@section('content')
<script id="cockpit-pending-jobs" type="application/json">@json($pendingJobs)</script>
<script id="cockpit-failed-jobs"  type="application/json">@json($failedJobs)</script>
<script id="cockpit-queue-stats"  type="application/json">@json($queueStats)</script>

<div
    class="space-y-6"
    x-data="{
        tab: 'failed',
        pendingJobs: [],
        failedJobs: [],
        queueStats: {},
        loading: false,

        init() {
            try {
                this.pendingJobs = JSON.parse(document.getElementById('cockpit-pending-jobs').textContent);
                this.failedJobs  = JSON.parse(document.getElementById('cockpit-failed-jobs').textContent);
                this.queueStats  = JSON.parse(document.getElementById('cockpit-queue-stats').textContent);
            } catch (e) {
                console.error('[Cockpit] Failed to parse jobs data', e);
            }
        },

        async retry(id) {
            if (!confirm('Retry this job?')) return;
            this.loading = true;
            try {
                const res = await cockpitFetch('{{ url(config('cockpit.path', 'cockpit') . '/jobs/failed') }}/' + id + '/retry', { method: 'POST' });
                const data = await res.json();
                if (data.success) {
                    this.failedJobs = this.failedJobs.filter(j => j.id != id);
                    Alpine.store('toasts').success(data.message);
                } else {
                    Alpine.store('toasts').error(data.message);
                }
            } catch (e) {
                Alpine.store('toasts').error('Network error');
            } finally {
                this.loading = false;
            }
        },

        async destroy(id) {
            if (!confirm('Delete this failed job permanently?')) return;
            this.loading = true;
            try {
                const res = await cockpitFetch('{{ url(config('cockpit.path', 'cockpit') . '/jobs/failed') }}/' + id, { method: 'DELETE' });
                const data = await res.json();
                if (data.success) {
                    this.failedJobs = this.failedJobs.filter(j => j.id != id);
                    Alpine.store('toasts').success(data.message);
                } else {
                    Alpine.store('toasts').error(data.message);
                }
            } catch (e) {
                Alpine.store('toasts').error('Network error');
            } finally {
                this.loading = false;
            }
        },

        async clearFailed() {
            if (!confirm('Delete ALL failed jobs? This cannot be undone.')) return;
            this.loading = true;
            try {
                const res = await cockpitFetch('{{ route('cockpit.jobs.clear-failed') }}', { method: 'DELETE' });
                const data = await res.json();
                if (data.success) {
                    this.failedJobs = [];
                    Alpine.store('toasts').success(data.message);
                } else {
                    Alpine.store('toasts').error(data.message);
                }
            } catch (e) {
                Alpine.store('toasts').error('Network error');
            } finally {
                this.loading = false;
            }
        },

    }"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-100">Jobs & Queues</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ count($pendingJobs) }} pending · {{ count($failedJobs) }} failed
            </p>
        </div>
        <button
            @click="clearFailed()"
            :disabled="failedJobs.length === 0 || loading"
            class="px-3 py-2 text-sm bg-red-900/40 hover:bg-red-900/60 border border-red-800 text-red-300 rounded-lg disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
        >
            Clear all failed
        </button>
    </div>

    {{-- Queue Stats --}}
    @if(count($queueStats) > 0)
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach($queueStats as $queue => $count)
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1 font-mono">{{ $queue }}</p>
            <p class="text-2xl font-bold text-gray-100">{{ $count }}</p>
            <p class="text-xs text-gray-500">pending</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tabs --}}
    <div class="flex gap-1 p-1 bg-gray-900 border border-gray-800 rounded-lg w-fit">
        <button
            @click="tab = 'pending'"
            :class="tab === 'pending' ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:text-gray-200'"
            class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
        >
            Pending
            <span class="ml-1.5 text-xs opacity-70" x-text="pendingJobs.length"></span>
        </button>
        <button
            @click="tab = 'failed'"
            :class="tab === 'failed' ? 'bg-red-600 text-white' : 'text-gray-400 hover:text-gray-200'"
            class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
        >
            Failed
            <span class="ml-1.5 text-xs opacity-70" x-text="failedJobs.length"></span>
        </button>
    </div>

    {{-- Pending Jobs Table --}}
    <div x-show="tab === 'pending'" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Attempts</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available At</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="job in pendingJobs" :key="job.id">
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 text-xs font-mono text-gray-400" x-text="job.id"></td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-800 text-gray-300 font-mono" x-text="job.queue"></span>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-200 max-w-xs truncate" x-text="job.job_class.split('\\\\').pop()"></td>
                        <td class="px-4 py-3 text-xs text-gray-400 font-mono" x-text="job.attempts"></td>
                        <td class="px-4 py-3 text-xs text-gray-400 font-mono" x-text="job.available_at"></td>
                        <td class="px-4 py-3">
                            <span
                                :class="job.reserved_at ? 'bg-amber-900/50 text-amber-300' : 'bg-blue-900/50 text-blue-300'"
                                class="text-xs px-2 py-0.5 rounded font-mono"
                                x-text="job.reserved_at ? 'Processing' : 'Waiting'"
                            ></span>
                        </td>
                    </tr>
                </template>
                <tr x-show="pendingJobs.length === 0">
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                        No pending jobs.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Failed Jobs Table --}}
    <div x-show="tab === 'failed'" class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Queue</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Class</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exception</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Failed At</th>
                    <th class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Actions</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="job in failedJobs" :key="job.id">
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                        <td class="px-4 py-3 text-xs font-mono text-gray-400" x-text="job.id"></td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-800 text-gray-300 font-mono" x-text="job.queue"></span>
                        </td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-200 max-w-xs truncate" x-text="job.job_class.split('\\\\').pop()"></td>
                        <td class="px-4 py-3 max-w-sm">
                            <button
                                @click="$dispatch('cockpit:open-modal', { title: 'Exception — job #' + job.id, content: '<pre>' + job.exception.replace(/</g, '&amp;lt;') + '</pre>' })"
                                class="text-xs text-gray-400 hover:text-red-300 truncate block w-full text-left transition-colors"
                                x-text="job.exception.split('\\n')[0] || '—'"
                                title="Click to see full exception"
                            ></button>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400 font-mono" x-text="job.failed_at"></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <button
                                    @click="retry(job.id)"
                                    :disabled="loading"
                                    title="Retry"
                                    class="p-1.5 rounded-md bg-blue-900/40 hover:bg-blue-800/60 text-blue-300 disabled:opacity-40 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                                <button
                                    @click="destroy(job.id)"
                                    :disabled="loading"
                                    title="Delete"
                                    class="p-1.5 rounded-md bg-red-900/40 hover:bg-red-800/60 text-red-300 disabled:opacity-40 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="failedJobs.length === 0">
                    <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                        No failed jobs. Everything looks healthy!
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

@endsection
