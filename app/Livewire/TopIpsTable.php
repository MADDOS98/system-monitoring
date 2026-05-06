<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ApacheLog;

class TopIpsTable extends Component
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
        $topIps = ApacheLog::query()
            ->when($this->from, fn($q) => $q->where('log_time', '>=', $this->from))
            ->when($this->to,   fn($q) => $q->where('log_time', '<=', $this->to))
            ->selectRaw('
                remote_host as ip,
                COUNT(*) as reqs,
                SUM(bytes_sent) as total_bytes,
                MAX(log_time) as last_seen_ts,
                SUM(CASE WHEN status BETWEEN 200 AND 299 THEN 1 ELSE 0 END) as s2xx,
                SUM(CASE WHEN status BETWEEN 300 AND 399 THEN 1 ELSE 0 END) as s3xx,
                SUM(CASE WHEN status BETWEEN 400 AND 499 THEN 1 ELSE 0 END) as s4xx,
                SUM(CASE WHEN status BETWEEN 500 AND 599 THEN 1 ELSE 0 END) as s5xx
            ')
            ->groupBy('remote_host')
            ->orderByDesc('reqs')
            ->limit(15)
            ->get()
            ->map(fn($row) => (object) [
                'ip'          => $row->ip,
                'hostname'    => null,
                'tag'         => null,
                'reqs'        => $row->reqs,
                'total_bytes' => $row->total_bytes,
                's2xx'        => $row->reqs > 0 ? round($row->s2xx / $row->reqs * 100) : 0,
                's3xx'        => $row->reqs > 0 ? round($row->s3xx / $row->reqs * 100) : 0,
                's4xx'        => $row->reqs > 0 ? round($row->s4xx / $row->reqs * 100) : 0,
                's5xx'        => $row->reqs > 0 ? round($row->s5xx / $row->reqs * 100) : 0,
                'last_seen'   => $row->last_seen_ts
                    ? now()->diffForHumans(\Carbon\Carbon::createFromTimestamp($row->last_seen_ts), true) . ' ago'
                    : '—',
            ]);

        return view('livewire.top-ips-table', compact('topIps'));
    }
}