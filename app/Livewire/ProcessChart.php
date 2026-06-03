<?php

namespace App\Livewire;

use App\Services\Monitoring\ProcessDetailQuery;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProcessChart extends Component
{
    public string $name;
    public string $metric = 'cpu'; // cpu | ram | disk | info
    public int    $fromTs = 0;
    public int    $toTs   = 0;

    public function mount(string $name, string $metric = 'cpu'): void
    {
        $this->name   = $name;
        $this->metric = in_array($metric, ['cpu', 'ram', 'disk', 'info'], true) ? $metric : 'cpu';

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
        $query = app(ProcessDetailQuery::class);

        $data = match ($this->metric) {
            'ram'   => $query->ramSnapshot($this->name, $this->fromTs, $this->toTs),
            'disk'  => $query->diskSnapshot($this->name, $this->fromTs, $this->toTs),
            'info'  => $query->infoSnapshot($this->name, $this->fromTs, $this->toTs),
            default => $query->cpuSnapshot($this->name, $this->fromTs, $this->toTs),
        };

        return view('livewire.process-chart', $data);
    }
}
