<div wire:poll.60s
     class="rounded-lg border border-border bg-panel px-5 py-4">

    @php
        $p          = $percentile;
        $pInt       = (int) $p->percentile;          // 95 din 95.00
        $window     = $p->window_minutes;
        $isPercent  = in_array($p->metric, ['cpu', 'ram'], true);

        // Axa slider-ului: 0-100 pentru metrice procentuale, 0-(max*1.2) altfel.
        if ($data !== null) {
            $axisMax = $isPercent
                ? 100.0
                : max(1.0, ($data['max'] ?? 0) * 1.2);
            $valuePos  = min(100, ($data['value'] / $axisMax) * 100);
            $medianPos = $median !== null
                ? min(100, ($median   / $axisMax) * 100)
                : null;
        }
    @endphp

    {{-- Header: window label + percentile badge --}}
    <div class="flex items-center justify-between mb-2">
        <span class="text-[10px] font-mono uppercase tracking-widest text-muted">
            {{ $window }} min window
        </span>
        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold
                     bg-amber-950/40 text-amber-300 border border-amber-900/60">
            P{{ $pInt }} &middot; {{ $pInt }}%
        </span>
    </div>

    @if($data === null)
        {{-- Empty state: no samples in window --}}
        <div class="flex flex-col items-center justify-center py-8 gap-1">
            <span class="text-2xl font-mono text-muted">—</span>
            <span class="text-[11px] font-mono text-neutral-600">no samples in window</span>
        </div>
    @else
        {{-- Big value --}}
        <div class="flex items-baseline gap-1 mb-3">
            <span class="text-4xl font-mono font-light text-amber-300">{{ number_format($data['value'], 1) }}</span>
            <span class="text-sm font-mono text-amber-700">{{ $unit }}</span>
        </div>

        {{-- Slider --}}
        <div class="mb-3">
            {{-- Axis labels --}}
            <div class="flex justify-between text-[10px] font-mono text-muted mb-1">
                <span>0</span>
                <span>{{ $isPercent ? '50' : number_format($axisMax / 2, 1) }}</span>
                <span>{{ $isPercent ? '100' . $unit : number_format($axisMax, 1) . ' ' . $unit }}</span>
            </div>

            {{-- Track --}}
            <div class="relative h-1.5 bg-amber-950/50 rounded-full">
                {{-- Median tick --}}
                @if($medianPos !== null)
                    <div class="absolute top-1/2 -translate-y-1/2 w-px h-3 bg-amber-700/70"
                         style="left: {{ $medianPos }}%"></div>
                @endif

                {{-- Percentile tick (main) --}}
                <div class="absolute top-1/2 -translate-y-1/2 w-0.5 h-3.5 bg-amber-400"
                     style="left: {{ $valuePos }}%"></div>
            </div>

            {{-- Tick labels --}}
            <div class="relative h-4 mt-1">
                @if($median !== null && $medianPos !== null)
                    <span class="absolute text-[10px] font-mono text-muted -translate-x-1/2 whitespace-nowrap"
                          style="left: {{ $medianPos }}%">
                        med {{ number_format($median, 0) }}
                    </span>
                @endif
                <span class="absolute text-[10px] font-mono px-1.5 py-px rounded
                             bg-amber-900/60 text-amber-200 -translate-x-1/2 whitespace-nowrap"
                      style="left: {{ $valuePos }}%">
                    P{{ $pInt }} {{ number_format($data['value'], 0) }}
                </span>
            </div>
        </div>

        {{-- Footer: min / avg / max / samples --}}
        <div class="flex items-center justify-between text-[11px] font-mono pt-2 border-t border-border/50">
            <div class="flex items-center gap-4">
                <span class="text-muted">min <span class="text-amber-300">{{ number_format($data['min'], 1) }}{{ $unit }}</span></span>
                <span class="text-muted">avg <span class="text-amber-300">{{ number_format($data['avg'], 1) }}{{ $unit }}</span></span>
                <span class="text-muted">max <span class="text-amber-300">{{ number_format($data['max'], 1) }}{{ $unit }}</span></span>
            </div>
            <span class="text-muted">{{ number_format($data['sample_count']) }} samples</span>
        </div>
    @endif
</div>
