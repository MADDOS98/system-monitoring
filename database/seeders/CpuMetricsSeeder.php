<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CpuMetricsSeeder extends Seeder
{
    private const CORE_COUNT = 8;

    public function run(): void
    {
        // 7 zile, câte un punct la fiecare minut => 10080 rânduri
        $totalPoints = 7 * 24 * 60;
        $chunk       = 1000;

        $now   = now()->timestamp;
        $start = $now - ($totalPoints * 60);

        // Bias per core: unele core-uri sunt mai incarcate decat altele
        // (ex: core 0 are mai mult kernel work, alte core-uri sunt mai relaxate)
        $coreBias = [];
        for ($c = 0; $c < self::CORE_COUNT; $c++) {
            $coreBias[$c] = mt_rand(-8, 12); // procentaje fixe pentru sesiune
        }

        // Hot core in desfasurare: un proces ce pin-uieste un core (countdown minute)
        $hotCore     = -1;
        $hotCoreLeft = 0;

        $rows = [];

        for ($i = 0; $i < $totalPoints; $i++) {
            $ts   = $start + ($i * 60);
            $hour = (int) date('G', $ts);

            // Total usage in functie de ora + val sinusoidal + zgomot
            $baseUsage = $this->hourBaseUsage($hour);
            $slowWave  = sin($i / 200 * M_PI) * 5;
            $noise     = mt_rand(-300, 300) / 100;
            $totalUsage = max(2, min(98, $baseUsage + $slowWave + $noise));

            // Spike ocazional (build job, request burst): ~0.4% sansa
            if (mt_rand(1, 250) === 1) {
                $totalUsage = min(98, $totalUsage + mt_rand(20, 40));
            }

            // Hot core: ~0.2% sansa sa porneasca, dureaza 10-40 minute
            if ($hotCoreLeft <= 0 && mt_rand(1, 500) === 1) {
                $hotCore     = mt_rand(0, self::CORE_COUNT - 1);
                $hotCoreLeft = mt_rand(10, 40);
            }

            // Distribuie total intre core-uri
            $perCore = [];
            for ($c = 0; $c < self::CORE_COUNT; $c++) {
                $coreUsage = $totalUsage + $coreBias[$c] + mt_rand(-500, 500) / 100;

                // Hot core trage la 80-100%
                if ($c === $hotCore && $hotCoreLeft > 0) {
                    $coreUsage = max($coreUsage, mt_rand(80, 100));
                }

                $perCore[] = round(max(0, min(100, $coreUsage)), 1);
            }

            if ($hotCoreLeft > 0) {
                $hotCoreLeft--;
            }

            // Stolen usage: pe mediu 0-1%, ocazional spike pana la 15%
            $stolenUsage = mt_rand(100, 500) / 100;
            if (mt_rand(1, 200) === 1) {
                $stolenUsage = mt_rand(300, 1500) / 100;
            }

            $rows[] = [
                'collected_at'   => $ts,
                'total_usage'    => round($totalUsage, 2),
                'per_core_usage' => json_encode($perCore),
                'stolen_usage'   => round($stolenUsage, 2),
            ];

            if (count($rows) === $chunk) {
                DB::connection('system_metrics')->table('cpu_metrics')->insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            DB::connection('system_metrics')->table('cpu_metrics')->insert($rows);
        }
    }

    /**
     * Procentaj de baza CPU per ora.
     *
     * Noapte (00-05): trafic minim, doar cron-uri si idle
     * Dimineata (06-09): start servicii, build-uri matinale
     * Zi (10-18): trafic maxim — query-uri, request-uri
     * Seara (19-23): moderata, backup-uri si rapoarte
     */
    private function hourBaseUsage(int $hour): float
    {
        return match (true) {
            $hour >= 0  && $hour <= 5  => 12.0,
            $hour >= 6  && $hour <= 9  => 32.0,
            $hour >= 10 && $hour <= 18 => 55.0,
            default                    => 28.0,
        };
    }
}
