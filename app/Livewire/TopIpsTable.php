<?php

namespace App\Livewire;

use App\Services\Monitoring\ApacheLogsQuery;
use App\Services\Monitoring\BucketResolver;
use Livewire\Attributes\On;
use Livewire\Component;

class TopIpsTable extends Component
{
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
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render()
    {
        $diff          = max(1, ($this->to ?? 0) - ($this->from ?? 0));
        $bucketSeconds = BucketResolver::secondsFor($diff);

        $topIps = collect(app(ApacheLogsQuery::class)->topIps(
            (int) $this->from,
            (int) $this->to,
            $this->tab
        ))->map(fn($r) => (object) $r);

        return view('livewire.top-ips-table', compact('topIps', 'bucketSeconds'));
    }
}
