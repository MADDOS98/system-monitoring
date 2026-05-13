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
    public bool $live = true;

    private const OFFSETS = ['5m' => 5, '1h' => 60, '24h' => 1440];

    public function mount(string $title = ''): void
    {
        $this->title  = $title;
        $this->preset = '5m';
        $this->live   = true;

        $this->refreshLiveTimes();
    }

    public function applyPreset(string $preset): void
    {
        if ($preset === 'custom') {
            $this->preset = 'custom';
            $this->live   = false;
            return;
        }

        if (!isset(self::OFFSETS[$preset])) {
            return;
        }

        $this->preset = $preset;
        $this->live   = true;

        $this->refreshLiveTimes();
        $this->dispatchTimeRange();
    }

    public function setNow(): void
    {
        if (!isset(self::OFFSETS[$this->preset])) {
            $this->preset = '5m';
        }
        $this->live = true;

        $this->refreshLiveTimes();
        $this->dispatchTimeRange();
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
        $this->live   = false;

        $this->dispatchTimeRange();
    }

    public function updatedTo(): void
    {
        $this->live = false;

        $this->dispatchTimeRange();
    }

    /**
     * In live mode, $from/$to sunt derivate din "now" + preset.
     * Aceasta metoda e apelata la mount, la fiecare actiune utilizator si la render.
     */
    private function refreshLiveTimes(): void
    {
        if (!$this->live) {
            return;
        }
        if (!isset(self::OFFSETS[$this->preset])) {
            return;
        }

        $now = Carbon::now();
        $this->to   = $now->format('Y-m-d\TH:i');
        $this->from = $now->copy()->subMinutes(self::OFFSETS[$this->preset])->format('Y-m-d\TH:i');
    }

    private function dispatchTimeRange(): void
    {
        // In live mode timestamp-urile vin direct din now() la precizie de secunda,
        // nu prin string-urile $from/$to (acelea sunt doar pentru afisare la nivel de minut).
        if ($this->live && isset(self::OFFSETS[$this->preset])) {
            $now = Carbon::now();
            $this->dispatch(
                'setTimeRange',
                (string) $now->copy()->subMinutes(self::OFFSETS[$this->preset])->timestamp,
                (string) $now->timestamp
            );
            return;
        }

        // Custom mode: parsam string-urile (user-ul edit-eaza la nivel de minut).
        if (empty($this->from) || empty($this->to)) {
            return;
        }

        $from = Carbon::createFromFormat('Y-m-d\TH:i', $this->from)->timestamp;
        $to   = Carbon::createFromFormat('Y-m-d\TH:i', $this->to)->timestamp;

        $this->dispatch('setTimeRange', (string) $from, (string) $to);
    }

    public function render()
    {
        // Tinem $from/$to la zi inainte de fiecare randare in live mode.
        $this->refreshLiveTimes();

        return view('livewire.time-range-picker');
    }
}
