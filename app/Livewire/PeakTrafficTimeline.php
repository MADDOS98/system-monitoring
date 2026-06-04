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
        $tz = config('app.timezone');

        $this->toDate = Carbon::now($tz)->format('Y-m-d');
        $this->from   = Carbon::now($tz)->subMinutes(5)->timestamp;
        $this->to     = Carbon::now($tz)->timestamp;
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $tz = config('app.timezone');

        $this->from   = (int) $from;
        $this->to     = (int) $to;
        // EXPLICIT $tz: createFromTimestamp fara tz returneaza UTC indiferent
        // de PHP TZ (mostenit din PHP DateTime('@ts')). Ar shifta ziua aleasa
        // cu offset-ul TZ pentru orice $to intre UTC 21:00 si 23:59.
        $this->toDate = Carbon::createFromTimestamp((int) $to, $tz)->format('Y-m-d');
    }

    public function render()
    {
        $tz   = config('app.timezone');
        $day  = $this->toDate ?? Carbon::now($tz)->format('Y-m-d');
        $data = app(ApacheLogsQuery::class)->peakBins($day, $tz);

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
