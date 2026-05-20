<div wire:key="peak-traffic-timeline"
     class="w-full rounded-lg border border-[#2a2a2a] mb-5 px-5 pt-4 pb-3">

    {{-- Header --}}
    <div class="mb-4">
        <p class="text-xs font-mono font-semibold text-[#e5e7eb]">Peak traffic timeline</p>
        <p class="text-[11px] font-mono text-[#6b7280] mt-0.5">
            24 bins &middot;
            {{ $day }} &middot;
            @if($selected !== null)
                <span class="text-[#93c5fd]">
                    {{ str_pad($selected, 2, '0', STR_PAD_LEFT) }}:00
                    &mdash;
                    {{ number_format($bins[$selected]) }} requests
                </span>
            @else
                click to inspect
            @endif
        </p>
    </div>

    {{-- Bars --}}
    <div class="flex items-end gap-[3px]" style="height: 120px">
        @for ($h = 0; $h < 24; $h++)
            @php
                $count      = $bins[$h];
                $pct        = $max > 0 ? ($count / $max) : 0;
                $heightPx   = max(3, (int) round($pct * 108));
                $isSelected = $selected === $h;
                $barLevel   = $levels[$h];
                $barClass   = $isSelected
                    ? match($barLevel) {
                        'warning'  => 'bg-orange-400',
                        'critical' => 'bg-red-500',
                        default    => 'bg-blue-500',
                    }
                    : match($barLevel) {
                        'warning'  => 'bg-orange-600 group-hover:bg-orange-500',
                        'critical' => 'bg-red-700 group-hover:bg-red-600',
                        default    => 'bg-blue-700 group-hover:bg-blue-600',
                    };
            @endphp

            <div
                wire:click="toggleHour({{ $h }})"
                class="relative flex-1 flex flex-col justify-end group cursor-pointer"
                style="height: 108px"
            >
                {{-- Tooltip --}}
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                            pointer-events-none opacity-0 group-hover:opacity-100
                            transition-opacity duration-150 whitespace-nowrap">
                    <div class="bg-[#1a1a1a] border border-[#3a3a3a] rounded px-2 py-1 text-[11px] font-mono text-[#e5e7eb]">
                        {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00 — {{ number_format($count) }} req
                    </div>
                    <div class="w-2 h-2 bg-[#1a1a1a] border-r border-b border-[#3a3a3a] rotate-45 mx-auto -mt-1"></div>
                </div>

                {{-- Bar --}}
                <div
                    style="height: {{ $heightPx }}px"
                    class="w-full rounded-sm transition-all duration-200 {{ $barClass }}"
                ></div>
            </div>
        @endfor
    </div>

    {{-- Hour labels --}}
    <div class="flex gap-[3px] mt-1.5">
        @for ($h = 0; $h < 24; $h++)
            <div class="flex-1 text-center text-[11px] font-mono text-gray-400">
                {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:00
            </div>
        @endfor
    </div>

</div>
