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
        // Base scope: tab + metric + search. Level filter is applied last so the
        // level badges (and the "All" badge) reflect counts under the OTHER active
        // filters — e.g. picking metric=cpu narrows the per-level numbers to cpu.
        $base = Alert::query()
            ->when($this->tab === 'active', fn ($q) => $q->whereNull('read_at'))
            ->when($this->tab === 'read',   fn ($q) => $q->whereNotNull('read_at'))
            ->when($this->metricFilter !== 'all', fn ($q) => $q->where('metric', $this->metricFilter))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('message', 'LIKE', $term)
                      ->orWhereHas('rule', fn ($q2) => $q2->where('name', 'LIKE', $term));
                });
            });

        $levelCounts = (clone $base)
            ->selectRaw('level, COUNT(*) AS c')
            ->groupBy('level')
            ->pluck('c', 'level');

        $tabTotal = (clone $base)->count();

        $alerts = (clone $base)
            ->with('rule')
            ->when($this->levelFilter !== 'all', fn ($q) => $q->where('level', $this->levelFilter))
            ->orderByDesc('id')
            ->get();

        // Active/Read tab badges stay global (independent of metric/search) so
        // the user can still see how many alerts exist in the other tab.
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
