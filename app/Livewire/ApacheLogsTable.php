<?php

namespace App\Livewire;

use App\Services\Monitoring\ApacheLogsQuery;
use App\Services\Monitoring\BucketResolver;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ApacheLogsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $searchQuery = '';
    public string $searchField = 'any';
    public ?int   $from        = null;
    public ?int   $to          = null;

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

    public function updatingSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatingSearchField(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Slide live window forward la fiecare actiune Livewire (paginare, search, refresh)
        // ca query-ul sa nu rateze randurile noi prepend-uite de poller-ul JS.
        $diff = ($this->to ?? 0) - ($this->from ?? 0);
        $isLivePreset = in_array($diff, [300, 3600, 86400], true);

        if ($isLivePreset) {
            $effectiveTo   = now()->timestamp;
            $effectiveFrom = $effectiveTo - $diff;
        } else {
            $effectiveTo   = $this->to;
            $effectiveFrom = $this->from;
        }

        $logs = app(ApacheLogsQuery::class)->paginate(
            (int) $effectiveFrom,
            (int) $effectiveTo,
            $this->getPage(),
            $this->searchQuery,
            $this->searchField
        );

        $bucketSeconds = BucketResolver::secondsFor(max(1, $diff));

        return view('livewire.apache-logs-table', [
            'logs'          => $logs,
            'bucketSeconds' => $bucketSeconds,
        ]);
    }
}
