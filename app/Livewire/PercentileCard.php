<?php

namespace App\Livewire;

use App\Models\Percentile;
use App\Services\Percentiles\PercentileCalculator;
use Livewire\Component;

class PercentileCard extends Component
{
    public Percentile $percentile;

    public function mount(Percentile $percentile): void
    {
        $this->percentile = $percentile;
    }

    public function render()
    {
        $calc = app(PercentileCalculator::class);

        $mainData = $calc->compute(
            $this->percentile->metric,
            (float) $this->percentile->percentile,
            (int) $this->percentile->window_minutes,
        );

        // Median (P50) ca referinta pentru tick-ul "med" pe slider.
        // Nu folosim 50% daca percentila e DEJA 50% (evitam dublu tick).
        $median = ((float) $this->percentile->percentile) === 50.0
            ? null
            : $calc->compute(
                $this->percentile->metric,
                50.0,
                (int) $this->percentile->window_minutes,
            );

        return view('livewire.percentile-card', [
            'data'   => $mainData,
            'median' => $median !== null ? $median['value'] : null,
            'unit'   => $this->unitFor($this->percentile->metric),
        ]);
    }

    private function unitFor(string $metric): string
    {
        return match ($metric) {
            'cpu', 'ram'                       => '%',
            'disk_io_read', 'disk_io_write'    => 'MB/s',
            'network_in', 'network_out'        => 'Mbps',
            default                            => '',
        };
    }
}
