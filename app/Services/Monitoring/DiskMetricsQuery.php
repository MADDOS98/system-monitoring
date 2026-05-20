<?php

namespace App\Services\Monitoring;

use App\Models\DiskIoMetric;
use App\Models\DiskUsageMetric;
use Carbon\Carbon;

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

        $rows = DiskIoMetric::whereBetween('collected_at', [$fromTs, $toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'read_bytes', 'write_bytes']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = ['readSum' => 0, 'writeSum' => 0, 'count' => 0, 'ts' => $fromTs + $i * $bucketSeconds];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $fromTs;
            $key    = (int) floor($offset / $bucketSeconds);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['readSum']  += $row->read_bytes;
            $buckets[$key]['writeSum'] += $row->write_bytes;
            $buckets[$key]['count']    += 1;
        }

        $labels = []; $read = []; $write = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            if ($b['count'] > 0) {
                $read[]  = round(($b['readSum']  / $b['count']) / 60 / 1_000_000, 2);
                $write[] = round(($b['writeSum'] / $b['count']) / 60 / 1_000_000, 2);
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

        $rows = DiskUsageMetric::whereBetween('collected_at', [$fromTs, $toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'used_bytes']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = ['sum' => 0, 'count' => 0, 'ts' => $fromTs + $i * $bucketSeconds];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $fromTs;
            $key    = (int) floor($offset / $bucketSeconds);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['sum']   += $row->used_bytes;
            $buckets[$key]['count'] += 1;
        }

        $labels = []; $values = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            $values[] = $b['count'] > 0
                ? round($b['sum'] / $b['count'] / (1024 * 1024 * 1024), 2)
                : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
