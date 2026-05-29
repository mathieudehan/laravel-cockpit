@extends('cockpit::layout')

@section('content')
<div
    class="space-y-6"
    x-data="{
        search: '',
        events: @json($events),

        get filtered() {
            if (this.search === '') return this.events;
            const q = this.search.toLowerCase();
            return this.events.filter(e =>
                e.event.toLowerCase().includes(q) ||
                e.listeners.some(l => l.toLowerCase().includes(q))
            );
        },
    }"
>

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-100">Events</h2>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ count($events) }} {{ Str::plural('event', count($events)) }} registered
            </p>
        </div>
    </div>

    {{-- Search --}}
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input
            type="text"
            x-model="search"
            placeholder="Search events or listeners…"
            class="w-full pl-9 pr-4 py-2.5 bg-gray-900 border border-gray-700 rounded-lg text-sm text-gray-200 placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
        >
    </div>

    {{-- Events List --}}
    @if(empty($events))
        <div class="flex flex-col items-center justify-center py-20 bg-gray-900 border border-gray-800 rounded-xl text-center">
            <svg class="w-10 h-10 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <p class="text-sm text-gray-500">No events registered.</p>
        </div>
    @else
        <div class="space-y-2">
            <template x-for="(event, i) in filtered" :key="event.event">
                <div
                    class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden"
                    x-data="{ open: false }"
                >
                    <button
                        @click="open = !open"
                        class="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-800/30 transition-colors text-left"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            {{-- Event name badge --}}
                            <div class="w-8 h-8 rounded-lg bg-indigo-900/40 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-mono text-gray-200 truncate" x-text="event.short"></p>
                                <p class="text-xs text-gray-500 font-mono truncate mt-0.5" x-text="event.event"></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0 ml-4">
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-800 text-gray-400">
                                <span x-text="event.count"></span> <span x-text="event.count === 1 ? 'listener' : 'listeners'"></span>
                            </span>
                            <svg
                                class="w-4 h-4 text-gray-500 transition-transform"
                                :class="open ? 'rotate-180' : ''"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    <div x-show="open" x-cloak class="border-t border-gray-800">
                        <div class="px-5 py-3 space-y-2">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Listeners</p>
                            <template x-for="(listener, li) in event.listeners" :key="li">
                                <div class="flex items-center gap-2.5 py-1.5">
                                    <div class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0"></div>
                                    <code
                                        class="text-xs font-mono text-gray-300 break-all"
                                        x-text="listener"
                                    ></code>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="filtered.length === 0" class="py-12 text-center bg-gray-900 border border-gray-800 rounded-xl">
                <p class="text-sm text-gray-500">No events match your search.</p>
            </div>
        </div>
    @endif

</div>
@endsection
