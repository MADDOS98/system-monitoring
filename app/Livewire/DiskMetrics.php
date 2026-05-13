<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\DiskIoMetric;
use App\Models\DiskUsageMetric;
use Carbon\Carbon;

class DiskMetrics extends Component
{
    public int $fromTs = 0;
    public int $toTs   = 0;

    public function mount(): void
    {
        $now          = Carbon::now();
        $this->toTs   = $now->timestamp;
        $this->fromTs = $now->copy()->subMinutes(5)->timestamp;
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $this->fromTs = (int) $from;
        $this->toTs   = (int) $to;
    }

    public function render()
    {
        $tz = config('app.timezone');

        $latestIo    = DiskIoMetric::orderByDesc('collected_at')->first();
        $latestUsage = DiskUsageMetric::orderByDesc('collected_at')->first();

        $readBytes  = $latestIo?->read_bytes  ?? 0;
        $writeBytes = $latestIo?->write_bytes ?? 0;

        $totalBytes = $latestUsage?->total_bytes ?? 0;
        $usedBytes  = $latestUsage?->used_bytes  ?? 0;
        $freeBytes  = $totalBytes - $usedBytes;
        $usedPct    = $totalBytes > 0 ? round(($usedBytes / $totalBytes) * 100, 1) : 0;

        $diffSeconds = max(1, $this->toTs - $this->fromTs);

        $bucketSecondsIo    = $this->resolveBucketSecondsIo($diffSeconds);
        $bucketSecondsUsage = $this->resolveBucketSecondsUsage($diffSeconds);
        $labelFormatIo      = $this->resolveLabelFormat($bucketSecondsIo, $diffSeconds);
        $labelFormatUsage   = $this->resolveLabelFormat($bucketSecondsUsage, $diffSeconds);

        $periodLabelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($this->fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($this->toTs, $tz)->format($periodLabelFormat);

        $ioChartData    = $this->getIoChartData($bucketSecondsIo, $labelFormatIo);
        $usageChartData = $this->getUsageChartData($bucketSecondsUsage, $labelFormatUsage);

        return view('livewire.disk-metrics', compact(
            'readBytes', 'writeBytes',
            'totalBytes', 'usedBytes', 'freeBytes', 'usedPct',
            'ioChartData', 'usageChartData', 'periodLabel',
            'bucketSecondsIo', 'bucketSecondsUsage',
            'labelFormatIo', 'labelFormatUsage'
        ));
    }

    private function getIoChartData(int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $this->toTs - $this->fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'read' => [], 'write' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = DiskIoMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'read_bytes', 'write_bytes']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [
                'readSum'  => 0,
                'writeSum' => 0,
                'count'    => 0,
                'ts'       => $this->fromTs + $i * $bucketSeconds,
            ];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $this->fromTs;
            $key    = (int) floor($offset / $bucketSeconds);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['readSum']  += $row->read_bytes;
            $buckets[$key]['writeSum'] += $row->write_bytes;
            $buckets[$key]['count']    += 1;
        }

        $labels = [];
        $read   = [];
        $write  = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            if ($b['count'] > 0) {
                // bytes/min -> MB/s: bytes / 60 / 1_000_000
                $read[]  = round(($b['readSum']  / $b['count']) / 60 / 1_000_000, 2);
                $write[] = round(($b['writeSum'] / $b['count']) / 60 / 1_000_000, 2);
            } else {
                $read[]  = 0;
                $write[] = 0;
            }
        }

        return ['labels' => $labels, 'read' => $read, 'write' => $write];
    }

    private function getUsageChartData(int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $this->toTs - $this->fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'values' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = DiskUsageMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'used_bytes']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [
                'sum'   => 0,
                'count' => 0,
                'ts'    => $this->fromTs + $i * $bucketSeconds,
            ];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $this->fromTs;
            $key    = (int) floor($offset / $bucketSeconds);
            if (!isset($buckets[$key])) {
                continue;
            }
            $buckets[$key]['sum']   += $row->used_bytes;
            $buckets[$key]['count'] += 1;
        }

        $labels = [];
        $values = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            $values[] = $b['count'] > 0
                ? round($b['sum'] / $b['count'] / (1024 * 1024 * 1024), 2)
                : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Reguli bucket pentru I/O (data 1/sec):
     * Identice cu RAM/Network.
     */
    private function resolveBucketSecondsIo(int $diffSeconds): int
    {
        $minutes = $diffSeconds / 60;

        return match (true) {
            $minutes <  20     => 1,
            $minutes <  100    => 5,
            $minutes <  720    => 60,
            $minutes <  4320   => 300,
            $minutes <  20160  => 900,
            $minutes <  86400  => 3600,
            default            => 86400,
        };
    }

    /**
     * Reguli bucket pentru Disk Usage (data 1/min):
     *   5m  -> 60s  (5 puncte)
     *   1h  -> 60s  (60 puncte)
     *   12h -> 60s  (720 puncte)
     *   3 zile -> 5 min
     *   14 zile -> 15 min
     *   60 zile -> 1 ora
     *   > 60 zile -> 1 zi
     */
    private function resolveBucketSecondsUsage(int $diffSeconds): int
    {
        $minutes = $diffSeconds / 60;

        return match (true) {
            $minutes <  720    => 60,      // <= 12h: 1 punct/min
            $minutes <  4320   => 300,     // <= 3 zile: 5 min
            $minutes <  20160  => 900,     // <= 14 zile: 15 min
            $minutes <  86400  => 3600,    // <= 60 zile: 1 ora
            default            => 86400,
        };
    }

    private function resolveLabelFormat(int $bucketSeconds, int $diffSeconds): string
    {
        if ($bucketSeconds < 60)     return 'H:i:s';
        if ($diffSeconds   < 86400)  return 'H:i';
        if ($bucketSeconds >= 86400) return 'M j';
        return 'M j H:i';
    }
}
