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
        $tz            = config('app.timezone');
        $diffSeconds   = $this->toTs - $this->fromTs;

        if ($diffSeconds <= 0) {
            return ['labels' => [], 'values' => []];
        }

        // Max 120 puncte vizibile in chart
        $maxPoints     = 120;
        $bucketSeconds = max(1, (int) ceil($diffSeconds / $maxPoints));
        $bucketCount   = (int) ceil($diffSeconds / $bucketSeconds);

        $rows = RamMetric::whereBetween('collected_at', [$this->fromTs, $this->toTs])
            ->orderBy('collected_at')
            ->get(['collected_at', 'used_kb']);

        // Initializam toate bucket-urile (chiar daca sunt goale) ca sa acopere intreaga perioada
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

        // Format label dinamic in functie de durata totala
        $labelFormat = $diffSeconds >= 86400 ? 'M j H:i' : 'H:i';

        $labels = [];
        $values = [];
        $lastValue = null;

        foreach ($buckets as $b) {
            $labels[] = Carbon::createFromTimestamp($b['ts'], $tz)->format($labelFormat);
            if ($b['count'] > 0) {
                $lastValue = $b['sum'] / $b['count'] / (1024 * 1024);
            }
            // Daca bucket-ul e gol, folosim ultima valoare cunoscuta (sau null la inceput)
            $values[] = $lastValue !== null ? round($lastValue, 2) : null;
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
