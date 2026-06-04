<?php

namespace App\Services\Percentiles;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PercentileCalculator
{
    private const CONNECTION = 'system_metrics';

    /**
     * Calculeaza percentila p (0-100), median si statistici asociate pentru un metric
     * intr-o fereastra rolling care se termina la $endTs (default = now).
     *
     * Strategie: O SINGURA query care fetch-uieste TOATE valorile sortate ASC.
     * Stats (count/min/avg/max) + percentile + median sunt calculate IN PHP din
     * array-ul rezultat. Anterior erau 2 query-uri per call (stats + OFFSET),
     * iar PercentileCard apela compute() de doua ori (pentru pct si pentru median)
     * → 4 query-uri per card. Acum e 1.
     *
     * Trade-off: incarca toate valorile in memorie. Pentru ferestre tipice
     * (5min-60min @ 1s cadence = 300-3600 valori) e neglijabil; pentru ferestre
     * extreme (>100k valori) consideram un cap la nivel de configurare.
     *
     * Returneaza:
     *   - value:        valoarea la percentila p
     *   - median:       valoarea la P50 (referinta pentru tick-ul "med" pe slider)
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

        // O singura citire: toate valorile sortate ASC. NULL-urile sunt filtrate
        // in WHERE (ex. RAM cand total_kb=0).
        $values = DB::connection(self::CONNECTION)
            ->table($table)
            ->selectRaw("{$valueExpr} AS value")
            ->whereBetween('collected_at', [$startTs, $endTs])
            ->whereNotNull(DB::raw($valueExpr))
            ->orderBy('value')
            ->pluck('value')
            ->all();

        $count = count($values);
        if ($count === 0) {
            return null;
        }

        // Cast la float — pluck poate intoarce string-uri in functie de driver.
        $values = array_map('floatval', $values);

        // Nearest-rank: index = floor((n-1) * p/100), 0-indexed.
        // Pentru p=95, count=100 → idx 94 (al 95-lea cel mai mic, adica P95 inclusiv).
        $pctIdx    = (int) floor(($count - 1) * ($percentile / 100.0));
        $medianIdx = (int) floor(($count - 1) * 0.5);

        return [
            'value'        => round($values[$pctIdx],   2),
            'median'       => round($values[$medianIdx], 2),
            'min'          => round($values[0],          2),
            'avg'          => round(array_sum($values) / $count, 2),
            'max'          => round($values[$count - 1], 2),
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
