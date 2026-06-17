<div
    wire:key="disk-metrics"
    data-bucket-seconds="{{ $bucketSecondsIo }}"
    data-bucket-io="{{ $bucketSecondsIo }}"
    data-bucket-usage="{{ $bucketSecondsUsage }}"
    data-label-format-io="{{ $labelFormatIo }}"
    data-label-format-usage="{{ $labelFormatUsage }}"
    data-from-ts="{{ $this->fromTs }}"
    data-to-ts="{{ $this->toTs }}"
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

    {{-- Card 5: Disk growth forecast (30-day rolling) --}}
    <livewire:metrics.disk-growth-forecast />

</div>

@script
<script>
(function () {
    const ioId        = 'disk-io-chart-{{ $this->getId() }}';
    const usageId     = 'disk-usage-chart-{{ $this->getId() }}';
    const componentId = '{{ $this->getId() }}';
    const PRESET_MINUTES = { '5m': 5, '1h': 60, '24h': 1440 };

    let ioChart    = null;
    let usageChart = null;
    let poller     = null;

    function getRoot() { return document.querySelector('[wire\\:id="' + componentId + '"]'); }

    function getBucketMs() {
        const sec = parseInt(getRoot()?.dataset.bucketSeconds || '1', 10);
        return Math.max(1, sec) * 1000;
    }

    function getTimeRange() {
        const picker = document.querySelector('[data-live]');
        const live   = picker?.dataset.live === '1';
        const preset = picker?.dataset.preset;
        if (live && PRESET_MINUTES[preset]) {
            const to   = Math.floor(Date.now() / 1000);
            const from = to - PRESET_MINUTES[preset] * 60;
            return { from, to };
        }
        const root = getRoot();
        return {
            from: parseInt(root?.dataset.fromTs || '0', 10),
            to:   parseInt(root?.dataset.toTs   || '0', 10),
        };
    }

    function setText(selector, text) {
        const el = getRoot()?.querySelector(selector);
        if (el) el.textContent = text;
    }

    function bytesToMbs(b) { return Math.round(b / 60 / 1_000_000 * 100) / 100; }
    function bytesToGb(b)  { return Math.round(b / (1024*1024*1024) * 100) / 100; }

    function updateCards(d) {
        // I/O
        const r = bytesToMbs(d.readBytes);
        const w = bytesToMbs(d.writeBytes);
        setText('[data-disk-read-mbs]',         r);
        setText('[data-disk-write-mbs]',        w);
        setText('[data-disk-read-mbs-legend]',  r);
        setText('[data-disk-write-mbs-legend]', w);
        setText('[data-disk-io-period]',        d.periodLabel);
        setText('[data-disk-usage-period]',     d.periodLabel);

        // Usage
        const totalBytes = d.totalBytes;
        const usedBytes  = d.usedBytes;
        if (totalBytes <= 0) return;
        const usedGb  = bytesToGb(usedBytes);
        const totalGb = bytesToGb(totalBytes);
        const freeGb  = Math.round((totalGb - usedGb) * 100) / 100;
        const pct     = d.usedPct;
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

    // Y axis dinamic pentru I/O: rotunjit in sus la urmatorul MB/s intreg (floor minim 1).
    function computeIoYMax(c) {
        const all = [...(c?.read || []), ...(c?.write || [])];
        if (all.length === 0) return 1;
        return Math.max(1, Math.ceil(Math.max(...all, 0)));
    }

    function applyIoChartData(c) {
        if (!ioChart || !c) return;
        ioChart.data.labels                   = c.labels;
        ioChart.data.datasets[0].data         = c.read;
        ioChart.data.datasets[1].data         = c.write;
        ioChart.options.scales.y.suggestedMax = computeIoYMax(c);
        ioChart.update('none');
    }

    function applyUsageChartData(c) {
        if (!usageChart || !c) return;
        usageChart.data.labels           = c.labels;
        usageChart.data.datasets[0].data = c.values;
        usageChart.update('none');
    }

    function buildIoChart() {
        const canvas = document.getElementById(ioId);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"read":[],"write":[]}');
        if (ioChart) ioChart.destroy();

        const initialIoYMax = computeIoYMax(data);

        ioChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'read',  data: data.read,  borderColor: 'rgb(56, 189, 248)', backgroundColor: 'rgba(56, 189, 248, 0.08)', borderWidth: 1.5, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true },
                    { label: 'write', data: data.write, borderColor: 'rgb(251, 113, 133)',backgroundColor: 'rgba(251, 113, 133, 0.08)',borderWidth: 1.5, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true }
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
                        backgroundColor: '#1a1a1a', borderColor: '#3a3a3a', borderWidth: 1,
                        titleColor: '#e5e7eb', bodyColor: '#9ca3af',
                        titleFont: { family: 'monospace', size: 11 }, bodyFont: { family: 'monospace', size: 11 },
                        callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + ' MB/s' }
                    }
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' } },
                    y: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 } }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' }, beginAtZero: true, suggestedMax: initialIoYMax }
                }
            }
        });
    }

    function buildUsageChart() {
        const canvas = document.getElementById(usageId);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"values":[]}');
        if (usageChart) usageChart.destroy();

        // Y axis fixat la [0, totalGb] — re-citit la fiecare rebuild ca sa
        // urmareasca eventuale schimbari de totalBytes prin morph.updated.
        const totalBytesAttr = parseFloat(getRoot()?.dataset.totalBytes || '0');
        const totalGb        = totalBytesAttr > 0 ? totalBytesAttr / (1024 * 1024 * 1024) : null;

        usageChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    borderColor: 'rgb(6, 182, 212)',
                    backgroundColor: 'rgba(6, 182, 212, 0.08)',
                    borderWidth: 1.5, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true,
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
                        backgroundColor: '#1a1a1a', borderColor: '#3a3a3a', borderWidth: 1,
                        titleColor: '#e5e7eb', bodyColor: '#9ca3af',
                        titleFont: { family: 'monospace', size: 11 }, bodyFont: { family: 'monospace', size: 11 },
                        callbacks: { label: ctx => ' ' + ctx.parsed.y + ' GB' }
                    }
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' } },
                    y: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 } }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' }, beginAtZero: true, ...(totalGb ? { suggestedMax: totalGb } : {}) }
                }
            }
        });
    }

    buildIoChart();
    buildUsageChart();

    poller = window.createPoller({
        getUrl: () => {
            const { from, to } = getTimeRange();
            if (!from || !to) return null;
            return `/poll/metrics?type=disk&from=${from}&to=${to}`;
        },
        intervalMs: getBucketMs(),
        onData: (d) => {
            updateCards(d);
            applyIoChartData(d.ioChartData);
            applyUsageChartData(d.usageChartData);
        },
    });
    poller.start();

    // Opreste poller-ul cand userul navigheaza wire:navigate spre alt tab/pagina,
    // altfel closure-ul ramane viu si vechiul setInterval continua sa polezeze =>
    // requesturi care se acumuleaza la fiecare tab-switch.
    document.addEventListener('livewire:navigating', () => poller.stop(), { once: true });

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'metrics.disk-metrics') {
            buildIoChart();
            buildUsageChart();
            poller.setInterval(getBucketMs());
        }
    });
})();
</script>
@endscript
