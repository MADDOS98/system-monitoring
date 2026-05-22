<?php

namespace App\Livewire;

use App\Models\Alert;
use App\Models\AlertRule;
use Livewire\Component;

class AlertsList extends Component
{
    public string $tab          = 'active';
    public string $levelFilter  = 'all';
    public string $metricFilter = 'all';
    public string $search       = '';

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['active', 'read'], true)) {
            $this->tab = $tab;
        }
    }

    public function setLevelFilter(string $level): void
    {
        if (in_array($level, ['all', 'critical', 'warning', 'info'], true)) {
            $this->levelFilter = $level;
        }
    }

    public function markAsRead(int $id): void
    {
        $alert = Alert::find($id);
        if ($alert && !$alert->isRead()) {
            $alert->markRead();
        }
    }

    public function clearFilters(): void
    {
        $this->levelFilter  = 'all';
        $this->metricFilter = 'all';
        $this->search       = '';
    }

    public function hasActiveFilters(): bool
    {
        return $this->levelFilter !== 'all'
            || $this->metricFilter !== 'all'
            || trim($this->search) !== '';
    }

    public function render()
    {
        // Base query scoped to current Active/Read tab.
        $base = Alert::query()
            ->when($this->tab === 'active', fn ($q) => $q->whereNull('read_at'))
            ->when($this->tab === 'read',   fn ($q) => $q->whereNotNull('read_at'));

        // Per-level counts within the current tab — used for the filter badges.
        $levelCounts = (clone $base)
            ->selectRaw('level, COUNT(*) AS c')
            ->groupBy('level')
            ->pluck('c', 'level');

        $tabTotal = (clone $base)->count();

        // Apply user filters (level, metric, search).
        $alerts = (clone $base)
            ->with('rule')
            ->when($this->levelFilter !== 'all',  fn ($q) => $q->where('level',  $this->levelFilter))
            ->when($this->metricFilter !== 'all', fn ($q) => $q->where('metric', $this->metricFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('message', 'LIKE', $term)
                      ->orWhereHas('rule', fn ($q2) => $q2->where('name', 'LIKE', $term));
                });
            })
            ->orderByDesc('id')
            ->get();

        $activeCount = Alert::whereNull('read_at')->count();
        $readCount   = Alert::whereNotNull('read_at')->count();

        return view('livewire.alerts-list', [
            'alerts'       => $alerts,
            'activeCount'  => $activeCount,
            'readCount'    => $readCount,
            'tabTotal'     => $tabTotal,
            'levelCounts'  => $levelCounts,
            'allMetrics'   => AlertRule::METRICS,
        ]);
    }
}
