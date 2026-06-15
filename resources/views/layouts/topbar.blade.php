<header class="flex items-center gap-9 p-7 h-12 border-b border-border bg-sidebar sticky top-0 z-10">

    {{-- Breadcrumb: derivat din ruta curenta prin view composer (App\Support\Breadcrumb). --}}
    <div class="flex items-center gap-1 text-sm text-muted font-mono">

        {{-- Sectiunea sidebar (non-clickable: sectiunile nu au landing dedicata). --}}
        <span>{{ $breadcrumbData['section'] ?? 'Workspace' }}</span>

        @foreach($breadcrumbData['crumbs'] ?? [] as $crumb)
        <span class="mx-1">/</span>
        @if(! empty($crumb['url']))
        <a href="{{ $crumb['url'] }}" wire:navigate
            class="hover:text-text transition-colors duration-150">
            {{ $crumb['label'] }}
        </a>
        @else
        <span class="{{ $loop->last ? 'text-text font-medium' : '' }}">
            {{ $crumb['label'] }}
        </span>
        @endif
        @endforeach

    </div>

    {{-- Right side --}}
    <div class="flex items-center gap-3 ml-auto">

        <div class="px-4 py-2">
            <p class="text-xs font-mono text-text font-medium truncate">{{ Auth::user()->name }}</p>
            <p class="text-xs font-mono text-muted truncate">{{ Auth::user()->email }}</p>
        </div>

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