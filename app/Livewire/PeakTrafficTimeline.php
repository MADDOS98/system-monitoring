<?php

namespace App\Livewire;

use App\Services\Monitoring\ApacheLogsQuery;
use App\Services\Monitoring\BucketResolver;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

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

    public function render()
    {
        $day  = $this->toDate ?? Carbon::now()->format('Y-m-d');
        $data = app(ApacheLogsQuery::class)->peakBins($day);

        $diff          = max(1, ($this->to ?? 0) - ($this->from ?? 0));
        $bucketSeconds = BucketResolver::secondsFor($diff);

        return view('livewire.peak-traffic-timeline', [
            'bins'          => $data['bins'],
            'max'           => $data['max'],
            'levels'        => $data['levels'],
            'day'           => $data['day'],
            'bucketSeconds' => $bucketSeconds,
        ]);
    }
}
