<?php

namespace App\Livewire\ApacheLogs;

use App\Services\Monitoring\ApacheLogsQuery;
use App\Services\Monitoring\BucketResolver;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TopIpsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?int $from = null;
    public ?int $to   = null;
    public string $tab = 'All';

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
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $diff          = max(1, ($this->to ?? 0) - ($this->from ?? 0));
        $bucketSeconds = BucketResolver::secondsFor($diff);

        // Mapeaza items (arrays) la stdClass pentru a pastra blade-ul existent
        // care foloseste $ip->ip / $ip->reqs / etc.
        $topIps = app(ApacheLogsQuery::class)
            ->topIps((int) $this->from, (int) $this->to, $this->tab, $this->getPage())
            ->through(fn ($row) => (object) $row);

        return view('livewire.apache-logs.top-ips-table', compact('topIps', 'bucketSeconds'));
    }
}
