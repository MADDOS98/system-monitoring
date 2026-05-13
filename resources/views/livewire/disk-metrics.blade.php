<div
    wire:key="disk-metrics"
    data-bucket-io="{{ $bucketSecondsIo }}"
    data-bucket-usage="{{ $bucketSecondsUsage }}"
    data-label-format-io="{{ $labelFormatIo }}"
    data-label-format-usage="{{ $labelFormatUsage }}"
    data-total-bytes="{{ $totalBytes }}">

    @php
        $readMbps  = round($readBytes  / 60 / 1_000_000, 2);
        $writeMbps = round($writeBytes / 60 / 1_000_000, 2);
        $totalGb   = $totalBytes > 0 ? round($totalBytes / (1024 * 1024 * 1024), 2) : 0;
        $usedGb    = $usedBytes  > 0 ? round($usedBytes  / (1024 * 1024 * 1024), 2) : 0;
        $freeGb    = $freeBytes  > 0 ? round($freeBytes  / (1024 * 1024 * 1024), 2) : 0;
    @endphp

    {{-- Card 1: Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-4">

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Read speed</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-disk-read-mbs>{{ $readMbps }}</span>
                <span class="text-sm font-normal text-neutral-400">MB/s</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Write speed</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-disk-write-mbs>{{ $writeMbps }}</span>
                <span class="text-sm font-normal text-neutral-400">MB/s</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Total storage</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-disk-total-gb>{{ $totalGb }}</span>
                <span class="text-sm font-normal text-neutral-400">GB</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Available</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-disk-free-gb>{{ $freeGb }}</span>
                <span class="text-sm font-normal text-neutral-400">GB</span>
            </p>
        </div>

    </div>

    {{-- Card 2: Capacity progress bar (like RAM) --}}
    <div class="rounded-lg border border-neutral-800 px-5 py-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-mono text-neutral-400">Capacity</p>
            <p data-disk-cap-label
               class="text-xs font-mono {{ $usedPct >= 75 ? 'text-red-400' : 'text-blue-400' }}">
                {{ $usedGb }} GB / {{ $totalGb }} GB
            </p>
        </div>

        <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700">
            <div
                data-disk-bar
                class="{{ $usedPct >= 75 ? 'bg-red-600' : 'bg-blue-600' }} text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full"
                style="width: {{ min(100, $usedPct) }}%"> <span data-disk-bar-pct>{{ $usedPct }}</span>%</div>
        </div>

        <div class="flex items-center justify-between mt-2">
            <span data-disk-cap-status
                  class="text-[11px] font-mono {{ $usedPct >= 75 ? 'text-red-400' : 'text-neutral-400' }}">
                {{ $usedPct >= 75 ? 'High pressure' : 'Normal' }}
            </span>
            <span class="text-[11px] font-mono text-neutral-500">
                Free: <span data-disk-cap-free-gb>{{ $freeGb }}</span> GB
            </span>
        </div>
    </div>

    {{-- Card 3: I/O throughput chart --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3 mb-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-mono font-semibold text-neutral-200">
                I/O throughput &middot; <span data-disk-io-period>{{ $periodLabel }}</span>
            </p>
            <div class="flex items-center gap-4 text-[11px] font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-sky-400"></span>
                    <span class="text-neutral-400">read</span>
                    <span class="text-sky-400"><span data-disk-read-mbs-legend>{{ $readMbps }}</span> MB/s</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-rose-400"></span>
                    <span class="text-neutral-400">write</span>
                    <span class="text-rose-400"><span data-disk-write-mbs-legend>{{ $writeMbps }}</span> MB/s</span>
                </span>
            </div>
        </div>

        <div style="height: 280px">
            <canvas
                id="disk-io-chart-{{ $this->getId() }}"
                data-chart='@json($ioChartData)'
                style="width:100%;height:100%"></canvas>
        </div>
    </div>

    {{-- Card 4: Disk usage chart (like RAM) --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-mono font-semibold text-neutral-200">Disk usage</p>
            <p class="text-[11px] font-mono text-blue-400">
                used <span data-disk-used-gb-chart>{{ $usedGb }}</span> GB
            </p>
        </div>
        <p class="text-[11px] font-mono text-neutral-500 mb-3">
            used of <span data-disk-total-gb-chart>{{ $totalGb }}</span> GB &middot; <span data-disk-usage-period>{{ $periodLabel }}</span>
        </p>

        <div style="height: 220px">
            <canvas
                id="disk-usage-chart-{{ $this->getId() }}"
                data-chart='@json($usageChartData)'
                style="width:100%;height:100%"></canvas>
        </div>
    </div>

</div>

@script
<script>
(function () {
    const ioId    = 'disk-io-chart-{{ $this->getId() }}';
    const usageId = 'disk-usage-chart-{{ $this->getId() }}';
    const componentId = '{{ $this->getId() }}';

    let ioChart    = null;
    let usageChart = null;
    let ioPending    = null; // { start, readSum, readCount, writeSum, writeCount }
    let usagePending = null; // { start, sum, count }

    function pad(n) { return String(n).padStart(2, '0'); }

    function getRoot() {
        return document.querySelector('[wire\\:id="' + componentId + '"]');
    }

    function getConfig() {
        const el = getRoot();
        return {
            bucketIo:        parseInt(el?.dataset.bucketIo        || '0', 10),
            bucketUsage:     parseInt(el?.dataset.bucketUsage     || '0', 10),
            labelFormatIo:   el?.dataset.labelFormatIo   || 'H:i:s',
            labelFormatUsage:el?.dataset.labelFormatUsage|| 'H:i',
            totalBytes:      parseInt(el?.dataset.totalBytes || '0', 10),
        };
    }

    function formatLabel(ts, fmt) {
        const d = new Date(ts * 1000);
        switch (fmt) {
            case 'H:i:s':   return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
            case 'H:i':     return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
            case 'M j':     return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            case 'M j H:i': return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            default:        return `${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }
    }

    function isLive() {
        const picker = document.querySelector('[data-live]');
        return picker?.dataset.live === '1';
    }

    function setText(selector, text) {
        const el = getRoot()?.querySelector(selector);
        if (el) el.textContent = text;
    }

    function bytesToMbs(b) {
        return Math.round(b / 60 / 1_000_000 * 100) / 100;
    }

    function bytesToGb(b) {
        return Math.round(b / (1024*1024*1024) * 100) / 100;
    }

    function updateIoCards(readBytes, writeBytes) {
        const r = bytesToMbs(readBytes);
        const w = bytesToMbs(writeBytes);
        setText('[data-disk-read-mbs]',        r);
        setText('[data-disk-write-mbs]',       w);
        setText('[data-disk-read-mbs-legend]', r);
        setText('[data-disk-write-mbs-legend]',w);
    }

    function updateUsageCards(totalBytes, usedBytes) {
        if (totalBytes <= 0) return;
        const usedGb  = bytesToGb(usedBytes);
        const totalGb = bytesToGb(totalBytes);
        const freeGb  = Math.round((totalGb - usedGb) * 100) / 100;
        const pct     = Math.round((usedBytes / totalBytes) * 1000) / 10;
        const high    = pct >= 75;

        setText('[data-disk-total-gb]',        totalGb);
        setText('[data-disk-free-gb]',         freeGb);
        setText('[data-disk-cap-free-gb]',     freeGb);
        setText('[data-disk-used-gb-chart]',   usedGb);
        setText('[data-disk-total-gb-chart]',  totalGb);
        setText('[data-disk-bar-pct]',         pct);
        setText('[data-disk-cap-label]',       `${usedGb} GB / ${totalGb} GB`);
        setText('[data-disk-cap-status]',      high ? 'High pressure' : 'Normal');

        const bar = getRoot()?.querySelector('[data-disk-bar]');
        if (bar) {
            bar.style.width = Math.min(100, pct) + '%';
            bar.classList.toggle('bg-red-600',  high);
            bar.classList.toggle('bg-blue-600', !high);
        }
        const label = getRoot()?.querySelector('[data-disk-cap-label]');
        if (label) {
            label.classList.toggle('text-red-400',  high);
            label.classList.toggle('text-blue-400', !high);
        }
        const status = getRoot()?.querySelector('[data-disk-cap-status]');
        if (status) {
            status.classList.toggle('text-red-400',     high);
            status.classList.toggle('text-neutral-400', !high);
        }
    }

    function shiftAndPushIo(label, rVal, wVal) {
        ioChart.data.labels.shift();
        ioChart.data.labels.push(label);
        ioChart.data.datasets[0].data.shift();
        ioChart.data.datasets[0].data.push(rVal);
        ioChart.data.datasets[1].data.shift();
        ioChart.data.datasets[1].data.push(wVal);
        ioChart.update('none');

        const first = ioChart.data.labels[0];
        const last  = ioChart.data.labels[ioChart.data.labels.length - 1];
        setText('[data-disk-io-period]', `${first} – ${last}`);
    }

    function shiftAndPushUsage(label, value) {
        usageChart.data.labels.shift();
        usageChart.data.labels.push(label);
        usageChart.data.datasets[0].data.shift();
        usageChart.data.datasets[0].data.push(value);
        usageChart.update('none');

        const first = usageChart.data.labels[0];
        const last  = usageChart.data.labels[usageChart.data.labels.length - 1];
        setText('[data-disk-usage-period]', `${first} – ${last}`);
    }

    function buildIoChart() {
        const canvas = document.getElementById(ioId);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"read":[],"write":[]}');
        if (ioChart) ioChart.destroy();

        ioChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'read',
                        data: data.read,
                        borderColor: 'rgb(56, 189, 248)',
                        backgroundColor: 'rgba(56, 189, 248, 0.08)',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                    },
                    {
                        label: 'write',
                        data: data.write,
                        borderColor: 'rgb(251, 113, 133)',
                        backgroundColor: 'rgba(251, 113, 133, 0.08)',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        borderColor: '#3a3a3a',
                        borderWidth: 1,
                        titleColor: '#e5e7eb',
                        bodyColor: '#9ca3af',
                        titleFont: { family: 'monospace', size: 11 },
                        bodyFont:  { family: 'monospace', size: 11 },
                        callbacks: {
                            label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + ' MB/s'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 },
                        grid:  { color: 'rgba(255,255,255,0.04)' },
                        border: { color: '#2a2a2a' },
                    },
                    y: {
                        ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 } },
                        grid:  { color: 'rgba(255,255,255,0.04)' },
                        border: { color: '#2a2a2a' },
                        beginAtZero: true,
                    }
                }
            }
        });
    }

    function buildUsageChart() {
        const canvas = document.getElementById(usageId);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"values":[]}');
        if (usageChart) usageChart.destroy();

        usageChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    borderColor: 'rgb(6, 182, 212)',
                    backgroundColor: 'rgba(6, 182, 212, 0.08)',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.4,
                    spanGaps: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        borderColor: '#3a3a3a',
                        borderWidth: 1,
                        titleColor: '#e5e7eb',
                        bodyColor: '#9ca3af',
                        titleFont: { family: 'monospace', size: 11 },
                        bodyFont:  { family: 'monospace', size: 11 },
                        callbacks: { label: ctx => ' ' + ctx.parsed.y + ' GB' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 },
                        grid:  { color: 'rgba(255,255,255,0.04)' },
                        border: { color: '#2a2a2a' },
                    },
                    y: {
                        ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 } },
                        grid:  { color: 'rgba(255,255,255,0.04)' },
                        border: { color: '#2a2a2a' },
                    }
                }
            }
        });
    }

    buildIoChart();
    buildUsageChart();

    function onEvent(e) {
        if (!isLive()) return;

        if (e.type === 'disk_io') {
            const { bucketIo, labelFormatIo } = getConfig();
            if (bucketIo <= 0) return;

            const ts = e.collectedAt;
            const r  = e.payload.read_bytes;
            const w  = e.payload.write_bytes;

            updateIoCards(r, w);

            const bucketStart = Math.floor(ts / bucketIo) * bucketIo;
            if (ioPending && ioPending.start !== bucketStart) {
                const rAvg = ioPending.readSum  / ioPending.readCount;
                const wAvg = ioPending.writeSum / ioPending.writeCount;
                shiftAndPushIo(
                    formatLabel(ioPending.start, labelFormatIo),
                    bytesToMbs(rAvg),
                    bytesToMbs(wAvg)
                );
                ioPending = null;
            }
            if (!ioPending) {
                ioPending = { start: bucketStart, readSum: 0, readCount: 0, writeSum: 0, writeCount: 0 };
            }
            ioPending.readSum   += r;
            ioPending.readCount += 1;
            ioPending.writeSum  += w;
            ioPending.writeCount+= 1;
            return;
        }

        if (e.type === 'disk_usage') {
            const { bucketUsage, labelFormatUsage } = getConfig();
            if (bucketUsage <= 0) return;

            const ts    = e.collectedAt;
            const total = e.payload.total_bytes;
            const used  = e.payload.used_bytes;

            updateUsageCards(total, used);

            const bucketStart = Math.floor(ts / bucketUsage) * bucketUsage;
            if (usagePending && usagePending.start !== bucketStart) {
                const avg = usagePending.sum / usagePending.count;
                shiftAndPushUsage(
                    formatLabel(usagePending.start, labelFormatUsage),
                    Math.round(avg / (1024*1024*1024) * 100) / 100
                );
                usagePending = null;
            }
            if (!usagePending) {
                usagePending = { start: bucketStart, sum: 0, count: 0 };
            }
            usagePending.sum   += used;
            usagePending.count += 1;
            return;
        }
    }

    if (window.Echo) {
        window.Echo.channel('metrics').listen('.MetricCollected', onEvent);
    }

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'disk-metrics') {
            buildIoChart();
            buildUsageChart();
            ioPending    = null;
            usagePending = null;
        }
    });
})();
</script>
@endscript
