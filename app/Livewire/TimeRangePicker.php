<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;

class TimeRangePicker extends Component
{
    public string $preset = '5m';
    public string $from   = '';
    public string $to     = '';
    public string $title  = '';
    public ?string $anchorFrom = null;
    public bool $live = true;

    public function mount(string $title = ''): void
    {
        $this->title  = $title;
        $this->preset = '5m';

        $now = Carbon::now();

        $this->from = $now->copy()->subMinutes(5)->format('Y-m-d\TH:i');
        $this->to   = $now->format('Y-m-d\TH:i');

        $this->anchorFrom = $this->to;
        $this->live       = true;
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;

        $offsets = ['5m' => 5, '1h' => 60, '24h' => 1440];

        if (!isset($offsets[$preset])) {
            return;
        }

        if ($this->live) {
            $this->anchorFrom = Carbon::now()->format('Y-m-d\TH:i');
        } elseif (!$this->anchorFrom) {
            $this->anchorFrom = $this->from ?: now()->format('Y-m-d\TH:i');
        }

        $base = Carbon::createFromFormat('Y-m-d\TH:i', $this->anchorFrom);

        $minutes = $offsets[$preset];

        $this->to   = $base->format('Y-m-d\TH:i');
        $this->from = $base->copy()->subMinutes($minutes)->format('Y-m-d\TH:i');

        $this->dispatchTimeRange();
    }

    public function setNow(): void
    {
        $offsets = ['5m' => 5, '1h' => 60, '24h' => 1440];

        if (!isset($offsets[$this->preset])) {
            $this->preset = '5m';
        }

        $minutes = $offsets[$this->preset];

        $now = Carbon::now();

        $this->to   = $now->format('Y-m-d\TH:i');
        $this->from = $now->copy()->subMinutes($minutes)->format('Y-m-d\TH:i');

        $this->anchorFrom = $this->to;
        $this->live       = true;

        $this->dispatchTimeRange();
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
        $this->anchorFrom = $this->from;
        $this->live = false;

        $this->dispatchTimeRange();
    }

    public function updatedTo(): void
    {
        $this->live = false;

        $this->dispatchTimeRange();
    }

    private function dispatchTimeRange(): void
    {
        if (empty($this->from) || empty($this->to)) {
            return;
        }

        $from = Carbon::createFromFormat('Y-m-d\TH:i', $this->from)->timestamp;
        $to   = Carbon::createFromFormat('Y-m-d\TH:i', $this->to)->timestamp;

        $this->dispatch('setTimeRange', (string) $from, (string) $to);
    }

    public function render()
    {
        return view('livewire.time-range-picker');
    }
}
