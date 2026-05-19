<?php

namespace App\Observers;

use App\Events\MetricCollected;
use App\Models\ConnectionMetric;

class ConnectionMetricObserver
{
    private const CLOSED_STATES = [
        'CLOSE', 'CLOSE_WAIT', 'TIME_WAIT', 'FIN_WAIT1', 'FIN_WAIT2', 'LAST_ACK', 'CLOSING',
    ];

    /**
     * Connection metrics se inserează în batch (mai multe IP-uri / minute), dar
     * fiecare insert via Eloquent declanseaza acest observer. Calculam agregatul
     * curent (toate randurile cu acelasi collected_at) si fire-uim 'connections'
     * event cu aceeasi structura pe care o asteapta frontend-ul.
     *
     * Frontend-ul va primi mai multe evenimente in succesiune rapida (cate unul
     * per rand inserat), ultimul avand agregatul complet — apoi se stabilizeaza.
     */
    public function created(ConnectionMetric $m): void
    {
        try {
            $rows = ConnectionMetric::where('collected_at', $m->collected_at)->get();

            $totalEst = 0;
            $totalClosed = 0;
            $totalOther = 0;
            $totalConn = 0;
            $byIp = [];

            foreach ($rows as $row) {
                $est = 0; $closed = 0; $other = 0;
                foreach (($row->state_counts ?? []) as $state => $count) {
                    if ($state === 'ESTABLISHED') {
                        $est += $count;
                    } elseif (in_array($state, self::CLOSED_STATES, true)) {
                        $closed += $count;
                    } else {
                        $other += $count;
                    }
                }
                $totalEst    += $est;
                $totalClosed += $closed;
                $totalOther  += $other;
                $totalConn   += $row->total_connections;
                $byIp[] = [
                    'ip'          => $row->local_ip,
                    'total'       => $row->total_connections,
                    'established' => $est,
                    'closed'      => $closed,
                    'other'       => $other,
                ];
            }

            usort($byIp, fn($a, $b) => $b['total'] <=> $a['total']);

            MetricCollected::dispatch('connections', (int) $m->collected_at, [
                'total'        => $totalConn,
                'established'  => $totalEst,
                'closed_other' => $totalClosed + $totalOther,
                'by_ip'        => $byIp,
            ]);
        } catch (\Throwable $e) {
            // Reverb oprit — nu blocam insertul.
        }
    }
}
