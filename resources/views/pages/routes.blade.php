@extends('cockpit::layout')

@section('content')
{{-- Embed route data outside the HTML attribute to avoid escaping/size issues --}}
<script id="cockpit-routes-data" type="application/json">@json($routes)</script>

<div
    class="space-y-6"
    x-data="{
        search: '',
        method: 'all',
        page: 1,
        perPage: 25,
        routes: [],

        init() {
            try {
                this.routes = JSON.parse(document.getElementById('cockpit-routes-data').textContent);
            } catch (e) {
                console.error('[Cockpit] Failed to parse routes data', e);
            }
        },

        get filtered() {
            return this.routes.filter(r => {
                const matchMethod = this.method === 'all' || r.methods.includes(this.method);
                const matchSearch = this.search === '' ||
                    r.uri.toLowerCase().includes(this.search.toLowerCase()) ||
                    r.name.toLowerCase().includes(this.search.toLowerCase()) ||
                    r.action.toLowerCase().includes(this.search.toLowerCase());
                return matchMethod && matchSearch;
            });
        },

        get paginated() {
            const start = (this.page - 1) * this.perPage;
            return this.filtered.slice(start, start + this.perPage);
        },

        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },

        resetPage() { this.page = 1; },

        copyUrl(uri) {
            const base = window.location.origin;
            navigator.clipboard.writeText(base + '/' + uri.replace(/^\//, ''));
            Alpine.store('toasts').success('URL copied to clipboard');
        },
    }"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-100">Routes</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                <span x-text="filtered.length"></span> routes found
            </p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                placeholder="Search by URI, name, or action…"
                x-model="search"
                @input="resetPage()"
                class="w-full pl-9 pr-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-sm text-gray-200 placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            >
        </div>

        <div class="flex gap-1 p-1 bg-gray-900 border border-gray-700 rounded-lg shrink-0">
            @foreach(['all', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $m)
                <button
                    @click="method = '{{ $m }}'; resetPage()"
                    :class="method === '{{ $m }}'
                        ? 'bg-indigo-600 text-white'
                        : 'text-gray-400 hover:text-gray-200 hover:bg-gray-800'"
                    class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
                >
                    {{ $m }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Method</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URI</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden xl:table-cell">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Middleware</th>
                    <th class="px-4 py-3 w-20"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(route, i) in paginated" :key="i">
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
                        {{-- Methods --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="m in route.methods" :key="m">
                                    <span
                                        :class="{
                                            'bg-emerald-900/50 text-emerald-300': m === 'GET' || m === 'HEAD',
                                            'bg-blue-900/50 text-blue-300': m === 'POST',
                                            'bg-amber-900/50 text-amber-300': m === 'PUT' || m === 'PATCH',
                                            'bg-red-900/50 text-red-300': m === 'DELETE',
                                            'bg-gray-800 text-gray-400': !['GET','HEAD','POST','PUT','PATCH','DELETE'].includes(m),
                                        }"
                                        class="text-xs px-1.5 py-0.5 rounded font-mono font-medium"
                                        x-text="m"
                                    ></span>
                                </template>
                            </div>
                        </td>

                        {{-- URI --}}
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs text-gray-200" x-text="route.uri"></span>
                        </td>

                        {{-- Name --}}
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <span class="font-mono text-xs text-gray-400" x-text="route.name || '—'"></span>
                        </td>

                        {{-- Action --}}
                        <td class="px-4 py-3 hidden xl:table-cell max-w-xs">
                            <span
                                class="font-mono text-xs text-gray-400 block truncate"
                                x-text="route.action"
                                :title="route.action"
                            ></span>
                        </td>

                        {{-- Middleware --}}
                        <td class="px-4 py-3 hidden lg:table-cell">
                            <div class="flex flex-wrap gap-1">
                                <template x-for="mw in route.middleware.slice(0, 3)" :key="mw">
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-gray-800 text-gray-500 font-mono" x-text="mw.split('\\').pop()"></span>
                                </template>
                                <span
                                    x-show="route.middleware.length > 3"
                                    class="text-xs px-1.5 py-0.5 rounded bg-gray-800 text-gray-600"
                                    x-text="'+' + (route.middleware.length - 3)"
                                ></span>
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5 justify-end">
                                <template x-if="route.methods.includes('GET')">
                                    <a
                                        :href="'/' + route.uri"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Open in new tab"
                                        class="p-1.5 rounded-md bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-gray-100 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </template>
                                <button
                                    @click="copyUrl(route.uri)"
                                    title="Copy URL"
                                    class="p-1.5 rounded-md bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-gray-100 transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>

                {{-- Empty State --}}
                <tr x-show="filtered.length === 0">
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500 text-sm">
                        No routes match your filters.
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Pagination --}}
        <div x-show="totalPages > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-800">
            <p class="text-xs text-gray-500">
                Page <span x-text="page"></span> of <span x-text="totalPages"></span>
                &mdash; <span x-text="filtered.length"></span> results
            </p>
            <div class="flex gap-1.5">
                <button
                    @click="page = Math.max(1, page - 1)"
                    :disabled="page === 1"
                    class="px-3 py-1.5 text-xs rounded-md bg-gray-800 text-gray-300 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                    Previous
                </button>
                <button
                    @click="page = Math.min(totalPages, page + 1)"
                    :disabled="page === totalPages"
                    class="px-3 py-1.5 text-xs rounded-md bg-gray-800 text-gray-300 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                >
                    Next
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
