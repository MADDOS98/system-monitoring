<?php

namespace App\Livewire;

use App\Models\Percentile;
use Livewire\Component;

class PercentilesPage extends Component
{
    /**
     * Listă de metrice "deschise" (accordion expanded). Cardurile dintr-un
     * accordion neambalat NU se randeaza, deci nu se calculeaza percentile —
     * lazy load server-side strict.
     */
    public array $openMetrics = ["cpu"];

    private const METRIC_LABELS = [
        'cpu'           => 'CPU',
        'cpu_stolen'    => 'CPU stolen',
        'ram'           => 'RAM',
        'disk_io_read'  => 'Disk read',
        'disk_io_write' => 'Disk write',
        'network_in'    => 'Network in',
        'network_out'   => 'Network out',
    ];

    public function toggle(string $metric): void
    {
        if (in_array($metric, $this->openMetrics, true)) {
            $this->openMetrics = array_values(array_diff($this->openMetrics, [$metric]));
        } else {
            $this->openMetrics[] = $metric;
        }
    }

    public function render()
    {
        $percentilesByMetric = Percentile::orderBy('metric')
            ->orderBy('percentile')
            ->get()
            ->groupBy('metric');

        return view('livewire.percentiles-page', [
            'percentilesByMetric' => $percentilesByMetric,
            'metricLabels'        => self::METRIC_LABELS,
        ]);
    }
}
