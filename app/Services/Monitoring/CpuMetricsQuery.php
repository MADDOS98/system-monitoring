<?php

namespace App\Services\Monitoring;

use App\Models\CpuMetric;
use Carbon\Carbon;

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

        $rows = CpuMetric::whereBetween('collected_at', [$fromTs, $toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'total_usage', 'per_core_usage', 'stolen_usage']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [
                'totalSum'    => 0,
                'coresAvgSum' => 0,
                'stolenSum'   => 0,
                'count'       => 0,
                'ts'          => $fromTs + $i * $bucketSeconds,
            ];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $fromTs;
            $key    = (int) floor($offset / $bucketSeconds);
            if (!isset($buckets[$key])) {
                continue;
            }
            $cores    = $row->per_core_usage;
            $coresAvg = !empty($cores) ? array_sum($cores) / count($cores) : 0;

            $buckets[$key]['totalSum']    += $row->total_usage;
            $buckets[$key]['coresAvgSum'] += $coresAvg;
            $buckets[$key]['stolenSum']   += $row->stolen_usage;
            $buckets[$key]['count']       += 1;
        }

        $labels   = [];
        $total    = [];
        $coresAvg = [];
        $stolen   = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            if ($b['count'] > 0) {
                $total[]    = round($b['totalSum']    / $b['count'], 2);
                $coresAvg[] = round($b['coresAvgSum'] / $b['count'], 2);
                $stolen[]   = round($b['stolenSum']   / $b['count'], 2);
            } else {
                $total[]    = 0;
                $coresAvg[] = 0;
                $stolen[]   = 0;
            }
        }

        return ['labels' => $labels, 'total' => $total, 'coresAvg' => $coresAvg, 'stolen' => $stolen];
    }
}
