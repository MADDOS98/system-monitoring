<div class="mt-6 rounded-lg border border-border px-5 py-4">

    {{-- Header --}}
    <div class="flex items-start justify-between mb-4">
        <div>
            <p class="text-xs font-mono font-semibold text-text flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-muted" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path d="M3 3v18h18"/>
                    <path d="M7 14l3-3 4 4 5-5"/>
                </svg>
                {{ $tabLabel }} percentiles
            </p>
            <p class="text-[11px] font-mono text-muted mt-0.5">
                the value below which N% of samples fall &middot; computed per rolling window
            </p>
        </div>
        <span class="flex items-center gap-1.5 text-[11px] font-mono text-muted whitespace-nowrap">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            updated every 60s
        </span>
    </div>

    @if($percentiles->isEmpty())
        {{-- Empty state --}}
        <div class="py-6 text-center">
            <p class="text-sm font-mono text-muted">No active percentiles for {{ $tabLabel }}.</p>
            <p class="text-[11px] font-mono text-neutral-600 mt-1">
                <a href="{{ route('percentiles') }}" wire:navigate class="text-amber-400 hover:text-amber-300 transition-colors">
                    Open percentiles management &rarr;
                </a>
            </p>
        </div>
    @else
        {{-- Grid of cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($percentiles as $p)
                <livewire:percentile-card :percentile="$p"
                                          :key="'tab-percentile-card-' . $p->id" />
            @endforeach
        </div>
    @endif
</div>
