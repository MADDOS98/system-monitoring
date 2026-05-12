<div wire:key="ram-metrics">

    {{-- Card 1: Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-4">

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Total</p>
            <p class="text-2xl font-semibold text-neutral-100">
                {{ round($totalKb / (1024 * 1024), 2) }}
                <span class="text-sm font-normal text-neutral-400">GB</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Used</p>
            <p class="text-2xl font-semibold text-neutral-100">
                {{ round($usedKb / (1024 * 1024), 2) }}
                <span class="text-sm font-normal text-neutral-400">GB</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Usage</p>
            <p class="text-2xl font-semibold text-neutral-100">
                {{ $usedPct }}<span class="text-sm font-normal text-neutral-400">%</span>
            </p>
        </div>

    </div>

    {{-- Card 2: Progress bar --}}
    <div class="rounded-lg border border-neutral-800 px-5 py-4 mb-4">
        <div class="flex items-center justify-between mb-2">
            <p class="text-xs font-mono text-neutral-400">Current pressure</p>
            <p class="text-xs font-mono {{ $usedPct >= 75 ? 'text-red-400' : 'text-blue-400' }}">
                {{ round($usedKb / (1024 * 1024), 2) }} GB / {{ round($totalKb / (1024 * 1024), 2) }} GB
            </p>
        </div>

        <div class="w-full bg-gray-200 rounded-full dark:bg-gray-700">
            <div
                class="{{ $usedPct >= 75 ? 'bg-red-600' : 'bg-blue-600' }} text-xs font-medium text-blue-100 text-center p-0.5 leading-none rounded-full"
                style="width: {{ min(100, $usedPct) }}%"> {{ $usedPct }}%</div>
        </div>

        <div class="flex items-center justify-between mt-2">
            <span class="text-[11px] font-mono {{ $usedPct >= 75 ? 'text-red-400' : 'text-neutral-400' }}">
                {{ $usedPct >= 75 ? 'High pressure' : 'Normal' }}
            </span>
            <span class="text-[11px] font-mono text-neutral-500">
                Free: {{ round($freeKb / (1024 * 1024), 2) }} GB
            </span>
        </div>
    </div>

    {{-- Card 3: Chart --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-1">
            <p class="text-xs font-mono font-semibold text-neutral-200">Memory usage</p>
            <p class="text-[11px] font-mono text-blue-400">
                used {{ round($usedKb / (1024 * 1024), 2) }} GB
            </p>
        </div>
        <p class="text-[11px] font-mono text-neutral-500 mb-3">
            used of {{ round($totalKb / (1024 * 1024), 2) }} GB &middot; {{ $periodLabel }}
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
        const id = 'ram-chart-{{ $this->getId() }}';
        let chart = null;

        function buildChart() {
            const canvas = document.getElementById(id);
            if (!canvas) return;

            const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"values":[]}');

            if (chart) {
                chart.destroy();
            }

            chart = new Chart(canvas, {
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
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1a1a1a',
                            borderColor: '#3a3a3a',
                            borderWidth: 1,
                            titleColor: '#e5e7eb',
                            bodyColor: '#9ca3af',
                            titleFont: {
                                family: 'monospace',
                                size: 11
                            },
                            bodyFont: {
                                family: 'monospace',
                                size: 11
                            },
                            callbacks: {
                                label: ctx => ' ' + ctx.parsed.y + ' GB'
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    family: 'monospace',
                                    size: 11
                                },
                                maxTicksLimit: 8
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.04)'
                            },
                            border: {
                                color: '#2a2a2a'
                            },
                        },
                        y: {
                            ticks: {
                                color: '#6b7280',
                                font: {
                                    family: 'monospace',
                                    size: 11
                                }
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.04)'
                            },
                            border: {
                                color: '#2a2a2a'
                            },
                        }
                    }
                }
            });
        }

        buildChart();

        // Reconstruim chart-ul de fiecare data cand componenta primeste update de la server
        Livewire.hook('morph.updated', ({
            el,
            component
        }) => {
            if (component.name === 'ram-metrics') {
                buildChart();
            }
        });
    })();
</script>
@endscript