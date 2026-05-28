<?php

namespace App\Services\Percentiles;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PercentileCalculator
{
    private const CONNECTION = 'system_metrics';

    /**
     * Calculeaza percentila p (0-100) si statistici asociate pentru un metric
     * intr-o fereastra rolling care se termina la $endTs (default = now).
     *
     * Returneaza:
     *   - value:        valoarea la percentila p
     *   - min, avg, max: statistici pe aceeasi fereastra
     *   - sample_count: cate sample-uri au fost in fereastra
     *   - window_from, window_to: intervalul efectiv calculat
     * SAU null daca nu sunt sample-uri in fereastra (evita div-by-zero, UI poate decide ce afiseaza).
     */
    public function compute(
        string $metric,
        float $percentile,
        int $windowMinutes,
        ?int $endTs = null,
    ): ?array {
        if ($percentile < 0 || $percentile > 100) {
            throw new InvalidArgumentException("Percentile must be in [0, 100], got {$percentile}");
        }
        if ($windowMinutes < 1) {
            throw new InvalidArgumentException("Window must be >= 1 minute, got {$windowMinutes}");
        }

        [$table, $valueExpr] = $this->resolveMetric($metric);

        $endTs   ??= time();
        $startTs = $endTs - ($windowMinutes * 60);

        // Query 1: agregate cu COUNT/MIN/AVG/MAX intr-o singura citire.
        // NULL-urile sunt filtrate (ex. RAM cand total_kb=0).
        $aggs = DB::connection(self::CONNECTION)
            ->table($table)
            ->selectRaw("
                COUNT({$valueExpr}) AS sample_count,
                MIN({$valueExpr})   AS min_value,
                AVG({$valueExpr})   AS avg_value,
                MAX({$valueExpr})   AS max_value
            ")
            ->whereBetween('collected_at', [$startTs, $endTs])
            ->whereNotNull(DB::raw($valueExpr))
            ->first();

        $count = (int) ($aggs->sample_count ?? 0);
        if ($count === 0) {
            return null;
        }

        // Query 2: valoarea la percentila — ORDER BY + LIMIT 1 OFFSET (count-1) * p/100.
        // Pentru p=95, count=100 → offset 94 (al 95-lea cel mai mic, adica P95 inclusiv).
        $offset = (int) floor(($count - 1) * ($percentile / 100.0));

        $pValue = (float) DB::connection(self::CONNECTION)
            ->table($table)
            ->selectRaw("{$valueExpr} AS value")
            ->whereBetween('collected_at', [$startTs, $endTs])
            ->whereNotNull(DB::raw($valueExpr))
            ->orderBy('value')
            ->offset($offset)
            ->limit(1)
            ->value('value');

        return [
            'value'        => round($pValue, 2),
            'min'          => round((float) $aggs->min_value, 2),
            'avg'          => round((float) $aggs->avg_value, 2),
            'max'          => round((float) $aggs->max_value, 2),
            'sample_count' => $count,
            'window_from'  => $startTs,
            'window_to'    => $endTs,
        ];
    }

    /**
     * Mapeaza metric-name → (tabel, expresie SQL pentru valoare).
     * Pastrat in sincronizare cu MetricSampleFetcher::samples().
     */
    private function resolveMetric(string $metric): array
    {
        return match ($metric) {
            'cpu'           => ['cpu_metrics',     'total_usage'],
            'cpu_stolen'    => ['cpu_metrics',     'stolen_usage'],
            'ram'           => ['ram_metrics',     '(used_kb * 100.0 / NULLIF(total_kb, 0))'],
            'disk_io_read'  => ['disk_io_metrics', '(read_bytes  / 60.0 / 1000000.0)'],
            'disk_io_write' => ['disk_io_metrics', '(write_bytes / 60.0 / 1000000.0)'],
            'network_in'    => ['network_metrics', '(rx_bytes * 8.0 / 60.0 / 1000000.0)'],
            'network_out'   => ['network_metrics', '(tx_bytes * 8.0 / 60.0 / 1000000.0)'],
            default         => throw new InvalidArgumentException("Unknown metric: {$metric}"),
        };
    }
}
