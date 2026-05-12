<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\NetworkMetric;
use Carbon\Carbon;

class NetworkMetrics extends Component
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

        $latest = NetworkMetric::orderByDesc('collected_at')->first();

        $rxBytes = $latest?->rx_bytes ?? 0;
        $txBytes = $latest?->tx_bytes ?? 0;

        $diffSeconds = $this->toTs - $this->fromTs;
        $labelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';

        $periodLabel = Carbon::createFromTimestamp($this->fromTs, $tz)->format($labelFormat)
            . ' – '
            . Carbon::createFromTimestamp($this->toTs, $tz)->format($labelFormat);

        $chartData = $this->getChartData();

        return view('livewire.network-metrics', compact(
            'rxBytes', 'txBytes', 'chartData', 'periodLabel'
        ));
    }

    private function getChartData(): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $this->toTs - $this->fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'rx' => [], 'tx' => []];
        }

        $bucketSeconds = $this->resolveBucketSeconds($diffSeconds);
        $bucketCount   = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = NetworkMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'rx_bytes', 'tx_bytes']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [
                'rxSum' => 0,
                'txSum' => 0,
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
            $buckets[$key]['rxSum'] += $row->rx_bytes;
            $buckets[$key]['txSum'] += $row->tx_bytes;
            $buckets[$key]['count'] += 1;
        }

        $labelFormat = $this->resolveLabelFormat($bucketSeconds, $diffSeconds);

        $labels = [];
        $rx     = [];
        $tx     = [];
        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            if ($b['count'] > 0) {
                // bytes/minute -> Mbps: bytes * 8 / 60 / 1_000_000
                $rx[] = round(($b['rxSum'] / $b['count']) * 8 / 60 / 1_000_000, 2);
                $tx[] = round(($b['txSum'] / $b['count']) * 8 / 60 / 1_000_000, 2);
            } else {
                $rx[] = 0;
                $tx[] = 0;
            }
        }

        return ['labels' => $labels, 'rx' => $rx, 'tx' => $tx];
    }

    private function resolveBucketSeconds(int $diffSeconds): int
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

    private function resolveLabelFormat(int $bucketSeconds, int $diffSeconds): string
    {
        if ($bucketSeconds < 60)     return 'H:i:s';
        if ($diffSeconds   < 86400)  return 'H:i';
        if ($bucketSeconds >= 86400) return 'M j';
        return 'M j H:i';
    }
}
