<header class="flex items-center gap-9 p-7 h-12 border-b border-border bg-sidebar sticky top-0 z-10">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-1 text-sm text-muted font-mono">
        <span class="hover:text-text cursor-pointer transition-colors duration-150">Workspace</span>
        <span class="mx-1">/</span>
        <span class="text-text font-medium">{{ $breadcrumb ?? 'Dashboard' }}</span>
    </div>

    {{-- Right side --}}
    <div class="flex items-center gap-3">

        {{-- Search --}}
        <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5 w-80">
            <svg class="w-3.5 h-3.5 text-muted flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <span class="text-xs text-muted flex-1 font-mono">Search servers, processes, alerts...</span>
            <kbd class="text-xs text-muted bg-border rounded px-1 font-mono">⌘k</kbd>
        </div>

        {{-- Server selector --}}
        <x-dropdown align="left" width="60">
            <x-slot name="trigger">
                <button class="flex bg-panel py-1 px-2 border border-border rounded-md items-center gap-2 hover:opacity-80 transition-opacity duration-150">
                    {{-- Live dot --}}
                    <span class="w-2 h-2 rounded-full bg-live animate-pulse flex-shrink-0"></span>
                    {{-- Server name --}}
                    <span class="text-sm font-semibold text-text font-mono">web-prod-01</span>
                    {{-- Region tag --}}
                    <span class="text-xs text-muted font-mono">· us-east-1</span>
                    {{-- Chevron --}}
                    <svg class="w-3 h-3 text-muted flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </x-slot>
 
            <x-slot name="content">
                <div class="px-4 py-2 border-b border-border">
                    <p class="text-xs font-mono text-muted uppercase tracking-widest">Servers</p>
                </div>
                <x-dropdown-link href="#">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-live flex-shrink-0"></span>
                        web-prod-01 <span class="text-muted ml-auto text-xs">us-east-1</span>
                    </span>
                </x-dropdown-link>
                <x-dropdown-link href="#">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-muted flex-shrink-0"></span>
                        web-prod-02 <span class="text-muted ml-auto text-xs">eu-west-1</span>
                    </span>
                </x-dropdown-link>
                <x-dropdown-link href="#">
                    <span class="flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-muted flex-shrink-0"></span>
                        db-prod-01 <span class="text-muted ml-auto text-xs">us-east-1</span>
                    </span>
                </x-dropdown-link>
            </x-slot>
        </x-dropdown>

        {{-- LIVE badge --}}
        <div class="flex flex-col items-center border border-border rounded-md px-3 py-1 bg-panel">
            <span class="w-2 h-2 rounded-full bg-live animate-pulse mb-0.5"></span>
            <span class="text-[10px] font-bold text-live tracking-widest font-mono">LIVE</span>
        </div>

        {{-- Notifications --}}
        <button class="relative p-1.5 rounded-md hover:bg-panel transition-colors duration-150">
            <svg class="w-4 h-4 text-label" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="absolute top-0.5 right-0.5 w-2 h-2 bg-danger rounded-full border border-sidebar"></span>
        </button>

        {{-- User dropdown --}}
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 hover:opacity-80 transition-opacity duration-150">
                    {{-- Avatar cu initialele userului --}}
                    <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-white text-xs font-bold font-mono">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->name, strrpos(Auth::user()->name, ' ') + 1, 1)) }}
                    </div>
                </button>
            </x-slot>

            <x-slot name="content">
                {{-- User info --}}
                <div class="px-4 py-2 border-b border-border">
                    <p class="text-xs font-mono text-text font-medium truncate">{{ Auth::user()->name }}</p>
                    <p class="text-xs font-mono text-muted truncate">{{ Auth::user()->email }}</p>
                </div>

                {{-- Links --}}
                <x-dropdown-link :href="route('profile.edit')">
                    Profile
                </x-dropdown-link>

                {{-- Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Log Out
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>

    </div>
</header>