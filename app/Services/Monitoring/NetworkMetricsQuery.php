<?php

namespace App\Services\Monitoring;

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

        $latest = NetworkMetric::orderByDesc('collected_at')->first();

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

        usort($byIp, fn($a, $b) => $b['total'] <=> $a['total']);
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
