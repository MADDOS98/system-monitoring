<?php

namespace App\Livewire;

use App\Models\Percentile;
use Livewire\Component;

class TabPercentiles extends Component
{
    public string $tab;

    /**
     * Mapeaza tab-ul din pagina metrics (cpu/ram/network/disk) la unul sau mai
     * multe metrice corespunzatoare in tabela percentiles.
     */
    private const TAB_TO_METRICS = [
        'cpu'     => ['cpu', 'cpu_stolen'],
        'ram'     => ['ram'],
        'network' => ['network_in', 'network_out'],
        'disk'    => ['disk_io_read', 'disk_io_write'],
    ];

    private const TAB_LABELS = [
        'cpu'     => 'CPU',
        'ram'     => 'RAM',
        'network' => 'Network',
        'disk'    => 'Disk',
    ];

    public function mount(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render()
    {
        $metrics = self::TAB_TO_METRICS[$this->tab] ?? [];

        $percentiles = empty($metrics)
            ? collect()
            : Percentile::whereIn('metric', $metrics)
                ->where('is_active', true)
                ->orderBy('metric')
                ->orderBy('percentile')
                ->get();

        return view('livewire.tab-percentiles', [
            'percentiles' => $percentiles,
            'tabLabel'    => self::TAB_LABELS[$this->tab] ?? ucfirst($this->tab),
        ]);
    }
}
