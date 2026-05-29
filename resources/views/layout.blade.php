<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cockpit — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gray: {
                            950: '#0a0a0f',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #374151; border-radius: 2px; }
        pre { white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="h-full bg-gray-950 text-gray-100 dark" x-data="cockpitApp()" @keydown.escape="closeModal()" @cockpit:open-modal.window="openModal($event.detail.title, $event.detail.content)">

{{-- Toast Container --}}
<div
    x-data
    class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
    style="min-width: 280px; max-width: 420px;"
    aria-live="polite"
>
    <template x-for="(toast, i) in $store.toasts.items" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            :class="{
                'bg-emerald-900 border-emerald-700 text-emerald-100': toast.type === 'success',
                'bg-red-900 border-red-700 text-red-100': toast.type === 'error',
                'bg-blue-900 border-blue-700 text-blue-100': toast.type === 'info',
            }"
            class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-lg border shadow-xl text-sm"
        >
            <span class="mt-0.5 shrink-0">
                <template x-if="toast.type === 'success'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="toast.type === 'error'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </template>
                <template x-if="toast.type === 'info'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </template>
            </span>
            <span x-text="toast.message" class="flex-1"></span>
        </div>
    </template>
</div>

{{-- Modal --}}
<div
    x-show="modal.open"
    x-cloak
    class="fixed inset-0 z-40 flex items-center justify-center p-4"
    @click.self="closeModal()"
>
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
    <div class="relative z-10 bg-gray-900 border border-gray-700 rounded-xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[80vh]">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
            <h3 class="font-semibold text-gray-100" x-text="modal.title"></h3>
            <button @click="closeModal()" class="text-gray-400 hover:text-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-5 scrollbar-thin">
            <div x-html="modal.content" class="text-sm text-gray-300 font-mono"></div>
        </div>
    </div>
</div>

{{-- Layout Shell --}}
<div class="flex h-full min-h-screen">

    {{-- Sidebar --}}
    <aside class="flex flex-col w-56 shrink-0 bg-gray-900 border-r border-gray-800">
        {{-- Logo --}}
        <div class="flex items-center gap-2.5 px-4 h-14 border-b border-gray-800">
            <div class="flex items-center justify-center w-7 h-7 rounded-md bg-indigo-600">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                </svg>
            </div>
            <span class="font-bold text-sm tracking-wide text-gray-100">Cockpit</span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 py-4 px-2 space-y-0.5 overflow-y-auto scrollbar-thin">
            @php
                $navItems = [
                    ['route' => 'cockpit.dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                    ['route' => 'cockpit.routes',    'label' => 'Routes',    'icon' => 'map'],
                    ['route' => 'cockpit.commands',  'label' => 'Commands',  'icon' => 'terminal'],
                    ['route' => 'cockpit.jobs',      'label' => 'Jobs',      'icon' => 'queue'],
                    ['route' => 'cockpit.logs',      'label' => 'Logs',      'icon' => 'logs'],
                    ['route' => 'cockpit.schedule',  'label' => 'Schedule',  'icon' => 'clock'],
                    ['route' => 'cockpit.events',    'label' => 'Events',    'icon' => 'zap'],
                ];
            @endphp

            @foreach($navItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a
                    href="{{ route($item['route']) }}"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors group
                        {{ $active
                            ? 'bg-indigo-600/20 text-indigo-300'
                            : 'text-gray-400 hover:bg-gray-800 hover:text-gray-100' }}"
                >
                    <span class="w-4 h-4 shrink-0 {{ $active ? 'text-indigo-400' : 'text-gray-500 group-hover:text-gray-300' }}">
                        @include('cockpit::_icons.' . $item['icon'])
                    </span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Footer --}}
        <div class="px-4 py-3 border-t border-gray-800">
            <div class="text-xs text-gray-600">Laravel {{ app()->version() }}</div>
            <div class="text-xs text-gray-600">PHP {{ PHP_VERSION }}</div>
        </div>
    </aside>

    {{-- Main --}}
    <div class="flex flex-col flex-1 min-w-0">

        {{-- Topbar --}}
        <header class="flex items-center justify-between px-6 h-14 border-b border-gray-800 bg-gray-900 shrink-0">
            <div class="flex items-center gap-3">
                <h1 class="text-sm font-semibold text-gray-100">{{ config('app.name', 'Application') }}</h1>
                @php
                    $env = app()->environment();
                    $envColor = match($env) {
                        'production' => 'bg-red-900/40 text-red-300 border-red-800',
                        'staging'    => 'bg-amber-900/40 text-amber-300 border-amber-800',
                        default      => 'bg-emerald-900/40 text-emerald-300 border-emerald-800',
                    };
                @endphp
                <span class="text-xs px-2 py-0.5 rounded-md border font-mono {{ $envColor }}">
                    {{ $env }}
                </span>
                @if(config('app.debug'))
                    <span class="text-xs px-2 py-0.5 rounded-md border bg-orange-900/40 text-orange-300 border-orange-800 font-mono">
                        debug on
                    </span>
                @endif
            </div>
            <div class="text-xs text-gray-500">
                {{ now()->format('D d M Y, H:i') }}
            </div>
        </header>

        {{-- Page Content --}}
        <main class="flex-1 overflow-y-auto p-6 scrollbar-thin">
            @yield('content')
        </main>
    </div>

</div>

<script>
function cockpitApp() {
    return {
        modal: { open: false, title: '', content: '' },

        openModal(title, content) {
            this.modal = { open: true, title, content };
            document.body.style.overflow = 'hidden';
        },

        closeModal() {
            this.modal.open = false;
            document.body.style.overflow = '';
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.store('toasts', {
        items: [],
        _counter: 0,

        add(message, type = 'info', duration = 3500) {
            const id = ++this._counter;
            this.items.push({ id, message, type, visible: true });

            setTimeout(() => {
                const item = this.items.find(t => t.id === id);
                if (item) item.visible = false;
                setTimeout(() => {
                    this.items = this.items.filter(t => t.id !== id);
                }, 300);
            }, duration);
        },

        success(msg) { this.add(msg, 'success'); },
        error(msg)   { this.add(msg, 'error', 5000); },
        info(msg)    { this.add(msg, 'info'); },
    });
});

// Shared CSRF-aware fetch helper
window.cockpitFetch = async function(url, options = {}) {
    const defaults = {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    };
    return fetch(url, { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) } });
};
</script>

{{-- Inline icon partials to avoid extra files --}}
@push('icons')@endpush
</body>
</html>
