<div wire:key="status-table"
     data-bucket-seconds="{{ $bucketSeconds }}"
     class="rounded-lg border border-border overflow-hidden flex flex-col"
     style="height: calc(12 * 53px)">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0">
        <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Requests by status</span>
        <span class="text-xs font-mono text-[#6b7280]">{{ number_format($byStatus->sum('total')) }} total</span>
    </div>

    {{-- Top gradient bar --}}
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
                ['group' => '2xx', 'label' => 'Success',      'sub' => 'OK / Created / 204',           'badge' => 'bg-green-950 text-green-400',  'bar' => 'bg-green-500'],
                ['group' => '3xx', 'label' => 'Redirect',     'sub' => 'Moved / Not Modified',          'badge' => 'bg-blue-950 text-blue-400',    'bar' => 'bg-blue-500'],
                ['group' => '4xx', 'label' => 'Client error', 'sub' => 'Bad Request / 401 / 403 / 404', 'badge' => 'bg-yellow-950 text-yellow-400','bar' => 'bg-yellow-500'],
                ['group' => '5xx', 'label' => 'Server error', 'sub' => '500 / 502 / 503 / 504',         'badge' => 'bg-red-950 text-red-400',      'bar' => 'bg-red-500'],
            ];
        @endphp

        @foreach($statusGroups as $grp)
            @php
                $count = $byStatus->where('group', $grp['group'])->sum('total');
                $pct   = $totalStat > 0 ? round($count / $totalStat * 100, 1) : 0;
            @endphp
            <div class="px-4 py-3 bg-[#111111] hover:bg-[#161616] transition-colors duration-100">
                <div class="flex items-center justify-between mb-1.5">
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

        @if($byStatus->isEmpty())
            <div class="flex items-center justify-center h-full text-[#6b7280] text-xs font-mono">
                No data available.
            </div>
        @endif
    </div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    function getRoot() { return document.querySelector('[wire\\:id="' + componentId + '"]'); }
    function isLive() {
        const picker = document.querySelector('[data-live]');
        return picker?.dataset.live === '1';
    }

    let pendingRefresh = false;
    function scheduleRefresh() {
        if (!isLive()) return;
        if (pendingRefresh) return;
        pendingRefresh = true;
        const ms = parseInt(getRoot()?.dataset.bucketSeconds || '1', 10) * 1000;
        setTimeout(() => { $wire.$refresh(); pendingRefresh = false; }, ms);
    }

    if (window.Echo) {
        window.Echo.channel('apache-logs').listen('.ApacheLogCreated', scheduleRefresh);
    }
})();
</script>
@endscript

</div>