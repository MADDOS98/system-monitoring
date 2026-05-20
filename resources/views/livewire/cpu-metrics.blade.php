<div
    wire:key="cpu-metrics"
    data-bucket-seconds="{{ $bucketSeconds }}"
    data-label-format="{{ $labelFormat }}"
    data-from-ts="{{ $this->fromTs }}"
    data-to-ts="{{ $this->toTs }}">

    {{-- Card 1: Stats --}}
    <div class="grid grid-cols-4 gap-4 mb-4">

        @php
            $status = $totalUsage >= 90 ? 'danger' : ($totalUsage >= 75 ? 'warning' : 'healthy');
            $statusClasses = [
                'healthy' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                'warning' => 'bg-amber-500/10  text-amber-400  border-amber-500/30',
                'danger'  => 'bg-red-500/10    text-red-400    border-red-500/30',
            ];
            $dotClasses = [
                'healthy' => 'bg-emerald-400',
                'warning' => 'bg-amber-400',
                'danger'  => 'bg-red-400',
            ];
        @endphp

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Total usage</p>
            <div class="flex items-center gap-3">
                <p class="text-2xl font-semibold text-neutral-100">
                    <span data-cpu-total>{{ $totalUsage }}</span><span class="text-sm font-normal text-neutral-400">%</span>
                </p>
                <span data-cpu-status
                      class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] font-mono {{ $statusClasses[$status] }}">
                    <span data-cpu-status-dot class="w-1.5 h-1.5 rounded-full {{ $dotClasses[$status] }}"></span>
                    <span data-cpu-status-label>{{ $status }}</span>
                </span>
            </div>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Cores avg</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-cpu-cores-avg>{{ $coresAvg }}</span><span class="text-sm font-normal text-neutral-400">%</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Stolen</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-cpu-stolen>{{ $stolenUsage }}</span><span class="text-sm font-normal text-neutral-400">%</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Cores</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-cpu-cores>{{ $coreCount }}</span>
            </p>
        </div>

    </div>

    {{-- Card 2: Chart --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-mono font-semibold text-neutral-200">
                CPU usage &middot; <span data-cpu-period>{{ $periodLabel }}</span>
            </p>
            <div class="flex items-center gap-4 text-[11px] font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-cyan-400"></span>
                    <span class="text-neutral-400">total</span>
                    <span class="text-cyan-400"><span data-cpu-total-legend>{{ $totalUsage }}</span>%</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-neutral-500"></span>
                    <span class="text-neutral-500">cores avg</span>
                    <span class="text-neutral-500"><span data-cpu-cores-legend>{{ $coresAvg }}</span>%</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-red-400"></span>
                    <span class="text-neutral-400">stolen</span>
                    <span class="text-red-400"><span data-cpu-stolen-legend>{{ $stolenUsage }}</span>%</span>
                </span>
            </div>
        </div>

        <div style="height: 320px">
            <canvas
                id="cpu-chart-{{ $this->getId() }}"
                data-chart='@json($chartData)'
                style="width:100%;height:100%"></canvas>
        </div>
    </div>

</div>

@script
<script>
(function () {
    const id          = 'cpu-chart-{{ $this->getId() }}';
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

    const STATUS_BADGE = {
        healthy: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
        warning: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
        danger:  'bg-red-500/10 text-red-400 border-red-500/30',
    };
    const STATUS_DOT = {
        healthy: 'bg-emerald-400',
        warning: 'bg-amber-400',
        danger:  'bg-red-400',
    };

    function updateStatus(total) {
        const status = total >= 90 ? 'danger' : (total >= 75 ? 'warning' : 'healthy');
        const badge  = getRoot()?.querySelector('[data-cpu-status]');
        const dot    = getRoot()?.querySelector('[data-cpu-status-dot]');
        const label  = getRoot()?.querySelector('[data-cpu-status-label]');
        if (badge) {
            Object.values(STATUS_BADGE).forEach(cls => badge.classList.remove(...cls.split(' ')));
            badge.classList.add(...STATUS_BADGE[status].split(' '));
        }
        if (dot) {
            Object.values(STATUS_DOT).forEach(cls => dot.classList.remove(cls));
            dot.classList.add(STATUS_DOT[status]);
        }
        if (label) label.textContent = status;
    }

    function updateCards(d) {
        setText('[data-cpu-total]',         d.totalUsage);
        setText('[data-cpu-cores-avg]',     d.coresAvg);
        setText('[data-cpu-stolen]',        d.stolenUsage);
        setText('[data-cpu-cores]',         d.coreCount);
        setText('[data-cpu-total-legend]',  d.totalUsage);
        setText('[data-cpu-cores-legend]',  d.coresAvg);
        setText('[data-cpu-stolen-legend]', d.stolenUsage);
        setText('[data-cpu-period]',        d.periodLabel);
        updateStatus(d.totalUsage);
    }

    function applyChartData(c) {
        if (!chart || !c) return;
        chart.data.labels           = c.labels;
        chart.data.datasets[0].data = c.total;
        chart.data.datasets[1].data = c.coresAvg;
        chart.data.datasets[2].data = c.stolen;
        chart.update('none');
    }

    function buildChart() {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"total":[],"coresAvg":[],"stolen":[]}');
        if (chart) chart.destroy();

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    { label: 'total',     data: data.total,    borderColor: 'rgb(34, 211, 238)',       backgroundColor: 'rgba(34, 211, 238, 0.10)', borderWidth: 1.8, pointRadius: 0, fill: true,  tension: 0.4, spanGaps: true, order: 1 },
                    { label: 'cores avg', data: data.coresAvg, borderColor: 'rgba(156, 163, 175, 0.45)', backgroundColor: 'rgba(0, 0, 0, 0)',         borderWidth: 1,   pointRadius: 0, fill: false, tension: 0.4, spanGaps: true, order: 2 },
                    { label: 'stolen',    data: data.stolen,   borderColor: 'rgb(248, 113, 113)',      backgroundColor: 'rgba(248, 113, 113, 0.08)',borderWidth: 1.5, pointRadius: 0, fill: true,  tension: 0.4, spanGaps: true, order: 3 }
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
                        callbacks: { label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%' }
                    }
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' } },
                    y: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 } }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' }, beginAtZero: true, suggestedMax: 100 }
                }
            }
        });
    }

    buildChart();

    poller = window.createPoller({
        getUrl: () => {
            const { from, to } = getTimeRange();
            if (!from || !to) return null;
            return `/poll/metrics?type=cpu&from=${from}&to=${to}`;
        },
        intervalMs: getBucketMs(),
        onData: (d) => {
            updateCards(d);
            applyChartData(d.chartData);
        },
    });
    poller.start();

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'cpu-metrics') {
            buildChart();
            poller.setInterval(getBucketMs());
        }
    });
})();
</script>
@endscript
