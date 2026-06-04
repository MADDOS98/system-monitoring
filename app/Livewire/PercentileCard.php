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

        // Un singur apel — compute() returneaza acum si median in acelasi payload.
        $data = $calc->compute(
            $this->percentile->metric,
            (float) $this->percentile->percentile,
            (int) $this->percentile->window_minutes,
        );

        // Tick-ul "med" pe slider: ascuns daca percentila e exact 50% (median == value,
        // ar fi tick suprapus). Altfel afisam median-ul din payload.
        $median = ($data !== null && ((float) $this->percentile->percentile) !== 50.0)
            ? $data['median']
            : null;

        return view('livewire.percentile-card', [
            'data'   => $data,
            'median' => $median,
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
