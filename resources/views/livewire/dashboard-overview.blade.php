@php
    use Carbon\Carbon;

    // Helpers locale.
    $usageColor = function (float $pct): string {
        if ($pct >= 85) return 'text-red-400';
        if ($pct >= 50) return 'text-amber-400';
        return 'text-emerald-400';
    };
    $usageStatus = function (float $pct): string {
        if ($pct >= 85) return 'critical';
        if ($pct >= 50) return 'moderate load';
        return 'low load';
    };
    $barColor = function (float $pct): string {
        if ($pct >= 85) return 'bg-red-500';
        if ($pct >= 70) return 'bg-amber-500';
        return 'bg-blue-500';
    };
    $fmtRamShort = function (int $kb): string {
        if ($kb >= 1048576) return number_format($kb / 1048576, 1) . 'G';
        if ($kb >= 1024)    return number_format($kb / 1024, 0)    . 'M';
        return number_format($kb) . 'K';
    };

    $critical = (int) ($alertCountsByLevel['critical'] ?? 0);
    $warning  = (int) ($alertCountsByLevel['warning']  ?? 0);
    $info     = (int) ($alertCountsByLevel['info']     ?? 0);

    $tz = config('app.timezone');
@endphp

<div class="space-y-4">

    {{-- Page header --}}
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-text">Dashboard</h1>
        <p class="text-sm text-muted mt-0.5 font-mono">
            Overview &middot; refreshes on page reload
        </p>
    </div>

    {{-- ─────────────── Stat cards (6 colonne) ─────────────── --}}
    <div class="grid grid-cols-6 gap-3">

        {{-- CPU USAGE --}}
        <div class="rounded-lg border border-neutral-800 px-4 py-3 bg-[#0d0d0d]">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-2">CPU USAGE</p>
            <p class="text-3xl font-semibold {{ $usageColor($cpuUsage) }}">
                {{ $cpuUsage }}<span class="text-sm font-normal text-neutral-400">%</span>
            </p>
            <p class="text-[11px] font-mono text-neutral-500 mt-2">
                {{ $usageStatus($cpuUsage) }} &middot; {{ $cpuCores }} cores
            </p>
        </div>

        {{-- RAM USAGE --}}
        <div class="rounded-lg border border-neutral-800 px-4 py-3 bg-[#0d0d0d]">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-2">RAM USAGE</p>
            <p class="text-3xl font-semibold {{ $usageColor($ramUsedPct) }}">
                {{ $ramUsedPct }}<span class="text-sm font-normal text-neutral-400">%</span>
            </p>
            <p class="text-[11px] font-mono text-neutral-500 mt-2">
                {{ $ramUsedGb }} GB of {{ $ramTotalGb }} GB used
            </p>
        </div>

        {{-- DISK USAGE --}}
        <div class="rounded-lg border border-neutral-800 px-4 py-3 bg-[#0d0d0d]">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-2">DISK USAGE</p>
            <p class="text-3xl font-semibold {{ $usageColor($diskUsedPct) }}">
                {{ $diskUsedPct }}<span class="text-sm font-normal text-neutral-400">%</span>
            </p>
            <p class="text-[11px] font-mono text-neutral-500 mt-2">
                {{ $diskUsedGb }} GB of {{ $diskTotalGb }} GB
            </p>
        </div>

        {{-- NET THROUGHPUT --}}
        <div class="rounded-lg border border-neutral-800 px-4 py-3 bg-[#0d0d0d]">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-2">NET THROUGHPUT</p>
            <p class="text-3xl font-semibold text-text">
                {{ $totalMbps }}<span class="text-sm font-normal text-neutral-400">Mbps</span>
            </p>
            <p class="text-[11px] font-mono text-neutral-500 mt-2">
                &uarr; {{ $txMbps }} out &middot; &darr; {{ $rxMbps }} in
            </p>
        </div>

        {{-- ACTIVE PROCESSES --}}
        <div class="rounded-lg border border-neutral-800 px-4 py-3 bg-[#0d0d0d]">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-2">ACTIVE PROCESSES</p>
            <p class="text-3xl font-semibold text-text">
                {{ $activeProcessCount }}
            </p>
            <p class="text-[11px] font-mono text-neutral-500 mt-2">
                {{ $totalProcessCount }} total &middot; {{ $stoppedProcessCount }} stopped
            </p>
        </div>

        {{-- ALERTS --}}
        <div class="rounded-lg border border-neutral-800 px-4 py-3 bg-[#0d0d0d]">
            <p class="text-[10px] font-mono text-neutral-500 uppercase tracking-widest mb-2">ALERTS</p>
            <p class="text-3xl font-semibold {{ $totalActiveAlerts > 0 ? 'text-red-400' : 'text-emerald-400' }}">
                {{ $totalActiveAlerts }}
            </p>
            <p class="text-[11px] font-mono mt-2">
                <span class="{{ $critical > 0 ? 'text-red-400' : 'text-neutral-500' }}">{{ $critical }} critical</span>
                <span class="text-neutral-600">&middot;</span>
                <span class="{{ $warning > 0 ? 'text-amber-400' : 'text-neutral-500' }}">{{ $warning }} warning</span>
            </p>
        </div>

    </div>

    {{-- ─────────────── Middle area: Recent alerts | Top processes + Storage ─────────────── --}}
    <div class="grid grid-cols-12 gap-3">

        {{-- Recent alerts (col-7) --}}
        <div class="col-span-7 rounded-lg border border-neutral-800 bg-[#0d0d0d] flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-800 flex-shrink-0">
                <p class="text-sm font-semibold text-text">Recent alerts</p>
                @if($critical > 0)
                    <span class="inline-flex items-center gap-1.5 text-[11px] font-mono px-2 py-0.5 border border-red-500/40 text-red-400 rounded">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                        {{ $critical }} critical
                    </span>
                @endif
            </div>
            <div class="divide-y divide-neutral-800 flex-1">
                @forelse($recentAlerts as $alert)
                    @php
                        $dotColor = match($alert->level) {
                            'critical' => 'bg-red-500',
                            'warning'  => 'bg-amber-500',
                            'info'     => 'bg-blue-500',
                            default    => 'bg-neutral-500',
                        };
                        $ago = $alert->window_end
                            ? Carbon::createFromTimestamp((int) $alert->window_end, $tz)->diffForHumans(['short' => true])
                            : '—';
                    @endphp
                    <div class="px-5 py-3 flex items-start gap-3">
                        <div class="w-2 h-2 rounded-full {{ $dotColor }} mt-2 flex-shrink-0"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-text">{{ $alert->message }}</p>
                            <p class="text-[11px] font-mono text-neutral-500 mt-0.5">
                                @if($alert->metric)
                                    <span>{{ $alert->metric }}</span>
                                    <span class="text-neutral-600">&middot;</span>
                                @endif
                                {{ $ago }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-12 text-center text-sm font-mono text-neutral-500">
                        No alerts yet.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right side: Top processes + Storage stacked (col-5) --}}
        <div class="col-span-5 space-y-3">

            {{-- Top processes --}}
            <div class="rounded-lg border border-neutral-800 bg-[#0d0d0d]">
                <div class="px-5 py-4 border-b border-neutral-800">
                    <p class="text-sm font-semibold text-text">Top processes</p>
                </div>
                <div class="px-5">
                    <div class="grid grid-cols-12 text-[10px] font-mono text-neutral-500 uppercase tracking-widest py-2.5 border-b border-neutral-800">
                        <div class="col-span-5">Process</div>
                        <div class="col-span-2 text-right">CPU</div>
                        <div class="col-span-2 text-right">RAM</div>
                        <div class="col-span-3 text-right">Status</div>
                    </div>
                    @forelse($topProcesses as $p)
                        <div class="grid grid-cols-12 items-center py-2.5 border-b border-neutral-800 last:border-b-0">
                            <div class="col-span-5 font-mono font-semibold text-sm text-text truncate">
                                {{ $p->name }}
                            </div>
                            <div class="col-span-2 font-mono text-sm text-right text-amber-400">
                                {{ number_format((float) $p->cpu_pct, 1) }}%
                            </div>
                            <div class="col-span-2 font-mono text-sm text-right text-neutral-300">
                                {{ $fmtRamShort((int) $p->ram_kb) }}
                            </div>
                            <div class="col-span-3 text-right">
                                <span class="inline-flex items-center gap-1 text-[10px] font-mono px-1.5 py-0.5 rounded bg-green-900 text-green-400">
                                    <span class="w-1 h-1 rounded-full bg-green-400"></span>
                                    running
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-sm font-mono text-neutral-500">
                            No active processes.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Storage --}}
            <div class="rounded-lg border border-neutral-800 bg-[#0d0d0d] px-5 py-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-text">Storage</p>
                    @if($diskUsedPct >= 85)
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-mono px-2 py-0.5 border border-red-500/40 text-red-400 rounded">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                            1 critical
                        </span>
                    @endif
                </div>

                <div class="flex items-center justify-between mb-1.5">
                    <p class="text-xs font-mono text-neutral-400">Capacity</p>
                    <p class="text-xs font-mono {{ $diskUsedPct >= 85 ? 'text-red-400' : ($diskUsedPct >= 70 ? 'text-amber-400' : 'text-blue-400') }}">
                        {{ $diskUsedGb }} GB / {{ $diskTotalGb }} GB
                    </p>
                </div>

                <div class="w-full bg-neutral-800 rounded-full h-5 overflow-hidden">
                    <div class="{{ $barColor($diskUsedPct) }} h-full rounded-full text-xs font-semibold text-white text-center leading-5 transition-all duration-300"
                         style="width: {{ min(100, max(2, $diskUsedPct)) }}%">
                        {{ $diskUsedPct }}%
                    </div>
                </div>

                <div class="flex items-center justify-between mt-2">
                    <span class="text-[11px] font-mono {{ $diskUsedPct >= 85 ? 'text-red-400' : ($diskUsedPct >= 70 ? 'text-amber-400' : 'text-neutral-400') }}">
                        {{ $diskUsedPct >= 85 ? 'High pressure' : ($diskUsedPct >= 70 ? 'Elevated' : 'Normal') }}
                    </span>
                    <span class="text-[11px] font-mono text-neutral-500">
                        Free: {{ $diskFreeGb }} GB
                    </span>
                </div>
            </div>

        </div>

    </div>

</div>
