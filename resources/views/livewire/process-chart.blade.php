@php
    // Helper-e locale pentru formatare in stat cards.
    $fmtKb = function (int $kb): string {
        if ($kb >= 1048576) return number_format($kb / 1048576, 2) . ' GB';
        if ($kb >= 1024)    return number_format($kb / 1024, 0)    . ' MB';
        return number_format($kb, 0) . ' KB';
    };
    $fmtBps = function (int $b): string {
        if ($b >= 1073741824) return number_format($b / 1073741824, 1) . ' GB/s';
        if ($b >= 1048576)    return number_format($b / 1048576, 1)    . ' MB/s';
        if ($b >= 1024)       return number_format($b / 1024, 1)       . ' KB/s';
        return $b . ' B/s';
    };
@endphp

<div wire:key="process-chart-{{ $metric }}"
     data-bucket-seconds="{{ $bucketSeconds }}"
     data-label-format="{{ $labelFormat }}"
     data-from-ts="{{ $this->fromTs }}"
     data-to-ts="{{ $this->toTs }}"
     data-metric="{{ $metric }}"
     data-name="{{ $this->name }}">

    {{-- ─────────────── Summary stat cards ─────────────── --}}
    <div class="grid grid-cols-4 gap-4 mb-4">

        @switch($metric)

            @case('cpu')
                @php
                    $status = $latest >= 1000 ? 'danger' : ($latest >= 500 ? 'warning' : 'healthy');
                    $statusClasses = [
                        'healthy' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
                        'warning' => 'bg-amber-500/10  text-amber-400  border-amber-500/30',
                        'danger'  => 'bg-red-500/10    text-red-400    border-red-500/30',
                    ];
                @endphp

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Current</p>
                    <div class="flex items-center gap-3">
                        <p class="text-2xl font-semibold text-neutral-100">
                            <span data-stat-current>{{ $latest }}</span><span class="text-sm font-normal text-neutral-400">%</span>
                        </p>
                        <span data-stat-status
                              class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] font-mono {{ $statusClasses[$status] }}">
                            <span data-stat-status-label>{{ $status }}</span>
                        </span>
                    </div>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Avg in window</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-avg>{{ $avg }}</span><span class="text-sm font-normal text-neutral-400">%</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Peak in window</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-peak>{{ $peak }}</span><span class="text-sm font-normal text-neutral-400">%</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Instances</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        &times; <span data-stat-count>{{ $count }}</span>
                    </p>
                </div>
                @break

            @case('ram')
                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Current</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-current>{{ $fmtKb($latest) }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Avg in window</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-avg>{{ $fmtKb($avg) }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Peak in window</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-peak>{{ $fmtKb($peak) }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Instances</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        &times; <span data-stat-count>{{ $count }}</span>
                    </p>
                </div>
                @break

            @case('io')
                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Current read</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-current-read>{{ $fmtBps($latestRead) }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Current write</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-current-write>{{ $fmtBps($latestWrite) }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Peak read</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-peak-read>{{ $fmtBps($peakRead) }}</span>
                    </p>
                </div>

                <div class="rounded-lg border border-neutral-800 px-5 py-4">
                    <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Peak write</p>
                    <p class="text-2xl font-semibold text-neutral-100">
                        <span data-stat-peak-write>{{ $fmtBps($peakWrite) }}</span>
                    </p>
                </div>
                @break
        @endswitch

    </div>

    {{-- ─────────────── Chart card ─────────────── --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-mono font-semibold text-neutral-200">
                @switch($metric)
                    @case('cpu') CPU usage @break
                    @case('ram') RAM usage @break
                    @case('io')  Disk I/O @break
                @endswitch
                &middot; <span data-period>{{ $periodLabel }}</span>
            </p>

            @if($metric === 'io')
                <div class="flex items-center gap-4 text-[11px] font-mono">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-sm bg-cyan-400"></span>
                        <span class="text-neutral-400">read</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-sm bg-orange-400"></span>
                        <span class="text-neutral-400">write</span>
                    </span>
                </div>
            @endif
        </div>

        <div style="height: 320px">
            <canvas id="process-chart-{{ $metric }}-{{ $this->getId() }}"
                    data-chart='@json($chartData)'
                    style="width:100%;height:100%"></canvas>
        </div>
    </div>

</div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    const metric      = '{{ $metric }}';
    const name        = @json($this->name);
    const canvasId    = 'process-chart-' + metric + '-' + componentId;
    const PRESET_MINUTES = { '5m': 5, '1h': 60, '24h': 1440 };

    let chart  = null;
    let poller = null;

    function getRoot()    { return document.querySelector('[wire\\:id="' + componentId + '"]'); }
    function getBucket()  { return Math.max(1, parseInt(getRoot()?.dataset.bucketSeconds || '15', 10)) * 1000; }

    function getRange() {
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

    function setText(sel, v) {
        const el = getRoot()?.querySelector(sel);
        if (el) el.textContent = v;
    }

    function fmtKb(kb) {
        if (kb >= 1048576) return (kb / 1048576).toFixed(2) + ' GB';
        if (kb >= 1024)    return Math.round(kb / 1024) + ' MB';
        return Math.round(kb) + ' KB';
    }
    function fmtBps(b) {
        if (b >= 1073741824) return (b / 1073741824).toFixed(1) + ' GB/s';
        if (b >= 1048576)    return (b / 1048576).toFixed(1)    + ' MB/s';
        if (b >= 1024)       return (b / 1024).toFixed(1)       + ' KB/s';
        return b + ' B/s';
    }

    const STATUS_CLASSES = {
        healthy: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30',
        warning: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
        danger:  'bg-red-500/10 text-red-400 border-red-500/30',
    };

    function updateCpuStatus(latest) {
        const status = latest >= 1000 ? 'danger' : (latest >= 500 ? 'warning' : 'healthy');
        const badge  = getRoot()?.querySelector('[data-stat-status]');
        const label  = getRoot()?.querySelector('[data-stat-status-label]');
        if (badge) {
            Object.values(STATUS_CLASSES).forEach(c => badge.classList.remove(...c.split(' ')));
            badge.classList.add(...STATUS_CLASSES[status].split(' '));
        }
        if (label) label.textContent = status;
    }

    function updateCards(d) {
        setText('[data-period]', d.periodLabel);
        if (metric === 'cpu') {
            setText('[data-stat-current]', d.latest);
            setText('[data-stat-avg]',     d.avg);
            setText('[data-stat-peak]',    d.peak);
            setText('[data-stat-count]',   d.count);
            updateCpuStatus(d.latest);
        } else if (metric === 'ram') {
            setText('[data-stat-current]', fmtKb(d.latest));
            setText('[data-stat-avg]',     fmtKb(d.avg));
            setText('[data-stat-peak]',    fmtKb(d.peak));
            setText('[data-stat-count]',   d.count);
        } else if (metric === 'io') {
            setText('[data-stat-current-read]',  fmtBps(d.latestRead));
            setText('[data-stat-current-write]', fmtBps(d.latestWrite));
            setText('[data-stat-peak-read]',     fmtBps(d.peakRead));
            setText('[data-stat-peak-write]',    fmtBps(d.peakWrite));
        }
    }

    function applyChartData(c) {
        if (!chart || !c) return;
        chart.data.labels = c.labels;
        if (metric === 'cpu') {
            chart.data.datasets[0].data = c.cpu;
        } else if (metric === 'ram') {
            chart.data.datasets[0].data = c.ram;
        } else if (metric === 'io') {
            chart.data.datasets[0].data = c.read;
            chart.data.datasets[1].data = c.write;
        }
        chart.update('none');
    }

    function buildChart() {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[]}');
        if (chart) chart.destroy();

        let datasets, tickCallback;

        if (metric === 'cpu') {
            datasets = [{
                label: 'CPU %', data: data.cpu || [],
                borderColor: 'rgb(168, 85, 247)', backgroundColor: 'rgba(168, 85, 247, 0.12)',
                borderWidth: 1.8, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true
            }];
            tickCallback = v => v + '%';
        } else if (metric === 'ram') {
            datasets = [{
                label: 'RAM', data: data.ram || [],
                borderColor: 'rgb(52, 211, 153)', backgroundColor: 'rgba(52, 211, 153, 0.12)',
                borderWidth: 1.8, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true
            }];
            tickCallback = v => fmtKb(v);
        } else { // io
            datasets = [
                {
                    label: 'read',  data: data.read  || [],
                    borderColor: 'rgb(34, 211, 238)', backgroundColor: 'rgba(34, 211, 238, 0.10)',
                    borderWidth: 1.6, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true
                },
                {
                    label: 'write', data: data.write || [],
                    borderColor: 'rgb(251, 146, 60)', backgroundColor: 'rgba(251, 146, 60, 0.10)',
                    borderWidth: 1.6, pointRadius: 0, fill: true, tension: 0.4, spanGaps: true
                },
            ];
            tickCallback = v => fmtBps(v);
        }

        chart = new Chart(canvas, {
            type: 'line',
            data: { labels: data.labels, datasets },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a', borderColor: '#3a3a3a', borderWidth: 1,
                        titleColor: '#e5e7eb', bodyColor: '#9ca3af',
                        titleFont: { family: 'monospace', size: 11 }, bodyFont: { family: 'monospace', size: 11 },
                        callbacks: {
                            label: ctx => {
                                if (metric === 'cpu') return ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + '%';
                                if (metric === 'ram') return ' ' + fmtKb(ctx.parsed.y);
                                return ' ' + ctx.dataset.label + ': ' + fmtBps(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' } },
                    y: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, callback: tickCallback }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' }, beginAtZero: true }
                }
            }
        });
    }

    buildChart();

    poller = window.createPoller({
        getUrl: () => {
            const { from, to } = getRange();
            if (!from || !to) return null;
            return `/poll/process-metrics?type=${metric}&name=${encodeURIComponent(name)}&from=${from}&to=${to}`;
        },
        intervalMs: getBucket(),
        onData: (d) => {
            updateCards(d);
            applyChartData(d.chartData);
        },
    });
    poller.start();

    document.addEventListener('livewire:navigating', () => poller.stop(), { once: true });

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'process-chart' && component.id === componentId) {
            buildChart();
            poller.setInterval(getBucket());
        }
    });
})();
</script>
@endscript
