<div class="space-y-3">

    {{-- Header with Manage button --}}
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Percentiles</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                the value below which N% of samples fall &middot; computed per rolling window
            </p>
        </div>
        <button type="button"
                onclick="Livewire.dispatch('open-percentiles-manager')"
                class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            Manage Percentiles
        </button>
    </div>

    {{-- Accordion-uri per metric --}}
    @foreach($metricLabels as $metric => $label)
        @php
            $percentiles = $percentilesByMetric->get($metric, collect());
            $isOpen      = in_array($metric, $openMetrics, true);
            $activeCount = $percentiles->where('is_active', true)->count();
            $totalCount  = $percentiles->count();
        @endphp

        <div class="rounded-lg border border-border bg-panel overflow-hidden">
            {{-- Header (toggle) --}}
            <button type="button" wire:click="toggle('{{ $metric }}')"
                    class="w-full flex items-center justify-between px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-mono font-semibold text-text">{{ $label }}</span>
                    <span class="text-[11px] font-mono text-muted">
                        {{ $activeCount }} / {{ $totalCount }} active
                    </span>
                </div>
                <svg class="w-4 h-4 text-muted transition-transform duration-150 {{ $isOpen ? 'rotate-180' : '' }}"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Body: cardurile randate doar daca accordion-ul e deschis (lazy server-side) --}}
            @if($isOpen)
                <div class="px-4 pb-4 pt-1 border-t border-border bg-[#0d0d0d]">
                    @if($percentiles->isEmpty())
                        <div class="py-6 text-center">
                            <p class="text-sm font-mono text-muted">No percentiles defined for {{ $label }}.</p>
                            <p class="text-[11px] font-mono text-neutral-600 mt-1">
                                Click "Manage Percentiles" to add some.
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-3">
                            @foreach($percentiles as $p)
                                <livewire:percentile-card :percentile="$p"
                                                          :key="'percentile-card-' . $p->id" />
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>
