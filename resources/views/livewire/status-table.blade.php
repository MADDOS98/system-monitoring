<div wire:key="status-table"
     data-bucket-seconds="{{ $bucketSeconds }}"
     class="rounded-lg border border-border overflow-hidden flex flex-col"
     style="height: calc(12 * 53px)">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0">
        <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Requests by status</span>
        <span data-status-total class="text-xs font-mono text-[#6b7280]">{{ number_format($byStatus->sum('total')) }} total</span>
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
        <div data-status-top-bar class="w-full rounded-full h-2" style="background: {{ $topGradient }}"></div>
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
                    <span data-status-count-{{ $grp['group'] }} class="text-xs font-mono font-semibold text-[#e5e7eb]">{{ number_format($count) }}</span>
                </div>
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-mono text-[#6b7280]">{{ $grp['sub'] }}</span>
                    <span data-status-pct-{{ $grp['group'] }} class="text-[10px] font-mono text-[#6b7280]">{{ $pct }}%</span>
                </div>
                <div class="w-full bg-[#2a2a2a] rounded-full h-1 overflow-hidden">
                    <div data-status-bar-{{ $grp['group'] }} class="{{ $grp['bar'] }} h-1 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        @endforeach
    </div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    function getRoot() { return document.querySelector('[wire\\:id="' + componentId + '"]'); }

    function setText(selector, text) {
        const el = getRoot()?.querySelector(selector);
        if (el) el.textContent = text;
    }
    function setStyle(selector, prop, val) {
        const el = getRoot()?.querySelector(selector);
        if (el) el.style[prop] = val;
    }

    function updateStatus(status) {
        if (!status) return;
        const total = Math.max(1, status.total || 0);
        const counts = { '2xx': status['2xx'] || 0, '3xx': status['3xx'] || 0, '4xx': status['4xx'] || 0, '5xx': status['5xx'] || 0 };
        const pct = {
            '2xx': Math.round(counts['2xx'] / total * 1000) / 10,
            '3xx': Math.round(counts['3xx'] / total * 1000) / 10,
            '4xx': Math.round(counts['4xx'] / total * 1000) / 10,
            '5xx': Math.round(counts['5xx'] / total * 1000) / 10,
        };

        setText('[data-status-total]', `${Number(status.total || 0).toLocaleString()} total`);

        const e2 = pct['2xx'];
        const e3 = e2 + pct['3xx'];
        const e4 = e3 + pct['4xx'];
        const e5 = e4 + pct['5xx'];
        const gradient = `linear-gradient(to right, #22c55e 0% ${e2}%, #3b82f6 ${e2}% ${e3}%, #eab308 ${e3}% ${e4}%, #ef4444 ${e4}% ${e5}%)`;
        setStyle('[data-status-top-bar]', 'background', gradient);

        ['2xx', '3xx', '4xx', '5xx'].forEach((grp) => {
            setText(`[data-status-count-${grp}]`,   Number(counts[grp]).toLocaleString());
            setText(`[data-status-pct-${grp}]`,     pct[grp] + '%');
            setStyle(`[data-status-bar-${grp}]`, 'width', pct[grp] + '%');
        });
    }

    document.addEventListener('apache-logs-poll', (e) => {
        if (!e.detail) return;
        updateStatus(e.detail.status);
    });
})();
</script>
@endscript

</div>