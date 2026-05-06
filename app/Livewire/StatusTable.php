<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ApacheLog;

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
        $byStatus = ApacheLog::query()
            ->when($this->from, fn($q) => $q->where('log_time', '>=', $this->from))
            ->when($this->to,   fn($q) => $q->where('log_time', '<=', $this->to))
            ->selectRaw('
                status,
                COUNT(*) as total,
                CASE
                    WHEN status BETWEEN 200 AND 299 THEN "2xx"
                    WHEN status BETWEEN 300 AND 399 THEN "3xx"
                    WHEN status BETWEEN 400 AND 499 THEN "4xx"
                    WHEN status BETWEEN 500 AND 599 THEN "5xx"
                    ELSE "other"
                END as `group`
            ')
            ->groupBy('status', 'group')
            ->orderBy('status')
            ->get()
            ->groupBy('group')
            ->map(fn($rows, $group) => (object) [
                'group' => $group,
                'total' => $rows->sum('total'),
            ])
            ->values();

        $totalStat = $byStatus->sum('total') ?: 1;

        return view('livewire.status-table', compact('byStatus', 'totalStat'));
    }
}