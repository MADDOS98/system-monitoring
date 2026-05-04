<aside class="w-52 flex-shrink-0 bg-sidebar border-r border-border flex flex-col justify-between py-4 fixed top-0 left-0 h-full z-20">

    <div>
        {{-- Logo --}}
        <div class="px-4 mb-6">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                <x-application-logo class="w-8 h-8" />
                <h1 class="text-sm">system-monitoring</h1>
            </a>
        </div>

        {{-- WORKSPACE --}}
        <div class="px-4 mb-1">
            <p class="text-xs font-semibold text-muted uppercase tracking-widest mb-1">Workspace</p>
        </div>
        <nav class="space-y-0.5 px-2">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('dashboard') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
                Dashboard
            </a>
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('metrics*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                Metrics
            </a>
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('processes*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <rect x="2" y="6" width="20" height="12" rx="2"/><path d="M6 12h4M14 12h4"/>
                </svg>
                Processes
            </a>
        </nav>

        {{-- OBSERVABILITY --}}
        <div class="px-4 mt-5 mb-1">
            <p class="text-xs font-semibold text-muted uppercase tracking-widest mb-1">Observability</p>
        </div>
        <nav class="space-y-0.5 px-2">
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('logs*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h5M8 16h3"/>
                </svg>
                Apache Logs
            </a>
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('network*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"/>
                </svg>
                Network
            </a>
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('storage*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <rect x="2" y="3" width="20" height="18" rx="2"/><path d="M8 10h8M8 14h5"/>
                </svg>
                Storage
            </a>
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('alerts*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                Alerts
                {{-- Badge dinamic - va fi înlocuit cu date reale --}}
                <span class="ml-auto bg-danger text-white text-[10px] font-semibold min-w-[18px] h-[18px] rounded-full flex items-center justify-center px-1">
                    4
                </span>
            </a>
        </nav>
    </div>

    {{-- SYSTEM + status --}}
    <div>
        <div class="px-4 mb-1">
            <p class="text-xs font-semibold text-muted uppercase tracking-widest mb-1">System</p>
        </div>
        <nav class="px-2">
            <a href="#"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors duration-150
                      {{ request()->routeIs('settings*') ? 'bg-panel text-text' : 'text-label hover:bg-panel hover:text-text' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                </svg>
                Settings
            </a>
        </nav>

        {{-- Live status --}}
        <div class="px-4 mt-4 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-live animate-pulse flex-shrink-0"></span>
            <span class="text-xs font-mono text-live">all systems operational</span>
        </div>
    </div>

</aside>