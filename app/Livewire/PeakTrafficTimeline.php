<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ApacheLog;
use Carbon\Carbon;

class PeakTrafficTimeline extends Component
{
    public ?string $toDate  = null;
    public ?int    $selected = null; // ora selectata (0-23)

    public function mount(): void
    {
        $this->toDate = Carbon::now()->format('Y-m-d');
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $this->toDate   = Carbon::createFromTimestamp((int) $to)->format('Y-m-d');
        $this->selected = null;
    }

    public function toggleHour(int $hour): void
    {
        $this->selected = $this->selected === $hour ? null : $hour;
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

        return view('livewire.peak-traffic-timeline', compact('bins', 'max', 'day'));
    }
}