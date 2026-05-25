<?php

namespace App\Livewire;

use App\Models\Alert;
use Livewire\Component;

class TabAlerts extends Component
{
    public string $tab;

    private const TAB_TO_METRICS = [
        'cpu'     => ['cpu'],
        'ram'     => ['ram'],
        'network' => ['network_in', 'network_out'],
        'disk'    => ['disk_io_read', 'disk_io_write'],
    ];

    public function mount(string $tab): void
    {
        $this->tab = $tab;
    }

    public function markAsRead(int $id): void
    {
        $alert = Alert::find($id);
        if ($alert && !$alert->isRead()) {
            $alert->markRead();
        }
    }

    public function render()
    {
        $metrics = self::TAB_TO_METRICS[$this->tab] ?? [];

        $alerts = empty($metrics)
            ? collect()
            : Alert::with('rule')
                ->whereNull('read_at')
                ->whereIn('metric', $metrics)
                ->orderByDesc('id')
                ->get();

        return view('livewire.tab-alerts', compact('alerts'));
    }
}
