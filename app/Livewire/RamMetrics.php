<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\RamMetric;
use Carbon\Carbon;

class RamMetrics extends Component
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

        $latest = RamMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderByDesc('collected_at')
            ->first();

        $totalKb = $latest?->total_kb ?? 0;
        $usedKb  = $latest?->used_kb  ?? 0;
        $freeKb  = $totalKb - $usedKb;
        $usedPct = $totalKb > 0 ? round(($usedKb / $totalKb) * 100, 1) : 0;

        $diffSeconds = $this->toTs - $this->fromTs;
        $labelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';

        $periodLabel = Carbon::createFromTimestamp($this->fromTs, $tz)->format($labelFormat)
            . ' – '
            . Carbon::createFromTimestamp($this->toTs, $tz)->format($labelFormat);

        $chartData = $this->getChartData();

        return view('livewire.ram-metrics', compact(
            'totalKb', 'usedKb', 'freeKb', 'usedPct', 'chartData', 'periodLabel'
        ));
    }

    private function getChartData(): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $this->toTs - $this->fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'values' => []];
        }

        $bucketSeconds = $this->resolveBucketSeconds($diffSeconds);
        $bucketCount   = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = RamMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'used_kb']);

        // Toate bucket-urile pornesc cu 0 — daca nu au date raman 0
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
            $buckets[$key]['sum']   += $row->used_kb;
            $buckets[$key]['count'] += 1;
        }

        $labelFormat = $this->resolveLabelFormat($bucketSeconds, $diffSeconds);

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

    /**
     * Regula generala pentru toate metricile:
     *   < 20 min       -> 1 secunda
     *   20 - 100 min   -> 5 secunde
     *   100 min - 12h  -> 1 minut
     *   12h - 3 zile   -> 5 minute
     *   3 - 14 zile    -> 15 minute
     *   14 - 60 zile   -> 1 ora
     *   > 60 zile      -> 1 zi
     */
    private function resolveBucketSeconds(int $diffSeconds): int
    {
        $minutes = $diffSeconds / 60;

        return match (true) {
            $minutes <  20     => 1,
            $minutes <  100    => 5,
            $minutes <  720    => 60,      // 12h
            $minutes <  4320   => 300,     // 3 zile
            $minutes <  20160  => 900,     // 14 zile
            $minutes <  86400  => 3600,    // 60 zile
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
