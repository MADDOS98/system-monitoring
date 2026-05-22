<?php

namespace App\Services\Alerts;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MetricSampleFetcher
{
    private const CONNECTION = 'system_metrics';

    public function samples(string $metric, int $from, int $to): Collection
    {
        [$table, $valueExpr] = match ($metric) {
            'cpu'           => ['cpu_metrics',     'total_usage'],
            'ram'           => ['ram_metrics',     '(used_kb * 100.0 / NULLIF(total_kb, 0))'],
            'disk_io_read'  => ['disk_io_metrics', '(read_bytes  / 60.0 / 1000000.0)'],
            'disk_io_write' => ['disk_io_metrics', '(write_bytes / 60.0 / 1000000.0)'],
            'network_in'    => ['network_metrics', '(rx_bytes * 8.0 / 60.0 / 1000000.0)'],
            'network_out'   => ['network_metrics', '(tx_bytes * 8.0 / 60.0 / 1000000.0)'],
            default         => throw new InvalidArgumentException("Unknown metric: {$metric}"),
        };

        $rows = DB::connection(self::CONNECTION)
            ->table($table)
            ->selectRaw("{$valueExpr} AS value")
            ->where('collected_at', '>=', $from)
            ->where('collected_at', '<',  $to)
            ->whereNotNull(DB::raw($valueExpr))
            ->orderBy('collected_at')
            ->pluck('value');

        return $rows->map(fn ($v) => (float) $v);
    }
}
