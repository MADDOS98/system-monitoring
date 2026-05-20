<div
    wire:key="ram-metrics"
    data-bucket-seconds="{{ $bucketSeconds }}"
    data-label-format="{{ $labelFormat }}"
    data-from-ts="{{ $this->fromTs }}"
    data-to-ts="{{ $this->toTs }}"
    data-total-kb="{{ $totalKb }}"
    data-label-format="{{ $labelFormat }}"
    data-total-kb="{{ $totalKb }}">

    {{-- Card 1: Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-4">

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Total</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-ram-total-gb>{{ round($totalKb / (1024 * 1024), 2) }}</span>
                <span class="text-sm font-normal text-neutral-400">GB</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Used</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-ram-used-gb>{{ round($usedKb / (1024 * 1024), 2) }}</span>
                <span class="text-sm font-normal text-neutral-400">GB</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Usage</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-ram-used-pct>{{ $usedPct }}</span><span class="text-sm font-normal text-neutral-400">%</span>
            </p>
        </div>

    </div>

    {{-- Card 2: Progress bar --}}
    <div class="rounded-lg border border-neutral-800 px-5 py-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-mono text-neutral-400">Current pressure</p>
            <p data-ram-pressure-label
               class="text-xs font-mono {{ $usedPct >= 75 ? 'text-red-400' : 'text-blue-400' }}">
                {{ round($usedKb / (1024 * 1024), 2) }} GB / {{ round($totalKb / (1024 * 1024), 2) }} GB
            </p>
        </div>

        <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700">
            <div
                data-ram-bar
                class="{{ $usedPct >= 75 ? 'bg-red-600' : 'bg-blue-600' }} text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full"
                style="width: {{ min(100, $usedPct) }}%"> <span data-ram-bar-pct>{{ $usedPct }}</span>%</div>
        </div>

        <div class="flex items-center justify-between mt-2">
            <span data-ram-pressure-status
                  class="text-[11px] font-mono {{ $usedPct >= 75 ? 'text-red-400' : 'text-neutral-400' }}">
                {{ $usedPct >= 75 ? 'High pressure' : 'Normal' }}
            </span>
            <span class="text-[11px] font-mono text-neutral-500">
                Free: <span data-ram-free-gb>{{ round($freeKb / (1024 * 1024), 2) }}</span> GB
            </span>
        </div>
    </div>

    {{-- Card 3: Chart --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-mono font-semibold text-neutral-200">Memory usage</p>
            <p class="text-[11px] font-mono text-blue-400">
                used <span data-ram-used-gb-chart>{{ round($usedKb / (1024 * 1024), 2) }}</span> GB
            </p>
        </div>
        <p class="text-[11px] font-mono text-neutral-500 mb-3">
            used of {{ round($totalKb / (1024 * 1024), 2) }} GB &middot; <span data-ram-period>{{ $periodLabel }}</span>
        </p>

        <div style="height: 220px">
            <canvas
                id="ram-chart-{{ $this->getId() }}"
                data-chart='@json($chartData)'
                style="width:100%;height:100%"></canvas>
        </div>
    </div>

</div>

@script
<script>
(function() {
    const id          = 'ram-chart-{{ $this->getId() }}';
    const componentId = '{{ $this->getId() }}';
    const PRESET_MINUTES = { '5m': 5, '1h': 60, '24h': 1440 };
    let chart  = null;
    let poller = null;

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

    function updateCards(d) {
        const totalKb = d.totalKb;
        const usedKb  = d.usedKb;
        if (totalKb <= 0) return;
        const usedGb  = Math.round((usedKb / (1024*1024)) * 100) / 100;
        const freeGb  = Math.round((d.freeKb / (1024*1024)) * 100) / 100;
        const totalGb = Math.round((totalKb / (1024*1024)) * 100) / 100;
        const pct     = d.usedPct;
        const high    = pct >= 75;

        setText('[data-ram-total-gb]',        totalGb);
        setText('[data-ram-used-gb]',         usedGb);
        setText('[data-ram-used-pct]',        pct);
        setText('[data-ram-bar-pct]',         pct);
        setText('[data-ram-free-gb]',         freeGb);
        setText('[data-ram-used-gb-chart]',   usedGb);
        setText('[data-ram-pressure-label]',  `${usedGb} GB / ${totalGb} GB`);
        setText('[data-ram-pressure-status]', high ? 'High pressure' : 'Normal');
        setText('[data-ram-period]',          d.periodLabel);

        const bar = getRoot()?.querySelector('[data-ram-bar]');
        if (bar) {
            bar.style.width = Math.min(100, pct) + '%';
            bar.classList.toggle('bg-red-600',  high);
            bar.classList.toggle('bg-blue-600', !high);
        }
        const label = getRoot()?.querySelector('[data-ram-pressure-label]');
        if (label) {
            label.classList.toggle('text-red-400',  high);
            label.classList.toggle('text-blue-400', !high);
        }
        const status = getRoot()?.querySelector('[data-ram-pressure-status]');
        if (status) {
            status.classList.toggle('text-red-400',     high);
            status.classList.toggle('text-neutral-400', !high);
        }
    }

    function applyChartData(c) {
        if (!chart || !c) return;
        chart.data.labels           = c.labels;
        chart.data.datasets[0].data = c.values;
        chart.update('none');
    }

    function buildChart() {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"values":[]}');
        if (chart) chart.destroy();

        chart = new Chart(canvas, {
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
                    y: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 } }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' } }
                }
            }
        });
    }

    buildChart();

    poller = window.createPoller({
        getUrl: () => {
            const { from, to } = getTimeRange();
            if (!from || !to) return null;
            return `/poll/metrics?type=ram&from=${from}&to=${to}`;
        },
        intervalMs: getBucketMs(),
        onData: (d) => {
            updateCards(d);
            applyChartData(d.chartData);
        },
    });
    poller.start();

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'ram-metrics') {
            buildChart();
            poller.setInterval(getBucketMs());
        }
    });
})();
</script>
@endscript
