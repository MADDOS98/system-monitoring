<div wire:key="network-metrics">

    @php
        $rxMbps = round($rxBytes * 8 / 60 / 1_000_000, 2);
        $txMbps = round($txBytes * 8 / 60 / 1_000_000, 2);
    @endphp

    {{-- Card 1: Latest RX / TX --}}
    <div class="grid grid-cols-2 gap-4 mb-4">

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 mb-1">
                <span class="text-emerald-400">↓ RX</span>
            </p>
            <p class="text-2xl font-semibold text-neutral-100">
                {{ $rxMbps }}
                <span class="text-sm font-normal text-neutral-400">Mbps</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 mb-1">
                <span class="text-amber-400">↑ TX</span>
            </p>
            <p class="text-2xl font-semibold text-neutral-100">
                {{ $txMbps }}
                <span class="text-sm font-normal text-neutral-400">Mbps</span>
            </p>
        </div>

    </div>

    {{-- Card 2: Bandwidth chart --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-mono font-semibold text-neutral-200">
                Bandwidth &middot; {{ $periodLabel }}
            </p>
            <div class="flex items-center gap-4 text-[11px] font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-emerald-400"></span>
                    <span class="text-neutral-400">RX</span>
                    <span class="text-emerald-400">{{ $rxMbps }} Mbps</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-amber-400"></span>
                    <span class="text-neutral-400">TX</span>
                    <span class="text-amber-400">{{ $txMbps }} Mbps</span>
                </span>
            </div>
        </div>

        <div style="height: 280px">
            <canvas
                id="network-chart-{{ $this->getId() }}"
                data-chart='@json($chartData)'
                style="width:100%;height:100%"></canvas>
        </div>
    </div>

</div>

@script
<script>
    (function () {
        const id  = 'network-chart-{{ $this->getId() }}';
        let chart = null;

        function buildChart() {
            const canvas = document.getElementById(id);
            if (!canvas) return;

            const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"rx":[],"tx":[]}');

            if (chart) { chart.destroy(); }

            chart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'RX',
                            data: data.rx,
                            borderColor: 'rgb(52, 211, 153)',
                            backgroundColor: 'rgba(52, 211, 153, 0.08)',
                            borderWidth: 1.5,
                            pointRadius: 0,
                            fill: true,
                            tension: 0.4,
                            spanGaps: true,
                        },
                        {
                            label: 'TX',
                            data: data.tx,
                            borderColor: 'rgb(251, 191, 36)',
                            backgroundColor: 'rgba(251, 191, 36, 0.08)',
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
                                label: ctx => ' ' + ctx.dataset.label + ': ' + ctx.parsed.y + ' Mbps'
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

        buildChart();

        Livewire.hook('morph.updated', ({ component }) => {
            if (component.name === 'network-metrics') {
                buildChart();
            }
        });
    })();
</script>
@endscript
