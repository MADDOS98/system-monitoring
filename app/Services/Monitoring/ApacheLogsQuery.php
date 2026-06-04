<?php

namespace App\Services\Monitoring;

use App\Models\ApacheLog;
use App\Models\HostReputation;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class ApacheLogsQuery
{
    private const CONNECTION = 'apache_logs';

    /**
     * Pagina principala (folosita la initial render Livewire si la fallback poll
     * cand user e pe page 2+ / cu search).
     */
    public function paginate(
        int $fromTs,
        int $toTs,
        int $page,
        string $searchQuery,
        string $searchField,
        int $perPage = 20
    ): LengthAwarePaginator {
        return DB::connection(self::CONNECTION)->table('apache_logs')
            ->when($fromTs, fn($q) => $q->where('log_time', '>=', $fromTs))
            ->when($toTs,   fn($q) => $q->where('log_time', '<=', $toTs))
            ->when($searchQuery !== '', function ($q) use ($searchQuery, $searchField) {
                return match($searchField) {
                    'IP'             => $q->where('remote_host', 'like', "%{$searchQuery}%"),
                    'URL / endpoint' => $q->where('uri',         'like', "%{$searchQuery}%"),
                    'User-Agent'     => $q->where('user_agent',  'like', "%{$searchQuery}%"),
                    'HTTP status'    => $q->where('status',      'like', "%{$searchQuery}%"),
                    'Method'         => $q->where('method',      'like', "%{$searchQuery}%"),
                    default          => $q->where(function ($q2) use ($searchQuery) {
                        $q2->where('remote_host', 'like', "%{$searchQuery}%")
                           ->orWhere('uri',         'like', "%{$searchQuery}%")
                           ->orWhere('status',      'like', "%{$searchQuery}%")
                           ->orWhere('method',      'like', "%{$searchQuery}%")
                           ->orWhere('user_agent',  'like', "%{$searchQuery}%");
                    }),
                };
            })
            ->orderByDesc('log_time')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Delta de prepend: rândurile cu id > $sinceId, in ordine cronologica inversa
     * (cele mai noi primele). Limita 200 ca anti-flood pentru cazul cand userul
     * lasa tab-ul in background si revine dupa mult timp.
     */
    public function newSince(int $sinceId, int $limit = 200): array
    {
        if ($sinceId <= 0) {
            return [];
        }

        return DB::connection(self::CONNECTION)->table('apache_logs')
            ->where('id', '>', $sinceId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'log_time', 'method', 'uri', 'status', 'remote_host', 'user_agent', 'bytes_sent'])
            ->map(fn($r) => [
                'id'          => (int) $r->id,
                'log_time'    => (int) $r->log_time,
                'method'      => $r->method,
                'uri'         => $r->uri,
                'status'      => (int) $r->status,
                'remote_host' => $r->remote_host,
                'user_agent'  => $r->user_agent,
                'bytes_sent'  => (int) ($r->bytes_sent ?? 0),
            ])
            ->all();
    }

    /**
     * IP-uri intr-o fereastra, paginate, cu reputatii imbinate.
     *
     * Returneaza un LengthAwarePaginator. Filtrarea pe tab se face IN PHP
     * (post-join cu host_reputations) inainte de paginare — N e mic (10-200
     * IP-uri distinct per fereastra) deci array_slice e ieftin.
     */
    public function topIps(
        int $fromTs,
        int $toTs,
        string $tab = 'All',
        int $page = 1,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $reputationsByIp = DB::connection(self::CONNECTION)
            ->table('host_reputations')
            ->get(['ip', 'host', 'status'])
            ->keyBy('ip');

        $allRows = DB::connection(self::CONNECTION)->table('apache_logs')
            ->when($fromTs, fn($q) => $q->where('log_time', '>=', $fromTs))
            ->when($toTs,   fn($q) => $q->where('log_time', '<=', $toTs))
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
            ->get()
            ->map(function ($row) use ($reputationsByIp) {
                $total = $row->reqs ?: 1;
                $rep   = $reputationsByIp->get($row->ip);

                $tag = null; $host = null; $status = null;
                if ($rep) {
                    $status = (int) $rep->status;
                    $host   = $rep->host;
                    $tag    = match ($status) {
                        HostReputation::STATUS_TRUSTED => 'TRUSTED',
                        HostReputation::STATUS_WARNING => 'WARNING',
                        HostReputation::STATUS_DANGER  => 'DANGER',
                        default                        => null,
                    };
                }

                return [
                    'ip'          => $row->ip,
                    'host'        => $host,
                    'tag'         => $tag,
                    'status'      => $status,
                    'reqs'        => (int) $row->reqs,
                    'total_bytes' => (int) $row->total_bytes,
                    's2xx'        => (int) round($row->s2xx / $total * 100),
                    's3xx'        => (int) round($row->s3xx / $total * 100),
                    's4xx'        => (int) round($row->s4xx / $total * 100),
                    's5xx'        => (int) round($row->s5xx / $total * 100),
                    'last_seen'   => $row->last_seen_ts
                        ? Carbon::now()->diffForHumans(Carbon::createFromTimestamp($row->last_seen_ts), true) . ' ago'
                        : '—',
                ];
            })
            ->filter(fn($ip) => match ($tab) {
                'Whitelisted' => $ip['status'] === HostReputation::STATUS_TRUSTED,
                'Suspicious'  => in_array($ip['status'], [HostReputation::STATUS_WARNING, HostReputation::STATUS_DANGER], true),
                default       => true,
            })
            ->values()
            ->all();

        $total   = count($allRows);
        $page    = max(1, $page);
        $offset  = ($page - 1) * $perPage;
        $items   = array_slice($allRows, $offset, $perPage);

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Distributie pe bucket-uri de status (2xx/3xx/4xx/5xx/other) intr-o fereastra.
     */
    public function byStatus(int $fromTs, int $toTs): array
    {
        $rows = DB::connection(self::CONNECTION)->table('apache_logs')
            ->when($fromTs, fn($q) => $q->where('log_time', '>=', $fromTs))
            ->when($toTs,   fn($q) => $q->where('log_time', '<=', $toTs))
            ->selectRaw('
                CASE
                    WHEN status BETWEEN 200 AND 299 THEN "2xx"
                    WHEN status BETWEEN 300 AND 399 THEN "3xx"
                    WHEN status BETWEEN 400 AND 499 THEN "4xx"
                    WHEN status BETWEEN 500 AND 599 THEN "5xx"
                    ELSE "other"
                END as `group`,
                COUNT(*) as total
            ')
            ->groupBy('group')
            ->get();

        $buckets = ['2xx' => 0, '3xx' => 0, '4xx' => 0, '5xx' => 0, 'other' => 0];
        $total   = 0;
        foreach ($rows as $r) {
            $buckets[$r->group] = (int) $r->total;
            $total += (int) $r->total;
        }
        $buckets['total'] = $total;

        return $buckets;
    }

    /**
     * 24 bin-uri orare cu fereastra dinamica:
     *  - $endTs null    → live mode: sliding now-23h → now (aliniat la ora curenta)
     *  - $endTs setat   → custom mode: ziua LOCALA a $endTs (00:00 → 23:00)
     *
     * Aliniat la ore intregi. Bin 0 = cea mai veche, bin 23 = cea mai recenta.
     * Labels-urile ("HH:00") sunt orele REALE locale — se rotesc cu trecerea timpului
     * in live mode, raman fixe 00..23 in custom mode (zi calendaristica completa).
     *
     * $tz explicit (default config) ca Carbon::createFromTimestamp sa nu cada
     * pe UTC implicit (mostenit din PHP DateTime('@ts')).
     */
    public function peakBins(?int $endTs = null, ?string $tz = null): array
    {
        $tz = $tz ?? config('app.timezone');

        if ($endTs === null) {
            // Live: sliding window ending at the current hour.
            $end = Carbon::now($tz)->startOfHour();
        } else {
            // Custom: end-of-day in $tz, rounded down to start of hour 23.
            $end = Carbon::createFromTimestamp($endTs, $tz)->endOfDay()->startOfHour();
        }

        $start = $end->copy()->subHours(23);

        $startTs = $start->timestamp;
        $endTs   = $end->timestamp + 3599;

        $rows = DB::connection(self::CONNECTION)
            ->table('apache_logs')
            ->whereBetween('log_time', [$startTs, $endTs])
            ->selectRaw(
                'CAST((log_time - ?) / 3600 AS INTEGER) as hour_index, COUNT(*) as total',
                [$startTs]
            )
            ->groupBy('hour_index')
            ->pluck('total', 'hour_index')
            ->toArray();

        $bins  = [];
        $hours = [];
        for ($i = 0; $i < 24; $i++) {
            $hourStart = $start->copy()->addHours($i);
            $bins[$i]  = (int) ($rows[$i] ?? 0);
            $hours[$i] = $hourStart->format('H:00');
        }

        $max = max($bins) ?: 1;

        $values   = array_values($bins);
        $mean     = array_sum($values) / 24;
        $variance = 0;
        foreach ($values as $v) {
            $variance += ($v - $mean) ** 2;
        }
        $variance /= 24;
        $std = sqrt($variance) ?: 1;

        $levels = [];
        for ($i = 0; $i < 24; $i++) {
            $current = $bins[$i];
            $prev    = $bins[$i - 1] ?? 0;
            $next    = $bins[$i + 1] ?? 0;

            $z = ($current - $mean) / $std;

            $localPeak = $current >= max(50, $mean * 1.5)
                && $current > ($prev * 1.4)
                && $current > ($next * 1.4);

            if ($z >= 3) {
                $levels[$i] = 'critical';
            } elseif ($z >= 2 || $localPeak) {
                $levels[$i] = 'warning';
            } else {
                $levels[$i] = 'normal';
            }
        }

        return [
            'bins'   => $bins,
            'hours'  => $hours,
            'max'    => $max,
            'levels' => $levels,
            'start'  => $startTs,
            'end'    => $endTs,
        ];
    }
}
