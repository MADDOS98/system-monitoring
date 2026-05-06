<x-app-layout>

    {{-- Header --}}
    <div
        x-data="{
            preset: '5m',
            from: '',
            to: '',
            init() { this.applyPreset(this.preset); },
            applyPreset(p) {
                this.preset = p;
                const now = new Date();
                const offsets = { '5m': 5, '1h': 60, '24h': 1440 };
                if (offsets[p]) {
                    const past = new Date(now - offsets[p] * 60000);
                    this.from = this.fmt(past);
                    this.to   = this.fmt(now);
                }
            },
            setNow() {
                const now = new Date();
                const offsets = { '5m': 5, '1h': 60, '24h': 1440 };
                const past = new Date(now - (offsets[this.preset] ?? 5) * 60000);
                this.from = this.fmt(past);
                this.to   = this.fmt(now);
            },
            fmt(d) {
                const pad = n => String(n).padStart(2, '0');
                return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())
                    + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            },
            fmtDisplay(str) {
                if (!str) return '—';
                return new Date(str).toLocaleString();
            }
        }"
        class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Apache Traffic</h1>
            <p class="text-xs text-muted font-mono mt-1">
                Window:
                <span class="text-label" x-text="fmtDisplay(from)"></span>
                <span class="mx-1">—</span>
                <span class="text-label" x-text="fmtDisplay(to)"></span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5">
                <span class="text-xs text-muted font-mono">From</span>
                <input type="datetime-local" x-model="from" @change="preset = 'custom'"
                    class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
            </div>
            <div x-show="preset === 'custom'" x-transition class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5" style="display:none">
                <span class="text-xs text-muted font-mono">To</span>
                <input type="datetime-local" x-model="to"
                    class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
            </div>
            <button @click="setNow()"
                class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150">
                Now
            </button>
            <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                <template x-for="p in ['5m', '1h', '24h', 'custom']" :key="p">
                    <button
                        @click="p !== 'custom' ? applyPreset(p) : (preset = p)"
                        :class="preset === p ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]'"
                        class="px-3 py-1.5 text-xs font-mono transition-colors duration-150 border-x border-[#2a2a2a]"
                        x-text="p"></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Table --}}

        <livewire:apache-logs-table />

        {{-- ── Bottom two tables ── --}}
        @php
        $tableHeight = 'calc(12 * 53px)';
        $topIps = $topIps ?? collect();
        $byStatus = $byStatus ?? collect();
        $totalReqs = $topIps->sum('reqs') ?: 1;
        $maxReqs = $topIps->max('reqs') ?: 1;
        $totalStat = $byStatus->sum('total') ?: 1;
        @endphp

        <div class="mt-5 grid grid-cols-2 gap-4 items-start">

            {{-- ── LEFT: Top source IPs ── --}}
            <div class="rounded-lg border border-border overflow-hidden flex flex-col" style="height: {{ $tableHeight }}">

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M5 12h14M12 5l7 7-7 7" />
                        </svg>
                        <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Top source IPs</span>
                    </div>
                    <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                        @foreach(['All', 'Whitelisted', 'Suspicious'] as $i => $tab)
                        <button class="px-3 py-1 text-xs font-mono transition-colors duration-150
                            {{ $i === 0 ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]' }}
                            {{ $i > 0 ? 'border-l border-[#2a2a2a]' : '' }}">
                            {{ $tab }}
                        </button>
                        @endforeach
                    </div>
                </div>

                {{-- Column headers --}}
                <div class="grid grid-cols-12 px-4 py-2 bg-sidebar border-b border-border flex-shrink-0">
                    <div class="col-span-4 text-[12px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">IP / Host</div>
                    <div class="col-span-2 text-[12px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">Reqs</div>
                    <div class="col-span-2 text-[12px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-right">Status mix</div>
                    <div class="col-span-2 text-[12px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-right">BW</div>
                    <div class="col-span-2 text-[12px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-right">Last seen</div>
                </div>

                {{-- Rows --}}
                <div class="overflow-y-auto flex-1 divide-y divide-[#2a2a2a]">
                    @forelse ($topIps as $ip)
                    @php
                    $showTag = in_array($ip->tag ?? '', ['TRUSTED', 'TOR EXIT', 'SCANNER', 'SCRAPER']);
                    $tagColor = match($ip->tag ?? '') {
                    'TRUSTED' => 'bg-green-900 text-green-400',
                    'TOR EXIT' => 'bg-red-900 text-red-400',
                    'SCANNER' => 'bg-orange-900 text-orange-400',
                    'SCRAPER' => 'bg-yellow-900 text-yellow-400',
                    default => '',
                    };
                    @endphp
                    <div class="grid grid-cols-12 px-4 py-2.5 bg-[#111111] hover:bg-[#161616] transition-colors duration-100 items-center">

                        {{-- IP + hostname (numai daca are tag relevant) --}}
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

                        {{-- Status mix progress bar — linear-gradient continuu --}}
                        <div class="col-span-2 px-2">
                            @php
                            $s2 = $ip->s2xx; $s3 = $ip->s3xx; $s4 = $ip->s4xx; $s5 = $ip->s5xx;
                            $p2 = $s2; $p3 = $p2 + $s3; $p4 = $p3 + $s4; $p5 = $p4 + $s5;
                            $gradient = "linear-gradient(to right, #22c55e 0% {$p2}%, #3b82f6 {$p2}% {$p3}%, #eab308 {$p3}% {$p4}%, #ef4444 {$p4}% {$p5}%)";
                            @endphp
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
                        <div class="col-span-2 text-[10px] font-mono text-[#6b7280] text-right">
                            {{ $ip->last_seen ?? '—' }}
                        </div>

                    </div>
                    @empty
                    <div class="flex items-center justify-center h-full text-[#6b7280] text-xs font-mono">
                        No data available.
                    </div>
                    @endforelse
                </div>

            </div>

            {{-- ── RIGHT: Requests by status ── --}}
            <div class="rounded-lg border border-border overflow-hidden flex flex-col" style="height: {{ $tableHeight }}">

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0">
                    <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Requests by status</span>
                    <span class="text-xs font-mono text-[#6b7280]">{{ number_format($byStatus->sum('total')) }} total</span>
                </div>

                {{-- Top progress bar --}}
                <div class="px-4 pt-3 pb-1 bg-[#111111] flex-shrink-0">
                    @php
                    $p2 = round($byStatus->where('group', '2xx')->sum('total') / $totalStat * 100, 1);
                    $p3 = round($byStatus->where('group', '3xx')->sum('total') / $totalStat * 100, 1);
                    $p4 = round($byStatus->where('group', '4xx')->sum('total') / $totalStat * 100, 1);
                    $p5 = round($byStatus->where('group', '5xx')->sum('total') / $totalStat * 100, 1);
                    $e2 = $p2; $e3 = $e2 + $p3; $e4 = $e3 + $p4; $e5 = $e4 + $p5;
                    $topGradient = "linear-gradient(to right, #22c55e 0% {$e2}%, #3b82f6 {$e2}% {$e3}%, #eab308 {$e3}% {$e4}%, #ef4444 {$e4}% {$e5}%)";
                    @endphp
                    <div class="w-full rounded-full h-2" style="background: {{ $topGradient }}"></div>
                </div>

                {{-- Status rows --}}
                <div class="overflow-y-auto flex-1 divide-y divide-[#2a2a2a]">
                    @php
                    $statusGroups = [
                    ['group' => '2xx', 'label' => 'Success', 'sub' => 'OK / Created / 204', 'badge' => 'bg-green-950 text-green-400', 'bar' => 'bg-green-500'],
                    ['group' => '3xx', 'label' => 'Redirect', 'sub' => 'Moved / Not Modified', 'badge' => 'bg-blue-950 text-blue-400', 'bar' => 'bg-blue-500'],
                    ['group' => '4xx', 'label' => 'Client error', 'sub' => 'Bad Request / 401 / 403 / 404','badge' => 'bg-yellow-950 text-yellow-400','bar' => 'bg-yellow-500'],
                    ['group' => '5xx', 'label' => 'Server error', 'sub' => '500 / 502 / 503 / 504', 'badge' => 'bg-red-950 text-red-400', 'bar' => 'bg-red-500'],
                    ];
                    @endphp

                    @foreach($statusGroups as $grp)
                    @php
                    $count = $byStatus->where('group', $grp['group'])->sum('total');
                    $pct = $totalStat > 0 ? round($count / $totalStat * 100, 1) : 0;
                    @endphp
                    <div class="px-4 py-3 bg-[#111111] hover:bg-[#161616] transition-colors duration-100">
                        <div class="flex items-start justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded {{ $grp['badge'] }}">{{ $grp['group'] }}</span>
                                <span class="text-xs font-mono text-[#e5e7eb]">{{ $grp['label'] }}</span>
                            </div>
                            <span class="text-xs font-mono font-semibold text-[#e5e7eb]">{{ number_format($count) }}</span>
                        </div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[10px] font-mono text-[#6b7280]">{{ $grp['sub'] }}</span>
                            <span class="text-[10px] font-mono text-[#6b7280]">{{ $pct }}%</span>
                        </div>
                        <div class="w-full bg-[#2a2a2a] rounded-full h-1 overflow-hidden">
                            <div class="{{ $grp['bar'] }} h-1 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>

            </div>

        </div>

</x-app-layout>