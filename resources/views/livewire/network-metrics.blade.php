<div
    wire:key="network-metrics"
    data-bucket-seconds="{{ $bucketSeconds }}"
    data-label-format="{{ $labelFormat }}">

    @php
        $rxMbps = round($rxBytes * 8 / 60 / 1_000_000, 2);
        $txMbps = round($txBytes * 8 / 60 / 1_000_000, 2);
    @endphp

    {{-- Card 1: Latest RX / TX + Connections --}}
    <div class="grid grid-cols-4 gap-4 mb-4">

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">
                <span class="text-emerald-400">↓ Inbound (RX)</span>
            </p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-net-rx-mbps>{{ $rxMbps }}</span>
                <span class="text-sm font-normal text-neutral-400">Mbps</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">
                <span class="text-amber-400">↑ Outbound (TX)</span>
            </p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-net-tx-mbps>{{ $txMbps }}</span>
                <span class="text-sm font-normal text-neutral-400">Mbps</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Established</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-net-established>{{ $totalEstablished }}</span>
            </p>
        </div>

        <div class="rounded-lg border border-neutral-800 px-5 py-4">
            <p class="text-xs font-mono text-neutral-500 uppercase tracking-widest mb-1">Closed / Other</p>
            <p class="text-2xl font-semibold text-neutral-100">
                <span data-net-closed-other>{{ $closedOther }}</span>
            </p>
        </div>

    </div>

    {{-- Card 2: Bandwidth chart --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-mono font-semibold text-neutral-200">
                Bandwidth &middot; <span data-net-period>{{ $periodLabel }}</span>
            </p>
            <div class="flex items-center gap-4 text-[11px] font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-emerald-400"></span>
                    <span class="text-neutral-400">RX</span>
                    <span class="text-emerald-400"><span data-net-rx-mbps-legend>{{ $rxMbps }}</span> Mbps</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-sm bg-amber-400"></span>
                    <span class="text-neutral-400">TX</span>
                    <span class="text-amber-400"><span data-net-tx-mbps-legend>{{ $txMbps }}</span> Mbps</span>
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

    {{-- Card 3: Connections by IP --}}
    <div class="rounded-lg border border-neutral-800 px-5 pt-4 pb-3 mt-4">
        <p class="text-sm font-mono font-semibold text-neutral-200 mb-1">Connections by IP</p>
        <p class="text-[11px] font-mono text-neutral-500 mb-4">
            spot hosts with abnormal closed / half-open ratios — potential spam, scan, or stuck client
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-xs font-mono">
                <thead class="text-[11px] uppercase tracking-widest text-neutral-500 border-b border-neutral-800">
                    <tr>
                        <th class="text-left py-2 pr-4">Local IP</th>
                        <th class="text-right py-2 px-4">Total</th>
                        <th class="text-right py-2 px-4">Established</th>
                        <th class="text-right py-2 px-4">Closed</th>
                        <th class="text-right py-2 px-4">Other</th>
                        <th class="text-left py-2 pl-4 w-64">State distribution</th>
                    </tr>
                </thead>
                <tbody data-conn-tbody class="text-neutral-200">
                    @forelse($byIp as $ip)
                        @php
                            $total = max(1, $ip['total']);
                            $pctEst    = round($ip['established'] / $total * 100);
                            $pctClosed = round($ip['closed']      / $total * 100);
                            $pctOther  = round($ip['other']       / $total * 100);
                        @endphp
                        <tr class="border-b border-neutral-800/50">
                            <td class="py-3 pr-4">{{ $ip['ip'] }}</td>
                            <td class="py-3 px-4 text-right">{{ $ip['total'] }}</td>
                            <td class="py-3 px-4 text-right text-emerald-400">{{ $ip['established'] }}</td>
                            <td class="py-3 px-4 text-right text-neutral-400">{{ $ip['closed'] }}</td>
                            <td class="py-3 px-4 text-right text-amber-400">{{ $ip['other'] }}</td>
                            <td class="py-3 pl-4">
                                <div class="flex h-1.5 rounded overflow-hidden bg-neutral-900">
                                    <div class="bg-emerald-400" style="width: {{ $pctEst }}%"></div>
                                    <div class="bg-neutral-500" style="width: {{ $pctClosed }}%"></div>
                                    <div class="bg-amber-400"   style="width: {{ $pctOther }}%"></div>
                                </div>
                                <div class="flex gap-3 mt-1.5 text-[10px]">
                                    <span class="text-emerald-400">● {{ $pctEst }}%</span>
                                    <span class="text-neutral-400">● {{ $pctClosed }}%</span>
                                    <span class="text-amber-400">● {{ $pctOther }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-neutral-500">No connection data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@script
<script>
(function () {
    const id          = 'network-chart-{{ $this->getId() }}';
    const componentId = '{{ $this->getId() }}';
    let chart   = null;
    let pending = null; // { start, rxSum, rxCount, txSum, txCount }

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

    function bytesToMbps(b) {
        return Math.round(b * 8 / 60 / 1_000_000 * 100) / 100;
    }

    function updateCards(rxBytes, txBytes) {
        const rxMbps = bytesToMbps(rxBytes);
        const txMbps = bytesToMbps(txBytes);
        setText('[data-net-rx-mbps]',        rxMbps);
        setText('[data-net-tx-mbps]',        txMbps);
        setText('[data-net-rx-mbps-legend]', rxMbps);
        setText('[data-net-tx-mbps-legend]', txMbps);
    }

    function shiftAndPush(label, rxValue, txValue) {
        chart.data.labels.shift();
        chart.data.labels.push(label);
        chart.data.datasets[0].data.shift();
        chart.data.datasets[0].data.push(rxValue);
        chart.data.datasets[1].data.shift();
        chart.data.datasets[1].data.push(txValue);
        chart.update('none');

        const first = chart.data.labels[0];
        const last  = chart.data.labels[chart.data.labels.length - 1];
        setText('[data-net-period]', `${first} – ${last}`);
    }

    function buildChart() {
        const canvas = document.getElementById(id);
        if (!canvas) return;
        const data = JSON.parse(canvas.dataset.chart || '{"labels":[],"rx":[],"tx":[]}');
        if (chart) chart.destroy();

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

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function rebuildConnTable(byIp) {
        const tbody = getRoot()?.querySelector('[data-conn-tbody]');
        if (!tbody || !Array.isArray(byIp)) return;

        if (byIp.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="py-4 text-center text-neutral-500">No connection data</td></tr>';
            return;
        }

        const html = byIp.map(row => {
            const total = Math.max(1, row.total);
            const pctEst    = Math.round(row.established / total * 100);
            const pctClosed = Math.round(row.closed      / total * 100);
            const pctOther  = Math.round(row.other       / total * 100);
            return `
                <tr class="border-b border-neutral-800/50">
                    <td class="py-3 pr-4">${escapeHtml(row.ip)}</td>
                    <td class="py-3 px-4 text-right">${row.total}</td>
                    <td class="py-3 px-4 text-right text-emerald-400">${row.established}</td>
                    <td class="py-3 px-4 text-right text-neutral-400">${row.closed}</td>
                    <td class="py-3 px-4 text-right text-amber-400">${row.other}</td>
                    <td class="py-3 pl-4">
                        <div class="flex h-1.5 rounded overflow-hidden bg-neutral-900">
                            <div class="bg-emerald-400" style="width: ${pctEst}%"></div>
                            <div class="bg-neutral-500" style="width: ${pctClosed}%"></div>
                            <div class="bg-amber-400"   style="width: ${pctOther}%"></div>
                        </div>
                        <div class="flex gap-3 mt-1.5 text-[10px]">
                            <span class="text-emerald-400">● ${pctEst}%</span>
                            <span class="text-neutral-400">● ${pctClosed}%</span>
                            <span class="text-amber-400">● ${pctOther}%</span>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tbody.innerHTML = html;
    }

    function onEvent(e) {
        if (e.type === 'connections') {
            setText('[data-net-established]',  e.payload.established);
            setText('[data-net-closed-other]', e.payload.closed_other);
            rebuildConnTable(e.payload.by_ip);
            return;
        }

        if (e.type !== 'network') return;
        if (!isLive()) return;

        const { bucketSeconds, labelFormat } = getConfig();
        if (bucketSeconds <= 0) return;

        const ts = e.collectedAt;
        const rx = e.payload.rx_bytes;
        const tx = e.payload.tx_bytes;

        updateCards(rx, tx);

        const bucketStart = Math.floor(ts / bucketSeconds) * bucketSeconds;

        if (pending && pending.start !== bucketStart) {
            const rxAvg = pending.rxSum / pending.rxCount;
            const txAvg = pending.txSum / pending.txCount;
            shiftAndPush(
                formatLabel(pending.start, labelFormat),
                bytesToMbps(rxAvg),
                bytesToMbps(txAvg)
            );
            pending = null;
        }

        if (!pending) {
            pending = { start: bucketStart, rxSum: 0, rxCount: 0, txSum: 0, txCount: 0 };
        }
        pending.rxSum   += rx;
        pending.rxCount += 1;
        pending.txSum   += tx;
        pending.txCount += 1;
    }

    if (window.Echo) {
        window.Echo.channel('metrics').listen('.MetricCollected', onEvent);
    }

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.name === 'network-metrics') {
            buildChart();
            pending = null;
        }
    });
})();
</script>
@endscript
