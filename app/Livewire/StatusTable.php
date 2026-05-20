<?php

namespace App\Livewire;

use App\Services\Monitoring\ApacheLogsQuery;
use App\Services\Monitoring\BucketResolver;
use Livewire\Attributes\On;
use Livewire\Component;

class StatusTable extends Component
{
    public ?int $from = null;
    public ?int $to   = null;

    public function mount(): void
    {
        $this->from = now()->subMinutes(5)->timestamp;
        $this->to   = now()->timestamp;
    }

    #[On('setTimeRange')]
    public function setTimeRange(string $from, string $to): void
    {
        $this->from = (int) $from;
        $this->to   = (int) $to;
    }

    public function render()
    {
        $diff          = max(1, ($this->to ?? 0) - ($this->from ?? 0));
        $bucketSeconds = BucketResolver::secondsFor($diff);

        $raw = app(ApacheLogsQuery::class)->byStatus((int) $this->from, (int) $this->to);

        $totalStat = $raw['total'] ?: 1;
        unset($raw['total']);

        // Convertesc array asociativ -> Collection de obiecte cu props ->group, ->total,
        // pentru compatibilitate cu blade-ul existent (`$byStatus->where('group', '2xx')`).
        $byStatus = collect($raw)
            ->map(fn($total, $group) => (object) ['group' => $group, 'total' => $total])
            ->values();

        return view('livewire.status-table', compact('byStatus', 'totalStat', 'bucketSeconds'));
    }
}
