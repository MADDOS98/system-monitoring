<div
    wire:key="cpu-metrics"
    data-bucket-seconds="{{ $bucketSeconds }}"
    data-label-format="{{ $labelFormat }}">

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
    let chart   = null;
    let pending = null; // { start, totalSum, coresSum, stolenSum, count }

    function pad(n) { return String(n).padStart(2, '0'); }

    function getRoot() {
        return document.querySelector('[wire\\:id="' + componentId + '"]');
    }

    function getConfig() {
        const el = getRoot();
        return {
            bucketSeconds: parseInt(el?.dataset.bucketSeconds || '0', 10),
            labelFormat:   el?.dataset.labelFormat || 'H:i:s',
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

    function updateCards(total, coresAvg, stolen) {
        setText('[data-cpu-total]',         total);
        setText('[data-cpu-cores-avg]',     coresAvg);
        setText('[data-cpu-stolen]',        stolen);
        setText('[data-cpu-total-legend]',  total);
        setText('[data-cpu-cores-legend]',  coresAvg);
        setText('[data-cpu-stolen-legend]', stolen);
        updateStatus(total);
    }

    function shiftAndPush(label, totalVal, coresVal, stolenVal) {
        chart.data.labels.shift();
        chart.data.labels.push(label);
        chart.data.datasets[0].data.shift();
        chart.data.datasets[0].data.push(totalVal);
        chart.data.datasets[1].data.shift();
        chart.data.datasets[1].data.push(coresVal);
        chart.data.datasets[2].data.shift();
        chart.data.datasets[2].data.push(stolenVal);
        chart.update('none');

        const first = chart.data.labels[0];
        const last  = chart.data.labels[chart.data.labels.length - 1];
        setText('[data-cpu-period]', `${first} – ${last}`);
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
                    {
                        label: 'total',
                        data: data.total,
                        borderColor: 'rgb(34, 211, 238)',
                        backgroundColor: 'rgba(34, 211, 238, 0.10)',
                        borderWidth: 1.8,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                        order: 1,
                    },
                    {
                        label: 'cores avg',
                        data: data.coresAvg,
                        borderColor: 'rgba(156, 163, 175, 0.45)',
                        backgroundColor: 'rgba(0, 0, 0, 0)',
                        borderWidth: 1,
                        pointRadius: 0,
                        fill: false,
                        tension: 0.4,
                        spanGaps: true,
                        order: 2,
                    },
                    {
                        label: 'stolen',
                        data: data.stolen,
                        borderColor: 'rgb(248, 113, 113)',
                        backgroundColor: 'rgba(248, 113, 113, 0.08)',
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: true,
                        tension: 0.4,
                        spanGaps: true,
                        order: 3,
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
                            label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%'
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
                        suggestedMax: 100,
                    }
                }
            }
        });
    }

    buildChart();

    function onEvent(e) {
        if (e.type !== 'cpu') return;
        if (!isLive()) return;

        const { bucketSeconds, labelFormat } = getConfig();
        if (bucketSeconds <= 0) return;

        const ts       = e.collectedAt;
        const total    = e.payload.total_usage;
        const cores    = e.payload.per_core_usage || [];
        const stolen   = e.payload.stolen_usage;
        const coresAvg = cores.length > 0
            ? Math.round(cores.reduce((a,b) => a+b, 0) / cores.length * 10) / 10
            : 0;

        updateCards(total, coresAvg, stolen);

        const bucketStart = Math.floor(ts / bucketSeconds) * bucketSeconds;
        if (pending && pending.start !== bucketStart) {
            shiftAndPush(
                formatLabel(pending.start, labelFormat),
                Math.round(pending.totalSum  / pending.count * 100) / 100,
                Math.round(pending.coresSum  / pending.count * 100) / 100,
                Math.round(pending.stolenSum / pending.count * 100) / 100
            );
            pending = null;
        }
        if (!pending) {
            pending = { start: bucketStart, totalSum: 0, coresSum: 0, stolenSum: 0, count: 0 };
        }
        pending.totalSum  += total;
        pending.coresSum  += coresAvg;
        pending.stolenSum += stolen;
        pending.count     += 1;
    }

    if (window.Echo) {
        window.Echo.channel('metrics').listen('.MetricCollected', onEvent);
    }

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'cpu-metrics') {
            buildChart();
            pending = null;
        }
    });
})();
</script>
@endscript
