<x-app-layout>

    {{-- Header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Apache Logs</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                Access log for <span class="font-semibold text-text">web-prod-01</span>
                · {{ $logs ? count($logs) : 0 }} entries
            </p>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-2">
            <select class="bg-panel border border-border text-label text-xs font-mono rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent transition duration-150">
                <option value="">All statuses</option>
                <option value="200">200 OK</option>
                <option value="301">301 Redirect</option>
                <option value="404">404 Not Found</option>
                <option value="500">500 Error</option>
            </select>
            <select class="bg-panel border border-border text-label text-xs font-mono rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent transition duration-150">
                <option value="">All methods</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>
            <button class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- ================================================================
         ACCORDION — starea e salvată în localStorage ca să persiste
         ================================================================ --}}
    <div
        x-data="{
            open: localStorage.getItem('logs-accordion') === 'true',
            toggle() {
                this.open = !this.open;
                localStorage.setItem('logs-accordion', this.open);
            }
        }"
        class="mb-5 border border-border rounded-lg overflow-hidden"
    >
        {{-- Accordion trigger --}}
        <button
            @click="toggle()"
            class="w-full flex items-center justify-between px-4 py-3 bg-sidebar hover:bg-panel transition-colors duration-150"
        >
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h7"/>
                </svg>
                <span class="text-xs font-mono font-semibold text-label uppercase tracking-widest">Quick Filters</span>
            </div>
            <svg
                class="w-4 h-4 text-muted transition-transform duration-200"
                :class="open ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
            >
                <path d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Accordion content --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="border-t border-border bg-panel px-4 py-4"
        >
            <div class="flex flex-wrap gap-2">

                {{-- TOP 15 IP ACCESSORS --}}
                <a
                    href="{{ route('apache-logs.top-ips') }}"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-xs font-mono font-medium transition-colors duration-150
                        {{ request()->routeIs('apache-logs.top-ips')
                            ? 'bg-accent/10 border-accent text-accent'
                            : 'bg-sidebar border-border text-label hover:border-accent hover:text-accent' }}"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"/>
                    </svg>
                    Top 15 IP Accessors
                </a>

                {{-- Placeholder pentru filtre viitoare --}}
                {{-- <a href="{{ route('apache-logs.example') }}" class="...">Example Filter</a> --}}

            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="w-full rounded-lg border border-border overflow-hidden">
        <div class="overflow-x-auto">
            @include($tableView)
        </div>

        {{-- Pagination --}}
        @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
            <div class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between">
                <p class="text-xs text-muted font-mono">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
                </p>
                <div class="text-xs font-mono">
                    {{ $logs->links() }}
                </div>
            </div>
        @endif

    </div>

</x-app-layout>