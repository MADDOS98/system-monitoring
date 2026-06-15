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

    /**
     * Disk growth forecast pe ultimele 30 zile (locale, TZ aware).
     *
     * Pentru fiecare zi calculam growth-ul intra-day = MAX(used_bytes) - MIN(used_bytes).
     * Avantaj: surprinde cresterea efectiva chiar daca au fost cleanup-uri.
     *
     * Stats agregate:
     *  - avg_per_day: media zilnica
     *  - max_per_day: cea mai mare crestere intr-o zi
     *  - days_left: estimare pe avg (free / avg_per_day)
     *  - days_left_worst: estimare conservatoare pe max (free / max_per_day)
     *
     * Levels per zi (pentru colorare bar):
     *  - z >= 1.5 → spike (rosu)
     *  - z >= 0.5 → elevated (amber)
     *  - else      → normal (sky)
     */
    public function growthForecast(?string $tz = null): array
    {
        $tz = $tz ?? config('app.timezone');

        $today   = Carbon::now($tz)->startOfDay();
        $start   = $today->copy()->subDays(29);
        $startTs = $start->timestamp;
        $endTs   = $today->copy()->endOfDay()->timestamp;

        // Per-day MAX/MIN; CAST integer division da day_idx [0..29] aliniat
        // la startTs (= local midnight cu 29 zile in urma).
        $rows = DB::connection('system_metrics')
            ->table('disk_usage_metrics')
            ->selectRaw(
                'CAST((collected_at - ?) / 86400 AS INTEGER) AS day_idx,
                 MAX(used_bytes) AS max_used,
                 MIN(used_bytes) AS min_used',
                [$startTs]
            )
            ->whereBetween('collected_at', [$startTs, $endTs])
            ->groupBy('day_idx')
            ->get()
            ->keyBy('day_idx');

        $growths = [];
        $labels  = [];
        for ($i = 0; $i < 30; $i++) {
            $dayStart = $start->copy()->addDays($i);
            $row      = $rows->get($i);
            $growths[$i] = $row ? max(0, (int) $row->max_used - (int) $row->min_used) : 0;
            $labels[$i]  = $dayStart->format('M j');
        }

        $values    = array_values($growths);
        $count     = count($values);
        $maxGrowth = $count > 0 ? max($values) : 0;
        $avgGrowth = $count > 0 ? array_sum($values) / $count : 0;

        // Z-score colors (pattern identic cu peakBins).
        $mean     = $avgGrowth;
        $variance = 0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $variance /= max(1, $count);
        $std = sqrt($variance) ?: 1;

        $levels = [];
        for ($i = 0; $i < 30; $i++) {
            $z = ($growths[$i] - $mean) / $std;
            if ($z >= 1.5) {
                $levels[$i] = 'spike';
            } elseif ($z >= 0.5) {
                $levels[$i] = 'elevated';
            } else {
                $levels[$i] = 'normal';
            }
        }

        // Current state pentru "days left" forecast.
        $latest = DiskUsageMetric::orderByDesc('collected_at')->first();
        $totalBytes = (int) ($latest?->total_bytes ?? 0);
        $usedBytes  = (int) ($latest?->used_bytes  ?? 0);
        $freeBytes  = max(0, $totalBytes - $usedBytes);

        $daysLeft      = $avgGrowth > 0 ? (int) floor($freeBytes / $avgGrowth) : null;
        $daysLeftWorst = $maxGrowth > 0 ? (int) floor($freeBytes / $maxGrowth) : null;

        return [
            'growths'         => $growths,
            'labels'          => $labels,
            'levels'          => $levels,
            'max_value'       => $maxGrowth ?: 1,
            'avg_per_day'     => (int) round($avgGrowth),
            'max_per_day'     => $maxGrowth,
            'free_bytes'      => $freeBytes,
            'days_left'       => $daysLeft,
            'days_left_worst' => $daysLeftWorst,
        ];
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
