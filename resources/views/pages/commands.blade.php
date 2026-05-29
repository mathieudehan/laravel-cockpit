@extends('cockpit::layout')

@section('content')
<div
    class="space-y-6"
    x-data="{
        search: '',
        namespace: 'all',
        selectedCommand: null,
        paramValues: {},
        optionValues: {},
        running: false,
        output: null,
        outputSuccess: null,
        showHistory: false,

        commands: @json($commands),
        history: @json($history),

        get namespaces() {
            return ['all', ...Object.keys(this.commands)];
        },

        get filtered() {
            let result = [];
            for (const [ns, cmds] of Object.entries(this.commands)) {
                if (this.namespace !== 'all' && ns !== this.namespace) continue;
                for (const cmd of cmds) {
                    if (this.search === '' || cmd.name.includes(this.search) || cmd.description.toLowerCase().includes(this.search.toLowerCase())) {
                        result.push({ ...cmd, namespace: ns });
                    }
                }
            }
            return result;
        },

        select(cmd) {
            this.selectedCommand = cmd;
            this.paramValues = {};
            this.optionValues = {};
            this.output = null;
            this.outputSuccess = null;
            cmd.arguments.forEach(a => { this.paramValues[a.name] = a.default ?? ''; });
            cmd.options.forEach(o => { this.optionValues[o.name] = o.default ?? (o.accepts_value ? '' : false); });
        },

        async run() {
            if (!this.selectedCommand || this.running) return;
            this.running = true;
            this.output = null;

            const params = { ...this.paramValues };
            for (const [key, val] of Object.entries(this.optionValues)) {
                if (val === true) params['--' + key] = true;
                else if (val !== false && val !== '') params['--' + key] = val;
            }

            try {
                const res = await cockpitFetch('{{ route('cockpit.commands.run') }}', {
                    method: 'POST',
                    body: JSON.stringify({ command: this.selectedCommand.name, params }),
                });
                const data = await res.json();
                this.output = data.output;
                this.outputSuccess = data.success;

                if (data.success) Alpine.store('toasts').success('Command executed successfully');
                else Alpine.store('toasts').error('Command returned an error');

                // Refresh history
                const r2 = await fetch(window.location.href);
                const html = await r2.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
            } catch (e) {
                this.output = 'Network error: ' + e.message;
                this.outputSuccess = false;
                Alpine.store('toasts').error('Network error');
            } finally {
                this.running = false;
            }
        },

        async clearHistory() {
            await cockpitFetch('{{ route('cockpit.commands.history.clear') }}', { method: 'DELETE' });
            this.history = [];
            Alpine.store('toasts').info('History cleared');
        },
    }"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-100">Commands</h2>
            <p class="text-sm text-gray-500 mt-0.5">Run Artisan commands from the browser</p>
        </div>
        <button
            @click="showHistory = !showHistory"
            class="flex items-center gap-2 px-3 py-2 text-sm bg-gray-800 hover:bg-gray-700 border border-gray-700 text-gray-300 rounded-lg transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            History
            <span x-show="history.length > 0" class="px-1.5 py-0.5 rounded text-xs bg-indigo-600 text-white" x-text="history.length"></span>
        </button>
    </div>

    {{-- History Panel --}}
    <div x-show="showHistory" x-cloak class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-300">Recent Executions</h3>
            <button @click="clearHistory()" class="text-xs text-red-400 hover:text-red-300 transition-colors">Clear all</button>
        </div>
        <template x-if="history.length === 0">
            <p class="text-sm text-gray-500">No commands have been run yet.</p>
        </template>
        <div class="space-y-3">
            <template x-for="(item, i) in history" :key="i">
                <div class="p-3 bg-gray-800/60 rounded-lg border border-gray-700/50">
                    <div class="flex items-center justify-between mb-1.5">
                        <code class="text-xs text-indigo-300 font-mono" x-text="'php artisan ' + item.command"></code>
                        <div class="flex items-center gap-2">
                            <span :class="item.exit_code === 0 ? 'text-emerald-400' : 'text-red-400'" class="text-xs font-mono">
                                exit <span x-text="item.exit_code"></span>
                            </span>
                            <span class="text-xs text-gray-500 font-mono" x-text="item.ran_at"></span>
                        </div>
                    </div>
                    <pre class="text-xs text-gray-400 bg-gray-900 rounded p-2 mt-1.5 max-h-24 overflow-y-auto scrollbar-thin" x-text="item.output"></pre>
                </div>
            </template>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Commands List --}}
        <div class="lg:col-span-1 space-y-3">
            {{-- Search & Filter --}}
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    placeholder="Filter commands…"
                    x-model="search"
                    class="w-full pl-9 pr-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-sm text-gray-200 placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
            </div>

            <select
                x-model="namespace"
                class="w-full px-3 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-indigo-500"
            >
                <template x-for="ns in namespaces" :key="ns">
                    <option :value="ns" x-text="ns === 'all' ? 'All namespaces' : ns"></option>
                </template>
            </select>

            {{-- List --}}
            <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden max-h-[calc(100vh-280px)] overflow-y-auto scrollbar-thin">
                <template x-for="(cmd, i) in filtered" :key="cmd.name">
                    <button
                        @click="select(cmd)"
                        :class="selectedCommand?.name === cmd.name
                            ? 'bg-indigo-600/20 border-l-2 border-indigo-500'
                            : 'hover:bg-gray-800/50 border-l-2 border-transparent'"
                        class="w-full text-left px-4 py-3 border-b border-gray-800/50 last:border-0 transition-colors"
                    >
                        <p class="text-xs font-mono text-gray-200" x-text="cmd.name"></p>
                        <p class="text-xs text-gray-500 mt-0.5 truncate" x-text="cmd.description || 'No description'"></p>
                    </button>
                </template>
                <div x-show="filtered.length === 0" class="px-4 py-8 text-center text-sm text-gray-500">
                    No commands found.
                </div>
            </div>
        </div>

        {{-- Command Form + Output --}}
        <div class="lg:col-span-2 space-y-4">
            <template x-if="!selectedCommand">
                <div class="flex flex-col items-center justify-center h-full py-20 text-center bg-gray-900 border border-gray-800 rounded-xl">
                    <svg class="w-10 h-10 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-gray-500">Select a command to configure and run it</p>
                </div>
            </template>

            <template x-if="selectedCommand">
                <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 space-y-5">
                    {{-- Command header --}}
                    <div class="flex items-start justify-between">
                        <div>
                            <code class="text-sm font-mono text-indigo-300" x-text="'php artisan ' + selectedCommand.name"></code>
                            <p class="text-xs text-gray-500 mt-1" x-text="selectedCommand.description || 'No description'"></p>
                        </div>
                        <button
                            @click="run()"
                            :disabled="running"
                            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors shrink-0"
                        >
                            <template x-if="running">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                            <template x-if="!running">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            <span x-text="running ? 'Running…' : 'Run'"></span>
                        </button>
                    </div>

                    {{-- Arguments --}}
                    <template x-if="selectedCommand.arguments.length > 0">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Arguments</h4>
                            <div class="space-y-2">
                                <template x-for="arg in selectedCommand.arguments" :key="arg.name">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1 font-mono">
                                            <span x-text="arg.name"></span>
                                            <template x-if="arg.required">
                                                <span class="text-red-400 ml-0.5">*</span>
                                            </template>
                                        </label>
                                        <p x-show="arg.description" class="text-xs text-gray-600 mb-1" x-text="arg.description"></p>
                                        <input
                                            type="text"
                                            x-model="paramValues[arg.name]"
                                            :placeholder="arg.default ?? ''"
                                            class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-gray-200 font-mono placeholder-gray-600 focus:outline-none focus:border-indigo-500"
                                        >
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Options --}}
                    <template x-if="selectedCommand.options.length > 0">
                        <div>
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Options</h4>
                            <div class="space-y-2">
                                <template x-for="opt in selectedCommand.options" :key="opt.name">
                                    <div>
                                        <template x-if="opt.accepts_value">
                                            <div>
                                                <label class="block text-xs text-gray-400 mb-1 font-mono">--<span x-text="opt.name"></span></label>
                                                <p x-show="opt.description" class="text-xs text-gray-600 mb-1" x-text="opt.description"></p>
                                                <input
                                                    type="text"
                                                    x-model="optionValues[opt.name]"
                                                    :placeholder="String(opt.default ?? '')"
                                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-gray-200 font-mono placeholder-gray-600 focus:outline-none focus:border-indigo-500"
                                                >
                                            </div>
                                        </template>
                                        <template x-if="!opt.accepts_value">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    x-model="optionValues[opt.name]"
                                                    class="w-4 h-4 rounded border-gray-600 bg-gray-800 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-gray-900"
                                                >
                                                <span>
                                                    <span class="text-xs text-gray-300 font-mono">--<span x-text="opt.name"></span></span>
                                                    <span x-show="opt.description" class="text-xs text-gray-500 ml-2" x-text="opt.description"></span>
                                                </span>
                                            </label>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Output --}}
                    <template x-if="output !== null">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Output</h4>
                                <span
                                    :class="outputSuccess ? 'bg-emerald-900/50 text-emerald-300' : 'bg-red-900/50 text-red-300'"
                                    class="text-xs px-1.5 py-0.5 rounded font-mono"
                                    x-text="outputSuccess ? 'success' : 'error'"
                                ></span>
                            </div>
                            <pre
                                class="text-xs font-mono text-gray-300 bg-gray-950 border border-gray-800 rounded-lg p-4 overflow-x-auto scrollbar-thin max-h-64 overflow-y-auto"
                                x-text="output"
                            ></pre>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
