<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\CpuMetric;
use Carbon\Carbon;

class CpuMetrics extends Component
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

        $latest = CpuMetric::orderByDesc('collected_at')->first();

        $totalUsage  = $latest?->total_usage  ?? 0;
        $stolenUsage = $latest?->stolen_usage ?? 0;
        $perCore     = $latest?->per_core_usage ?? [];
        $coreCount   = count($perCore);
        $coresAvg    = $coreCount > 0 ? round(array_sum($perCore) / $coreCount, 1) : 0;

        $diffSeconds = max(1, $this->toTs - $this->fromTs);

        $bucketSeconds = $this->resolveBucketSeconds($diffSeconds);
        $labelFormat   = $this->resolveLabelFormat($bucketSeconds, $diffSeconds);

        $periodLabelFormat = $diffSeconds >= 86400 ? 'Y-m-d H:i' : 'H:i';
        $periodLabel = Carbon::createFromTimestamp($this->fromTs, $tz)->format($periodLabelFormat)
            . ' – '
            . Carbon::createFromTimestamp($this->toTs, $tz)->format($periodLabelFormat);

        $chartData = $this->getChartData($bucketSeconds, $labelFormat);

        return view('livewire.cpu-metrics', compact(
            'totalUsage', 'stolenUsage', 'coresAvg', 'coreCount',
            'chartData', 'periodLabel',
            'bucketSeconds', 'labelFormat'
        ));
    }

    private function getChartData(int $bucketSeconds, string $labelFormat): array
    {
        $tz          = config('app.timezone');
        $diffSeconds = $this->toTs - $this->fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'total' => [], 'coresAvg' => [], 'stolen' => []];
        }

        $bucketCount = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = CpuMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'total_usage', 'per_core_usage', 'stolen_usage']);

        $buckets = [];
        for ($i = 0; $i < $bucketCount; $i++) {
            $buckets[$i] = [
                'totalSum'    => 0,
                'coresAvgSum' => 0,
                'stolenSum'   => 0,
                'count'       => 0,
                'ts'          => $this->fromTs + $i * $bucketSeconds,
            ];
        }

        foreach ($rows as $row) {
            $offset = $row->collected_at - $this->fromTs;
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
