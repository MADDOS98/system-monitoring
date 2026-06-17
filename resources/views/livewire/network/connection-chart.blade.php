<div wire:key="connection-chart-{{ $key }}"
     data-bucket-seconds="{{ $bucketSeconds }}"
     data-label-format="{{ $labelFormat }}"
     data-from-ts="{{ $this->fromTs }}"
     data-to-ts="{{ $this->toTs }}"
     data-name="{{ $this->name }}">

    {{-- Stat cards --}}
    <div class="grid grid-cols-3 gap-4 mb-4">

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Current</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-stat-current>{{ $currentValue }}</span>
                <span class="text-sm font-normal text-neutral-400">conn</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Avg in window</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-stat-avg>{{ $avgValue }}</span>
                <span class="text-sm font-normal text-neutral-400">conn</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Peak</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-stat-peak>{{ $maxValue }}</span>
                <span class="text-sm font-normal text-neutral-400">conn</span>
            </p>
        </div>

    </div>

    {{-- Chart card --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-mono font-semibold text-neutral-200">
                Total connections &middot; <span data-period>{{ $periodLabel }}</span>
            </p>
            <div class="flex items-center gap-4 text-[11px] font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-emerald-400"></span>
                    <span class="text-neutral-400">connections</span>
                </span>
            </div>
        </div>

        <div style="height: 320px">
            <canvas id="connection-chart-{{ $this->getId() }}"
                    data-chart='@json($chartData)'
                    style="width:100%;height:100%"></canvas>
        </div>
    </div>

    {{-- Lista IPs din grup (afisata doar pentru grupuri, simpla tabela) --}}
    @if($is_group && ! empty($perIp))
        <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3 mt-4">
            <p class="text-xs font-mono font-semibold text-neutral-200 mb-1">
                IPs in group
                <span class="text-neutral-500 font-normal">&middot; {{ count($perIp) }} {{ count($perIp) === 1 ? 'address' : 'addresses' }}</span>
            </p>
            <p class="text-[11px] font-mono text-neutral-500 mb-4">
                breakdown by IP at latest sample
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-xs font-mono">
                    <thead class="text-[11px] uppercase tracking-widest text-neutral-500 border-b border-neutral-800">
                        <tr>
                            <th class="text-left py-2 pr-4">IP</th>
                            <th class="text-right py-2 px-4">Total connections</th>
                        </tr>
                    </thead>
                    <tbody class="text-neutral-200">
                        @foreach($perIp as $row)
                            <tr class="border-b border-neutral-800/50 last:border-b-0">
                                <td class="py-3 pr-4">{{ $row['ip'] }}</td>
                                <td class="py-3 px-4 text-right">{{ $row['total_connections'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    const canvasId    = 'connection-chart-' + componentId;
    const name        = @json($this->name);
    const PRESET_MINUTES = { '5m': 5, '1h': 60, '24h': 1440 };

    let chart  = null;
    let poller = null;

    function getRoot()   { return document.querySelector('[wire\\:id="' + componentId + '"]'); }
    function getBucket() { return Math.max(1, parseInt(getRoot()?.dataset.bucketSeconds || '60', 10)) * 1000; }

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

    function buildChart() {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"values":[]}');
        if (chart) chart.destroy();

        chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'connections',
                    data: data.values,
                    borderColor: 'rgb(52, 211, 153)',
                    backgroundColor: 'rgba(52, 211, 153, 0.12)',
                    borderWidth: 1.8,
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
                        backgroundColor: '#1a1a1a', borderColor: '#3a3a3a', borderWidth: 1,
                        titleColor: '#e5e7eb', bodyColor: '#9ca3af',
                        titleFont: { family: 'monospace', size: 11 }, bodyFont: { family: 'monospace', size: 11 },
                        callbacks: { label: ctx => ' ' + ctx.parsed.y + ' connections' }
                    }
                },
                scales: {
                    x: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, maxTicksLimit: 8 }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' } },
                    y: { ticks: { color: '#6b7280', font: { family: 'monospace', size: 11 }, precision: 0 }, grid: { color: 'rgba(255,255,255,0.04)' }, border: { color: '#2a2a2a' }, beginAtZero: true }
                }
            }
        });
    }

    function applyData(d) {
        if (!chart || !d?.chartData) return;
        chart.data.labels           = d.chartData.labels;
        chart.data.datasets[0].data = d.chartData.values;
        chart.update('none');

        setText('[data-stat-current]', d.currentValue);
        setText('[data-stat-avg]',     d.avgValue);
        setText('[data-stat-peak]',    d.maxValue);
        setText('[data-period]',       d.periodLabel);
    }

    buildChart();

    poller = window.createPoller({
        getUrl: () => {
            const { from, to } = getRange();
            if (!from || !to) return null;
            return `/poll/connection?name=${encodeURIComponent(name)}&from=${from}&to=${to}`;
        },
        intervalMs: getBucket(),
        onData: (d) => applyData(d),
    });
    poller.start();

    document.addEventListener('livewire:navigating', () => poller.stop(), { once: true });

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'network.connection-chart' && component.id === componentId) {
            buildChart();
            poller.setInterval(getBucket());
        }
    });
})();
</script>
@endscript
