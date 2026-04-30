<x-app-layout>

    {{-- Overview header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Overview</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                Real-time health for
                <span class="font-semibold text-text">web-prod-01</span>
                · last updated just now
            </p>
        </div>

        {{-- Time range + Refresh --}}
        <div class="flex items-center gap-2">
            <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                <button class="px-3 py-1.5 text-xs text-muted hover:text-text font-mono transition-colors duration-150">15m</button>
                <button class="px-3 py-1.5 text-xs text-text font-mono bg-panel border-x border-border">1h</button>
                <button class="px-3 py-1.5 text-xs text-muted hover:text-text font-mono transition-colors duration-150">6h</button>
                <button class="px-3 py-1.5 text-xs text-muted hover:text-text font-mono transition-colors duration-150">24h</button>
                <button class="px-3 py-1.5 text-xs text-muted hover:text-text font-mono transition-colors duration-150 border-l border-border">7d</button>
            </div>
            <button class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-4 gap-4 mb-5">

        {{-- CPU --}}
        <div class="bg-panel border border-border rounded-lg px-4 py-3">
            <p class="text-xs text-muted font-mono uppercase tracking-widest mb-1">CPU Usage</p>
            <p class="text-2xl font-semibold text-text font-mono">42<span class="text-sm text-muted">%</span></p>
            <p class="text-xs text-live font-mono mt-1">↓ normal</p>
        </div>

        {{-- RAM --}}
        <div class="bg-panel border border-border rounded-lg px-4 py-3">
            <p class="text-xs text-muted font-mono uppercase tracking-widest mb-1">RAM Usage</p>
            <p class="text-2xl font-semibold text-text font-mono">6.2<span class="text-sm text-muted">GB</span></p>
            <p class="text-xs text-warning font-mono mt-1">↑ elevated</p>
        </div>

        {{-- Disk --}}
        <div class="bg-panel border border-border rounded-lg px-4 py-3">
            <p class="text-xs text-muted font-mono uppercase tracking-widest mb-1">Disk Usage</p>
            <p class="text-2xl font-semibold text-text font-mono">58<span class="text-sm text-muted">%</span></p>
            <p class="text-xs text-live font-mono mt-1">↓ normal</p>
        </div>

        {{-- Uptime --}}
        <div class="bg-panel border border-border rounded-lg px-4 py-3">
            <p class="text-xs text-muted font-mono uppercase tracking-widest mb-1">Uptime</p>
            <p class="text-2xl font-semibold text-text font-mono">14<span class="text-sm text-muted">d</span></p>
            <p class="text-xs text-live font-mono mt-1">● online</p>
        </div>

    </div>

    {{-- Chart panel --}}
    <div class="w-full rounded-lg bg-black border border-border relative" style="min-height: 420px;">

        {{-- Grid lines --}}
        <div class="absolute inset-0 flex flex-col justify-between px-6 py-4 pointer-events-none">
            @foreach(range(1, 5) as $i)
                <div class="w-full border-t border-white/5"></div>
            @endforeach
        </div>

        {{-- Placeholder text --}}
        <div class="flex items-center justify-center h-full min-h-[420px]">
            <p class="text-xs font-mono text-muted/30 tracking-widest select-none">
                — chart coming soon —
            </p>
        </div>

    </div>

</x-app-layout>