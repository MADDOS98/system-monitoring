<?php

namespace App\Livewire;

use App\Models\Alert;
use Livewire\Component;

class AlertsList extends Component
{
    public string $tab = 'active';

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['active', 'read'], true)) {
            $this->tab = $tab;
        }
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
        $alerts = Alert::with('rule')
            ->when($this->tab === 'active', fn ($q) => $q->whereNull('read_at'))
            ->when($this->tab === 'read',   fn ($q) => $q->whereNotNull('read_at'))
            ->orderByDesc('id')
            ->get();

        $activeCount = Alert::whereNull('read_at')->count();
        $readCount   = Alert::whereNotNull('read_at')->count();

        return view('livewire.alerts-list', compact('alerts', 'activeCount', 'readCount'));
    }
}
