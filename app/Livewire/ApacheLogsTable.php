<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ApacheLog;

class ApacheLogsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $searchQuery = '';
    public string $searchField = 'any';

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
            ->when($this->searchQuery !== '', function ($q) {
                $query = $this->searchQuery;
                $field = $this->searchField;

                return match($field) {
                    'IP'           => $q->where('remote_host', 'like', "%{$query}%"),
                    'URL / endpoint' => $q->where('uri', 'like', "%{$query}%"),
                    'User-Agent'   => $q->where('user_agent', 'like', "%{$query}%"),
                    'HTTP status'  => $q->where('status', 'like', "%{$query}%"),
                    'Method'       => $q->where('method', 'like', "%{$query}%"),
                    default        => $q->where(function ($q2) use ($query) {
                        $q2->where('remote_host', 'like', "%{$query}%")
                           ->orWhere('uri', 'like', "%{$query}%")
                           ->orWhere('user_agent', 'like', "%{$query}%")
                           ->orWhere('status', 'like', "%{$query}%")
                           ->orWhere('method', 'like', "%{$query}%");
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