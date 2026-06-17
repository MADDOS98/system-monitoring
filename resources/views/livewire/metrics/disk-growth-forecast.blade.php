@php
    // Helper local pentru formatarea bytes-urilor.
    $fmtBytes = function (int $b): string {
        if ($b >= 1099511627776) return number_format($b / 1099511627776, 1) . ' TB';
        if ($b >= 1073741824)    return number_format($b / 1073741824, 1)    . ' GB';
        if ($b >= 1048576)       return number_format($b / 1048576, 1)       . ' MB';
        if ($b >= 1024)          return number_format($b / 1024, 1)          . ' KB';
        return $b . ' B';
    };
    // Helper care intoarce valoarea + unitatea separate (pentru styling subscript).
    $splitBytes = function (int $b): array {
        if ($b >= 1099511627776) return [number_format($b / 1099511627776, 1), 'TB'];
        if ($b >= 1073741824)    return [number_format($b / 1073741824, 1), 'GB'];
        if ($b >= 1048576)       return [number_format($b / 1048576, 1), 'MB'];
        if ($b >= 1024)          return [number_format($b / 1024, 1), 'KB'];
        return [$b, 'B'];
    };

    [$avgVal, $avgUnit] = $splitBytes($avg_per_day);
    [$maxVal, $maxUnit] = $splitBytes($max_per_day);
@endphp

<div wire:key="disk-growth-forecast"
     data-growths='@json($growths)'
     data-levels='@json($levels)'
     data-labels='@json($labels)'
     data-max-value="{{ $max_value }}"
     class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3 mt-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-xs font-mono font-semibold text-neutral-200 flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            Disk growth forecast
        </p>
        <span class="text-[11px] font-mono text-neutral-500">based on last 30 days</span>
    </div>

    {{-- 3 stat cards --}}
    <div class="grid grid-cols-3 gap-4 mb-5">

        <div class="border-r border-neutral-800 pr-4">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-1">AVG / DAY</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-stat-avg>{{ $avgVal }}</span>
                <span class="text-sm font-normal text-neutral-400">{{ $avgUnit }}</span>
            </p>
        </div>

        <div class="border-r border-neutral-800 pr-4">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-1">MAX / DAY</p>
            <p class="text-2xl font-semibold text-amber-400">
                <span data-stat-max>{{ $maxVal }}</span>
                <span class="text-sm font-normal text-neutral-400">{{ $maxUnit }}</span>
            </p>
        </div>

        <div>
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-1">EST. DAYS LEFT</p>
            <p class="text-2xl font-semibold text-emerald-400">
                @if($days_left !== null)
                    <span data-stat-days>{{ $days_left }}</span>
                    <span class="text-sm font-normal text-neutral-400">days</span>
                @else
                    <span class="text-neutral-500">&mdash;</span>
                @endif
            </p>
            @if($days_left_worst !== null)
                <p class="text-[10px] font-mono text-neutral-500 mt-0.5">
                    worst case: <span data-stat-days-worst>{{ $days_left_worst }}</span>d
                </p>
            @endif
        </div>

    </div>

    {{-- Chart subheader --}}
    <p class="text-[11px] font-mono text-neutral-500 mb-2">daily growth — last 30 days</p>

    {{-- Bars container --}}
    <div data-bars-container class="flex items-end gap-[3px]" style="height: 90px">
        @for ($i = 0; $i < 30; $i++)
            @php
                $growth   = $growths[$i];
                $pct      = $max_value > 0 ? ($growth / $max_value) : 0;
                $heightPx = max(3, (int) round($pct * 78));
                $lvl      = $levels[$i];
                $barColor = match($lvl) {
                    'elevated' => 'bg-amber-500 group-hover:bg-amber-400',
                    'spike'    => 'bg-rose-500 group-hover:bg-rose-400',
                    default    => 'bg-sky-500 group-hover:bg-sky-400',
                };
            @endphp

            <div data-bar-cell data-day="{{ $i }}"
                 class="relative flex-1 flex flex-col justify-end group cursor-pointer"
                 style="height: 78px">

                {{-- Tooltip --}}
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                            pointer-events-none opacity-0 group-hover:opacity-100
                            transition-opacity duration-150 whitespace-nowrap">
                    <div class="bg-[#1a1a1a] border border-[#3a3a3a] rounded px-2 py-1 text-[11px] font-mono text-neutral-200">
                        <span data-tooltip-label>{{ $labels[$i] }}</span> &middot;
                        <span data-tooltip-value>{{ $fmtBytes($growth) }}</span>
                    </div>
                    <div class="w-2 h-2 bg-[#1a1a1a] border-r border-b border-[#3a3a3a] rotate-45 mx-auto -mt-1"></div>
                </div>

                {{-- Bar --}}
                <div data-bar data-level="{{ $lvl }}"
                     class="w-full rounded-sm {{ $barColor }} transition-all duration-200"
                     style="height: {{ $heightPx }}px"></div>
            </div>
        @endfor
    </div>

    {{-- X-axis endpoint labels + legend --}}
    <div class="relative mt-1.5">
        <div class="flex justify-between text-[11px] font-mono text-neutral-500">
            <span>-30d</span>
            <span>today</span>
        </div>
        <div class="absolute left-1/2 top-0 -translate-x-1/2 flex items-center gap-4 text-[11px] font-mono">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-sm bg-sky-500"></span>
                <span class="text-neutral-400">normal</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-sm bg-amber-500"></span>
                <span class="text-neutral-400">elevated</span>
            </span>
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-sm bg-rose-500"></span>
                <span class="text-neutral-400">spike</span>
            </span>
        </div>
    </div>

    {{-- Footer summary --}}
    <p class="text-[11px] font-mono text-neutral-500 mt-4 pt-3 border-t border-neutral-800">
        <span data-foot-free>{{ $fmtBytes($free_bytes) }}</span> free &middot;
        avg <span data-foot-avg>{{ $fmtBytes($avg_per_day) }}</span>/day &rarr;
        @if($days_left !== null)
            full in ~<span data-foot-days>{{ $days_left }}</span> days
        @else
            growth not measurable yet
        @endif
        @if($days_left_worst !== null)
            &middot; worst case (max) &rarr; <span data-foot-days-worst>{{ $days_left_worst }}</span> days
        @endif
    </p>

</div>
