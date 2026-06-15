<?php

namespace App\Services\Monitoring;

use App\Models\RamMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RamMetricsQuery
{
    public function snapshot(int $fromTs, int $toTs): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = max(1, $toTs - $fromTs);

        // MAX(id) e direct pe PK (B-tree), mai rapid decat MAX(collected_at) si
        // semantic identic (id e autoincrement, ordinea inserarii = ordinea cronologica).
        $latest = RamMetric::orderByDesc('id')->first();

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

        // Bucketize in SQL: same formula as the previous PHP loop —
        // bucket_ts = floor((collected_at - fromTs) / bucketSec) * bucketSec + fromTs.
        $bucketRows = DB::connection('system_metrics')
            ->table('ram_metrics')
            ->selectRaw(
                '((collected_at - ?) / ?) * ? + ? AS bucket_ts, AVG(used_kb) AS avg_used_kb',
                [$fromTs, $bucketSeconds, $bucketSeconds, $fromTs]
            )
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
            $values[] = $row !== null
                ? round($row->avg_used_kb / (1024 * 1024), 2)
                : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
