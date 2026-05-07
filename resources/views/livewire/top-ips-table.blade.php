<div wire:key="top-ips-table"
    class="rounded-lg border border-border overflow-hidden flex flex-col"
    style="height: calc(12 * 53px)">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0">
        <div class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
            <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Top source IPs</span>
        </div>
        <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
            @foreach(['All', 'Whitelisted', 'Suspicious'] as $tab)
            <button
                wire:click="setTab('{{ $tab }}')"
                class="px-3 py-1 text-xs font-mono transition-colors duration-150
                        {{ $this->tab === $tab ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]' }}
                        border-l border-[#2a2a2a] first:border-l-0">
                {{ $tab }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Column headers --}}
    <div class="grid grid-cols-12 px-4 py-2 bg-sidebar border-b border-border flex-shrink-0">
        <div class="col-span-4 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">IP / Host</div>
        <div class="col-span-2 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">Reqs</div>
        <div class="col-span-2 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">Status mix</div>
        <div class="col-span-2 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-right">BW</div>
        <div class="col-span-1 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-right">Last seen</div>
        <div class="col-span-1"></div>
    </div>

    {{-- Rows --}}
    <div class="overflow-y-auto flex-1 divide-y divide-[#2a2a2a]">
        @forelse ($topIps as $ip)
        @php
        $selected = in_array($ip->ip, $this->selectedIps);
        $showTag = in_array($ip->tag ?? '', ['TRUSTED', 'SUSPECT']);
        $tagColor = match($ip->tag ?? '') {
        'TRUSTED' => 'bg-green-900 text-green-400',
        'SUSPECT' => 'bg-red-900 text-red-400',
        default => '',
        };
        $s2 = $ip->s2xx; $s3 = $ip->s3xx; $s4 = $ip->s4xx; $s5 = $ip->s5xx;
        $p2 = $s2; $p3 = $p2 + $s3; $p4 = $p3 + $s4; $p5 = $p4 + $s5;
        $gradient = "linear-gradient(to right, #22c55e 0% {$p2}%, #3b82f6 {$p2}% {$p3}%, #eab308 {$p3}% {$p4}%, #ef4444 {$p4}% {$p5}%)";
        $rowBg = $selected
        ? match($ip->tag ?? '') {
        'TRUSTED' => 'bg-green-500/15',
        'SUSPECT' => 'bg-red-500/15',
        default => 'bg-[#111111]',
        }
        : match($ip->tag ?? '') {
        'TRUSTED' => 'bg-green-500/5',
        'SUSPECT' => 'bg-red-500/5',
        default => 'bg-[#111111]',
        };
        @endphp
        <div class="relative grid grid-cols-12 px-4 py-2.5 {{ $rowBg }} transition-colors duration-100 items-center group">
            {{-- Overlay transparent care intensifica bg-ul existent la hover --}}
            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-[0.04] transition-opacity duration-100 pointer-events-none"></div>

            {{-- IP + tag + hostname --}}
            <div class="col-span-4">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[#e5e7eb] text-xs font-mono">{{ $ip->ip }}</span>
                    @if($showTag)
                    <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded {{ $tagColor }}">{{ $ip->tag }}</span>
                    @endif
                </div>
                @if($showTag && !empty($ip->hostname))
                <div class="text-[10px] text-[#6b7280] font-mono mt-0.5 truncate">{{ $ip->hostname }}</div>
                @endif
            </div>

            {{-- Reqs --}}
            <div class="col-span-2 text-xs font-mono text-[#e5e7eb]">
                {{ number_format($ip->reqs) }}
            </div>

            {{-- Status mix gradient bar --}}
            <div class="col-span-2 pr-2">
                <div class="w-full rounded-full h-1.5" style="background: {{ $gradient }}"></div>
            </div>

            {{-- BW --}}
            <div class="col-span-2 text-xs font-mono text-[#9ca3af] text-right">
                @php
                $bytes = $ip->total_bytes ?? 0;
                if ($bytes >= 1073741824) echo round($bytes / 1073741824, 1) . ' GB';
                elseif ($bytes >= 1048576) echo round($bytes / 1048576, 1) . ' MB';
                elseif ($bytes >= 1024) echo round($bytes / 1024, 1) . ' KB';
                else echo $bytes . ' B';
                @endphp
            </div>

            {{-- Last seen --}}
            <div class="col-span-1 text-[10px] font-mono text-[#6b7280] text-right">
                {{ $ip->last_seen ?? '—' }}
            </div>

            {{-- Select button --}}
            <div class="col-span-1 flex justify-end">
                <button
                    wire:click="toggleIp('{{ $ip->ip }}')"
                    class="w-6 h-6 rounded border flex items-center justify-center transition-colors duration-150
                            {{ $selected
                                ? 'border-accent bg-accent/20 text-accent'
                                : 'border-[#3a3a3a] bg-[#1a1a1a] text-[#6b7280] hover:border-accent hover:text-accent' }}">
                    @if($selected)
                    {{-- Checkmark --}}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    @else
                    {{-- Plus --}}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    @endif
                </button>
            </div>

        </div>
        @empty
        <div class="flex items-center justify-center h-full text-[#6b7280] text-xs font-mono">
            No data available.
        </div>
        @endforelse
    </div>

</div>