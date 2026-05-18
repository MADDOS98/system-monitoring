<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\ApacheLog;
use App\Models\HostReputation;

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
        // Map IP -> HostReputation row (status + host) pentru lookup O(1)
        $reputationsByIp = HostReputation::all()->keyBy('ip');

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
            ->map(function ($row) use ($reputationsByIp) {
                $total = $row->reqs ?: 1;
                $rep   = $reputationsByIp->get($row->ip);

                $tag    = null;
                $host   = null;
                $status = null;
                if ($rep) {
                    $status = $rep->status;
                    $host   = $rep->host;
                    $tag    = match ($rep->status) {
                        HostReputation::STATUS_TRUSTED => 'TRUSTED',
                        HostReputation::STATUS_WARNING => 'WARNING',
                        HostReputation::STATUS_DANGER  => 'DANGER',
                        default                        => null,
                    };
                }

                return (object) [
                    'ip'          => $row->ip,
                    'host'        => $host,
                    'tag'         => $tag,
                    'status'      => $status,
                    'reqs'        => $row->reqs,
                    'total_bytes' => $row->total_bytes,
                    's2xx'        => round($row->s2xx / $total * 100),
                    's3xx'        => round($row->s3xx / $total * 100),
                    's4xx'        => round($row->s4xx / $total * 100),
                    's5xx'        => round($row->s5xx / $total * 100),
                    'last_seen'   => $row->last_seen_ts
                        ? now()->diffForHumans(\Carbon\Carbon::createFromTimestamp($row->last_seen_ts), true) . ' ago'
                        : '—',
                ];
            })
            ->filter(fn($ip) => match ($this->tab) {
                'Whitelisted' => $ip->status === HostReputation::STATUS_TRUSTED,
                'Suspicious'  => in_array($ip->status, [HostReputation::STATUS_WARNING, HostReputation::STATUS_DANGER], true),
                default       => true,
            });

        return view('livewire.top-ips-table', compact('topIps'));
    }
}
