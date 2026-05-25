<?php

namespace App\Services\Monitoring;

use App\Models\DiskIoMetric;
use App\Models\DiskUsageMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DiskMetricsQuery
{
    public function snapshot(int $fromTs, int $toTs): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = max(1, $toTs - $fromTs);

        $latestIo    = DiskIoMetric::orderByDesc('collected_at')->first();
        $latestUsage = DiskUsageMetric::orderByDesc('collected_at')->first();

        $readBytes  = $latestIo?->read_bytes  ?? 0;
        $writeBytes = $latestIo?->write_bytes ?? 0;

        $totalBytes = $latestUsage?->total_bytes ?? 0;
        $usedBytes  = $latestUsage?->used_bytes  ?? 0;
        $freeBytes  = $totalBytes - $usedBytes;
        $usedPct    = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0;

        $bucketSecondsIo    = BucketResolver::secondsFor($diffSeconds);
        $bucketSecondsUsage = BucketResolver::secondsForMinutely($diffSeconds);
        $labelFormatIo      = BucketResolver::labelFormat($bucketSecondsIo, $diffSeconds);
        $labelFormatUsage   = BucketResolver::labelFormat($bucketSecondsUsage, $diffSeconds);

        $periodLabelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($toTs, $tz)->format($periodLabelFormat);

        $ioChartData    = $this->buildIoChart($fromTs, $toTs, $bucketSecondsIo, $labelFormatIo);
        $usageChartData = $this->buildUsageChart($fromTs, $toTs, $bucketSecondsUsage, $labelFormatUsage);

        return [
            'readBytes'          => $readBytes,
            'writeBytes'         => $writeBytes,
            'totalBytes'         => $totalBytes,
            'usedBytes'          => $usedBytes,
            'freeBytes'          => $freeBytes,
            'usedPct'            => $usedPct,
            'ioChartData'        => $ioChartData,
            'usageChartData'     => $usageChartData,
            'periodLabel'        => $periodLabel,
            'bucketSecondsIo'    => $bucketSecondsIo,
            'bucketSecondsUsage' => $bucketSecondsUsage,
            'labelFormatIo'      => $labelFormatIo,
            'labelFormatUsage'   => $labelFormatUsage,
        ];
    }

    private function buildIoChart(int $fromTs, int $toTs, int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $toTs - $fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'read' => [], 'write' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $bucketRows = DB::connection('system_metrics')
            ->table('disk_io_metrics')
            ->selectRaw(
                '((collected_at - ?) / ?) * ? + ? AS bucket_ts, AVG(read_bytes) AS avg_read, AVG(write_bytes) AS avg_write',
                [$fromTs, $bucketSeconds, $bucketSeconds, $fromTs]
            )
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->groupBy('bucket_ts')
            ->get()
            ->keyBy('bucket_ts');

        $labels = []; $read = []; $write = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $ts  = $fromTs + $i * $bucketSeconds;
            $row = $bucketRows->get($ts);

            $labels[] = Carbon::createFromTimestamp($ts, $tz)->format($labelFormat);
            if ($row !== null) {
                $read[]  = round($row->avg_read  / 60 / 1_000_000, 2);
                $write[] = round($row->avg_write / 60 / 1_000_000, 2);
            } else {
                $read[]  = 0;
                $write[] = 0;
            }
        }

        return ['labels' => $labels, 'read' => $read, 'write' => $write];
    }

    private function buildUsageChart(int $fromTs, int $toTs, int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $toTs - $fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'values' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $bucketRows = DB::connection('system_metrics')
            ->table('disk_usage_metrics')
            ->selectRaw(
                '((collected_at - ?) / ?) * ? + ? AS bucket_ts, AVG(used_bytes) AS avg_used',
                [$fromTs, $bucketSeconds, $bucketSeconds, $fromTs]
            )
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->groupBy('bucket_ts')
            ->get()
            ->keyBy('bucket_ts');

        $labels = []; $values = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $ts  = $fromTs + $i * $bucketSeconds;
            $row = $bucketRows->get($ts);

            $labels[] = Carbon::createFromTimestamp($ts, $tz)->format($labelFormat);
            $values[] = $row !== null
                ? round($row->avg_used / (1024 * 1024 * 1024), 2)
                : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
