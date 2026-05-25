<?php

namespace App\Services\Monitoring;

use App\Models\CpuMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CpuMetricsQuery
{
    public function snapshot(int $fromTs, int $toTs): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = max(1, $toTs - $fromTs);

        $latest = CpuMetric::orderByDesc('collected_at')->first();

        $totalUsage  = $latest?->total_usage  ?? 0;
        $stolenUsage = $latest?->stolen_usage ?? 0;
        $perCore     = $latest?->per_core_usage ?? [];
        $coreCount   = count($perCore);
        $coresAvg    = $coreCount > 0 ? round(array_sum($perCore) / $coreCount, 1) : 0;

        $bucketSeconds = BucketResolver::secondsFor($diffSeconds);
        $labelFormat   = BucketResolver::labelFormat($bucketSeconds, $diffSeconds);

        $periodLabelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($toTs, $tz)->format($periodLabelFormat);

        $chartData = $this->buildChart($fromTs, $toTs, $bucketSeconds, $labelFormat);

        return [
            'totalUsage'    => $totalUsage,
            'stolenUsage'   => $stolenUsage,
            'coresAvg'      => $coresAvg,
            'coreCount'     => $coreCount,
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
            return ['labels' => [], 'total' => [], 'coresAvg' => [], 'stolen' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        // For coresAvg we need the per-row mean of the per_core_usage JSON array.
        // The correlated subquery `(SELECT AVG(value) FROM json_each(per_core_usage))`
        // unpacks the JSON per row and averages its elements, regardless of core count.
        $bucketRows = DB::connection('system_metrics')
            ->table('cpu_metrics')
            ->selectRaw(
                '((collected_at - ?) / ?) * ? + ? AS bucket_ts,
                 AVG(total_usage)  AS avg_total,
                 AVG(stolen_usage) AS avg_stolen,
                 AVG((SELECT AVG(value) FROM json_each(per_core_usage))) AS avg_cores',
                [$fromTs, $bucketSeconds, $bucketSeconds, $fromTs]
            )
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->groupBy('bucket_ts')
            ->get()
            ->keyBy('bucket_ts');

        $labels   = [];
        $total    = [];
        $coresAvg = [];
        $stolen   = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $ts  = $fromTs + $i * $bucketSeconds;
            $row = $bucketRows->get($ts);

            $labels[] = Carbon::createFromTimestamp($ts, $tz)->format($labelFormat);
            if ($row !== null) {
                $total[]    = round($row->avg_total,  2);
                $coresAvg[] = round($row->avg_cores,  2);
                $stolen[]   = round($row->avg_stolen, 2);
            } else {
                $total[]    = 0;
                $coresAvg[] = 0;
                $stolen[]   = 0;
            }
        }

        return ['labels' => $labels, 'total' => $total, 'coresAvg' => $coresAvg, 'stolen' => $stolen];
    }
}
