<?php

namespace App\Services\Monitoring;

use App\Models\ConnectionIpGroup;
use App\Models\ConnectionMetric;
use App\Models\NetworkMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NetworkMetricsQuery
{
    public function snapshot(int $fromTs, int $toTs): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = max(1, $toTs - $fromTs);

        // MAX(id) e direct pe PK (B-tree), mai rapid decat MAX(collected_at) si
        // semantic identic (id e autoincrement, ordinea inserarii = ordinea cronologica).
        $latest = NetworkMetric::orderByDesc('id')->first();

        $rxBytes = $latest?->rx_bytes ?? 0;
        $txBytes = $latest?->tx_bytes ?? 0;

        // Connection metrics — ultima inregistrare comuna pentru toate IP-urile
        $latestConnTs = ConnectionMetric::max('collected_at') ?? 0;
        $connRows     = $latestConnTs > 0
            ? ConnectionMetric::where('collected_at', $latestConnTs)->get()
            : collect();

        $totalEstablished = 0;
        $totalClosed      = 0;
        $totalOther       = 0;
        $byIp             = [];

        foreach ($connRows as $row) {
            $cat = $this->categorizeStates($row->state_counts ?? []);
            $totalEstablished += $cat['established'];
            $totalClosed      += $cat['closed'];
            $totalOther       += $cat['other'];
            $byIp[] = [
                'ip'          => $row->local_ip,
                'total'       => $row->total_connections,
                'established' => $cat['established'],
                'closed'      => $cat['closed'],
                'other'       => $cat['other'],
            ];
        }

        // Aplicam gruparea pe baza tabelei connection_ip_groups.
        $byIp = $this->applyGrouping($byIp);
        $closedOther = $totalClosed + $totalOther;

        $bucketSeconds = BucketResolver::secondsFor($diffSeconds);
        $labelFormat   = BucketResolver::labelFormat($bucketSeconds, $diffSeconds);

        $periodLabelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($toTs, $tz)->format($periodLabelFormat);

        $chartData = $this->buildChart($fromTs, $toTs, $bucketSeconds, $labelFormat);

        return [
            'rxBytes'          => $rxBytes,
            'txBytes'          => $txBytes,
            'chartData'        => $chartData,
            'periodLabel'      => $periodLabel,
            'bucketSeconds'    => $bucketSeconds,
            'labelFormat'      => $labelFormat,
            'totalEstablished' => $totalEstablished,
            'closedOther'      => $closedOther,
            'byIp'             => $byIp,
        ];
    }

    /**
     * Aplica maparea ip → group_name din connection_ip_groups.
     * IP-urile cu grup se agregheaza intr-un singur rand (sum total/est/closed/other).
     * IP-urile fara grup raman individuale.
     *
     * Returnam fields suplimentare:
     *  - key:      cheia URL pentru pagina de detaliu (group_name sau ip)
     *  - display:  ce afisam in tabela (group_name sau ip)
     *  - is_group: bool, true daca rand-ul reprezinta un grup agregat
     *  - ips:      array cu IP-urile incluse (1 element pentru non-grup)
     */
    private function applyGrouping(array $byIp): array
    {
        $groupMap = ConnectionIpGroup::pluck('group_name', 'ip')->toArray();

        $grouped = [];
        foreach ($byIp as $row) {
            $isGroup = isset($groupMap[$row['ip']]);
            $key     = $isGroup ? $groupMap[$row['ip']] : $row['ip'];

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'key'         => $key,
                    'display'     => $key,
                    'is_group'    => $isGroup,
                    'total'       => 0,
                    'established' => 0,
                    'closed'      => 0,
                    'other'       => 0,
                    'ips'         => [],
                ];
            }

            $grouped[$key]['total']       += $row['total'];
            $grouped[$key]['established'] += $row['established'];
            $grouped[$key]['closed']      += $row['closed'];
            $grouped[$key]['other']       += $row['other'];
            $grouped[$key]['ips'][]       = $row['ip'];
        }

        $result = array_values($grouped);
        usort($result, fn ($a, $b) => $b['total'] <=> $a['total']);
        return $result;
    }

    /**
     * Snapshot pentru pagina de detaliu /network/connections?key=...
     * Returneaza chart data (total_connections in timp), plus stats
     * (current/min/max/avg) si lista IP-urilor agregate.
     */
    public function connectionSnapshot(string $key, int $fromTs, int $toTs): array
    {
        $tz   = config('app.timezone');
        $diff = max(1, $toTs - $fromTs);

        // Determinam ce IP-uri agregam: daca $key e un group_name, lista din grup;
        // altfel e un IP individual, lista cu un singur element.
        $groupIps = ConnectionIpGroup::where('group_name', $key)->pluck('ip')->toArray();
        $ips      = ! empty($groupIps) ? $groupIps : [$key];
        $isGroup  = ! empty($groupIps);

        $bucketSeconds = BucketResolver::secondsForMinutely($diff);
        $labelFormat   = BucketResolver::labelFormat($bucketSeconds, $diff);
        $bucketCount   = (int) ceil($diff / $bucketSeconds);

        // SUM(total_connections) per bucket pentru toate IP-urile date.
        $bucketRows = DB::connection('system_metrics')
            ->table('connection_metrics')
            ->selectRaw(
                '((collected_at - ?) / ?) * ? + ? AS bucket_ts, SUM(total_connections) AS sum_conn',
                [$fromTs, $bucketSeconds, $bucketSeconds, $fromTs]
            )
            ->whereIn('local_ip', $ips)
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->groupBy('bucket_ts')
            ->get()
            ->keyBy('bucket_ts');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $ts  = $fromTs + $i * $bucketSeconds;
            $row = $bucketRows->get($ts);
            $labels[] = Carbon::createFromTimestamp($ts, $tz)->format($labelFormat);
            $values[] = $row !== null ? (int) $row->sum_conn : 0;
        }

        $nonZero = array_filter($values);
        $currentValue = (int) end($values) ?: 0;
        $maxValue     = ! empty($values)  ? (int) max($values) : 0;
        $minValue     = ! empty($nonZero) ? (int) min($nonZero) : 0;
        $avgValue     = ! empty($nonZero) ? round(array_sum($nonZero) / count($nonZero), 1) : 0;

        $periodLabelFormat = $diff >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($toTs, $tz)->format($periodLabelFormat);

        // Pentru grupuri: lista IPs cu cel mai recent total_connections per IP
        // (util pentru a vedea distributia interna a grupului).
        $perIp = [];
        if ($isGroup && ! empty($ips)) {
            $latestPerIp = DB::connection('system_metrics')
                ->table('connection_metrics')
                ->selectRaw('local_ip, MAX(id) as max_id')
                ->whereIn('local_ip', $ips)
                ->groupBy('local_ip');

            $perIp = DB::connection('system_metrics')
                ->table('connection_metrics AS cm')
                ->joinSub($latestPerIp, 'latest', 'latest.max_id', '=', 'cm.id')
                ->select(['cm.local_ip', 'cm.total_connections', 'cm.collected_at'])
                ->orderByDesc('cm.total_connections')
                ->get()
                ->map(fn ($r) => [
                    'ip'                => $r->local_ip,
                    'total_connections' => (int) $r->total_connections,
                    'collected_at'      => (int) $r->collected_at,
                ])
                ->all();
        }

        return [
            'key'           => $key,
            'is_group'      => $isGroup,
            'ips'           => $ips,
            'perIp'         => $perIp,
            'chartData'     => ['labels' => $labels, 'values' => $values],
            'currentValue'  => $currentValue,
            'maxValue'      => $maxValue,
            'minValue'      => $minValue,
            'avgValue'      => $avgValue,
            'periodLabel'   => $periodLabel,
            'bucketSeconds' => $bucketSeconds,
            'labelFormat'   => $labelFormat,
        ];
    }

    private function categorizeStates(array $stateCounts): array
    {
        $closedStates = ['CLOSE', 'CLOSE_WAIT', 'TIME_WAIT', 'FIN_WAIT1', 'FIN_WAIT2', 'LAST_ACK', 'CLOSING'];

        $est = 0; $closed = 0; $other = 0;
        foreach ($stateCounts as $state => $count) {
            if ($state === 'ESTABLISHED') {
                $est += $count;
            } elseif (in_array($state, $closedStates, true)) {
                $closed += $count;
            } else {
                $other += $count;
            }
        }
        return ['established' => $est, 'closed' => $closed, 'other' => $other];
    }

    private function buildChart(int $fromTs, int $toTs, int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $toTs - $fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'rx' => [], 'tx' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $bucketRows = DB::connection('system_metrics')
            ->table('network_metrics')
            ->selectRaw(
                '((collected_at - ?) / ?) * ? + ? AS bucket_ts, AVG(rx_bytes) AS avg_rx, AVG(tx_bytes) AS avg_tx',
                [$fromTs, $bucketSeconds, $bucketSeconds, $fromTs]
            )
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->groupBy('bucket_ts')
            ->get()
            ->keyBy('bucket_ts');

        $labels = [];
        $rx     = [];
        $tx     = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $ts  = $fromTs + $i * $bucketSeconds;
            $row = $bucketRows->get($ts);

            $labels[] = Carbon::createFromTimestamp($ts, $tz)->format($labelFormat);
            if ($row !== null) {
                $rx[] = round($row->avg_rx * 8 / 60 / 1_000_000, 2);
                $tx[] = round($row->avg_tx * 8 / 60 / 1_000_000, 2);
            } else {
                $rx[] = 0;
                $tx[] = 0;
            }
        }

        return ['labels' => $labels, 'rx' => $rx, 'tx' => $tx];
    }
}
