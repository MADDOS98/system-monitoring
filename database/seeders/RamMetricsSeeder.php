<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RamMetricsSeeder extends Seeder
{
    public function run(): void
    {
        // 7 zile, câte un punct la fiecare minut => 10080 rânduri
        $totalPoints = 7 * 24 * 60;
        $chunk       = 1000;

        $totalKb   = 16 * 1024 * 1024; // 16 GB în KB
        $usedBase  = 0.45;

        $now   = now()->timestamp;
        $start = $now - ($totalPoints * 60);

        $rows = [];

        for ($i = 0; $i < $totalPoints; $i++) {
            $ts   = $start + ($i * 60);
            $hour = (int) date('G', $ts);
            $hourFactor = $this->hourFactor($hour);
            $slowWave   = sin($i / 180 * M_PI) * 0.04;
            $noise      = mt_rand(-100, 100) / 10000;

            $usedPct = max(0.20, min(0.95, $usedBase + $hourFactor + $slowWave + $noise));

            // Spike ocazional (memory leak simulat)
            if (mt_rand(1, 1000) <= 3) {
                $usedPct = min(0.95, $usedPct + mt_rand(10, 20) / 100);
            }

            $rows[] = [
                'collected_at' => $ts,
                'total_kb'     => $totalKb,
                'used_kb'      => (int) ($totalKb * $usedPct),
            ];

            if (count($rows) === $chunk) {
                DB::connection('system_metrics')->table('ram_metrics')->insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            DB::connection('system_metrics')->table('ram_metrics')->insert($rows);
        }
    }

    private function hourFactor(int $hour): float
    {
        return match(true) {
            $hour >= 0  && $hour <= 5  => -0.10,
            $hour >= 6  && $hour <= 9  =>  0.05,
            $hour >= 10 && $hour <= 18 =>  0.15,
            default                    =>  0.05,
        };
    }
}
