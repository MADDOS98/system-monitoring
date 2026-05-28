<?php

namespace App\Services\Alerts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MetricSampleFetcher
{
    private const CONNECTION = 'system_metrics';

    /**
     * Returneaza sample-urile dintre [$from, $to) ca o colectie de obiecte
     * stdClass cu campurile `ts` (int, unix sec) si `value` (float).
     *
     * Trebuie tinut minte timestamp-ul pe langa valoare pentru ca AlertEvaluator
     * are nevoie de ele ca sa calculeze streak-ul de inactivitate (inactive_reset_sec).
     */
    public function samples(string $metric, int $from, int $to): Collection
    {
        [$table, $valueExpr] = match ($metric) {
            'cpu'           => ['cpu_metrics',     'total_usage'],
            'cpu_stolen'    => ['cpu_metrics',     'stolen_usage'],
            'ram'           => ['ram_metrics',     '(used_kb * 100.0 / NULLIF(total_kb, 0))'],
            'disk_io_read'  => ['disk_io_metrics', '(read_bytes  / 60.0 / 1000000.0)'],
            'disk_io_write' => ['disk_io_metrics', '(write_bytes / 60.0 / 1000000.0)'],
            'network_in'    => ['network_metrics', '(rx_bytes * 8.0 / 60.0 / 1000000.0)'],
            'network_out'   => ['network_metrics', '(tx_bytes * 8.0 / 60.0 / 1000000.0)'],
            default         => throw new InvalidArgumentException("Unknown metric: {$metric}"),
        };

        return DB::connection(self::CONNECTION)
            ->table($table)
            ->selectRaw("collected_at AS ts, {$valueExpr} AS value")
            ->where('collected_at', '>=', $from)
            ->where('collected_at', '<',  $to)
            ->whereNotNull(DB::raw($valueExpr))
            ->orderBy('collected_at')
            ->get()
            ->map(fn ($r) => (object) [
                'ts'    => (int)   $r->ts,
                'value' => (float) $r->value,
            ]);
    }
}
