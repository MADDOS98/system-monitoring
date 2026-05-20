<?php

namespace App\Services\Monitoring;

use App\Models\RamMetric;
use Carbon\Carbon;

class RamMetricsQuery
{
    public function snapshot(int $fromTs, int $toTs): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = max(1, $toTs - $fromTs);

        $latest = RamMetric::orderByDesc('collected_at')->first();

        $totalKb = $latest?->total_kb ?? 0;
        $usedKb  = $latest?->used_kb  ?? 0;
        $freeKb  = $totalKb - $usedKb;
        $usedPct = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0;

        $bucketSeconds = BucketResolver::secondsFor($diffSeconds);
        $labelFormat   = BucketResolver::labelFormat($bucketSeconds, $diffSeconds);

        $periodLabelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($toTs, $tz)->format($periodLabelFormat);

        $chartData = $this->buildChart($fromTs, $toTs, $bucketSeconds, $labelFormat);

        return [
            'totalKb'       => $totalKb,
            'usedKb'        => $usedKb,
            'freeKb'        => $freeKb,
            'usedPct'       => $usedPct,
            'chartData'     => $chartData,
            'periodLabel'   => $periodLabel,
            'bucketSeconds' => $bucketSeconds,
            'labelFormat'   => $labelFormat,
        ];
    }

    private function buildChart(int $fromTs, int $toTs, int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $toTs - $fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'values' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = RamMetric::whereBetween('collected_at', [$fromTs, $toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'used_kb']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [
                'sum'   => 0,
                'count' => 0,
                'ts'    => $fromTs + $i * $bucketSeconds,
            ];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $fromTs;
            $key    = (int) floor($offset / $bucketSeconds);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['sum']   += $row->used_kb;
            $buckets[$key]['count'] += 1;
        }

        $labels = [];
        $values = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            $values[] = $b['count'] > 0
                ? round($b['sum'] / $b['count'] / (1024 * 1024), 2)
                : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
