<?php

namespace App\Livewire;

use App\Services\Monitoring\ApacheLogsQuery;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class PeakTrafficTimeline extends Component
{
    public ?int $from = null;
    public ?int $to   = null;

    public function mount(): void
    {
        $tz         = config('app.timezone');
        $this->to   = Carbon::now($tz)->timestamp;
        $this->from = $this->to - 300; // default 5m live preset
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $this->from = (int) $from;
        $this->to   = (int) $to;
    }

    public function render()
    {
        $tz   = config('app.timezone');
        $diff = ($this->to ?? 0) - ($this->from ?? 0);

        // Detecteaza live preset prin durata exacta — match cu ApacheLogsTable
        // si TimeRangePicker (300s = 5m, 3600s = 1h, 86400s = 24h).
        $isLivePreset = in_array($diff, [300, 3600, 86400], true);

        // Live → sliding 24h pana la ora curenta. Custom → ziua locala a $to.
        $endTs = $isLivePreset ? null : $this->to;

        $data = app(ApacheLogsQuery::class)->peakBins($endTs, $tz);

        return view('livewire.peak-traffic-timeline', [
            'bins'   => $data['bins'],
            'hours'  => $data['hours'],
            'max'    => $data['max'],
            'levels' => $data['levels'],
            'start'  => $data['start'],
            'end'    => $data['end'],
        ]);
    }
}
