<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ApacheLog;
use Carbon\Carbon;

class PeakTrafficTimeline extends Component
{
    public ?string $toDate = null;
    public ?int    $from   = null;
    public ?int    $to     = null;

    public function mount(): void
    {
        $this->toDate = Carbon::now()->format('Y-m-d');
        $this->from   = Carbon::now()->subMinutes(5)->timestamp;
        $this->to     = Carbon::now()->timestamp;
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $this->from   = (int) $from;
        $this->to     = (int) $to;
        $this->toDate = Carbon::createFromTimestamp((int) $to)->format('Y-m-d');
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

    public function render()
    {
        $day = $this->toDate ?? Carbon::now()->format('Y-m-d');

        $dayStart = Carbon::parse($day)->startOfDay()->timestamp;
        $dayEnd   = Carbon::parse($day)->endOfDay()->timestamp;

        $rows = ApacheLog::query()
            ->whereBetween('log_time', [$dayStart, $dayEnd])
            ->selectRaw('CAST(strftime("%H", datetime(log_time, "unixepoch")) AS INTEGER) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        $bins = [];
        for ($h = 0; $h < 24; $h++) {
            $bins[$h] = $rows[$h] ?? 0;
        }

        $max = max($bins) ?: 1;

        /**
         * =========================
         *  LOGICĂ PE NIVELE
         * =========================
         */

        $values = array_values($bins);
        $mean = array_sum($values) / count($values);

        $variance = 0;
        foreach ($values as $v) {
            $variance += pow($v - $mean, 2);
        }
        $variance = $variance / count($values);
        $std = sqrt($variance) ?: 1;

        $zScores = [];
        $levels = [];

        for ($h = 0; $h < 24; $h++) {
            $current = $bins[$h];

            // Z-score
            $z = ($current - $mean) / $std;
            $zScores[$h] = $z;

            // Vecini (local peak)
            $prev = $bins[$h - 1] ?? 0;
            $next = $bins[$h + 1] ?? 0;

            $localPeak =
                $current >= 50 && // prag minim anti-noise
                $current > ($prev * 1.4) &&
                $current > ($next * 1.4);

            // Sistem pe nivele
            if ($z >= 2.8) {
                $levels[$h] = 'critical';
            } elseif ($z >= 2 || $localPeak) {
                $levels[$h] = 'warning';
            } else {
                $levels[$h] = 'normal';
            }
        }

        $bucketSeconds = $this->resolveBucketSeconds(max(1, ($this->to ?? 0) - ($this->from ?? 0)));

        return view('livewire.peak-traffic-timeline', compact('bins', 'max', 'day', 'levels', 'bucketSeconds'));
    }
}
