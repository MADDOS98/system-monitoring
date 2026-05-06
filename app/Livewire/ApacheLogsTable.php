<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use App\Models\ApacheLog;

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

    // Reset paginarea cand se schimba cautarea
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
        $logs = ApacheLog::query()
            ->when($this->from, fn($q) => $q->where('log_time', '>=', $this->from))
            ->when($this->to,   fn($q) => $q->where('log_time', '<=', $this->to))
            ->when($this->searchQuery !== '', function ($q) {
                $query = $this->searchQuery;
                $field = $this->searchField;

                return match($field) {
                    'IP'           => $q->where('remote_host', 'like', "%{$query}%"),
                    'URL / endpoint' => $q->where('uri', 'like', "%{$query}%"),
                    'User-Agent'   => $q->where('user_agent', 'like', "%{$query}%"),
                    'HTTP status'  => $q->where('status', 'like', "%{$query}%"),
                    'Method'       => $q->where('method', strtoupper($query)),
                    default        => $q->where(function ($q2) use ($query) {
                        $q2->where('remote_host', 'like', "%{$query}%")
                           ->orWhere('uri', 'like', "%{$query}%")
                           ->orWhere('status', 'like', "%{$query}%")
                           ->orWhere('method', $query); // method: match exact, nu LIKE
                    }),
                };
            })
            ->orderByDesc('log_time')
            ->paginate(20);

        return view('livewire.apache-logs-table', [
            'logs' => $logs,
        ]);
    }
}