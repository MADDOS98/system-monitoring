<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NetworkMetricsSeeder extends Seeder
{
    public function run(): void
    {
        // 7 zile, câte un punct la fiecare minut => 10080 rânduri
        $totalPoints = 7 * 24 * 60;
        $chunk       = 1000;

        $now   = now()->timestamp;
        $start = $now - ($totalPoints * 60);

        $rows = [];

        for ($i = 0; $i < $totalPoints; $i++) {
            $ts   = $start + ($i * 60);
            $hour = (int) date('G', $ts);

            // Trafic de baza in functie de ora
            [$baseRx, $baseTx] = $this->hourBaseBytes($hour);

            // Variatie lenta (trend de ore)
            $slowWave = sin($i / 240 * M_PI);
            $rxWave   = (int) ($slowWave * 50_000);
            $txWave   = (int) ($slowWave * 400_000);

            // Zgomot mic per minut
            $rxNoise = mt_rand(-20_000, 20_000);
            $txNoise = mt_rand(-150_000, 150_000);

            $rx = max(1_000, $baseRx + $rxWave + $rxNoise);
            $tx = max(5_000, $baseTx + $txWave + $txNoise);

            // Spike ocazional: burst de trafic (upload mare / download mare)
            if (mt_rand(1, 500) <= 2) {
                $rx += mt_rand(500_000, 2_000_000);   // upload burst
                $tx += mt_rand(2_000_000, 10_000_000); // download burst
            }

            $rows[] = [
                'collected_at' => $ts,
                'rx_bytes'     => $rx,
                'tx_bytes'     => $tx,
            ];

            if (count($rows) === $chunk) {
                DB::connection('system_metrics')->table('network_metrics')->insert($rows);
                $rows = [];
            }
        }

        if (!empty($rows)) {
            DB::connection('system_metrics')->table('network_metrics')->insert($rows);
        }
    }

    /**
     * Returneaza [rx_bytes, tx_bytes] per minut in functie de ora.
     * Server web: TX >> RX (serverul serveste continut mai mult decat primeste)
     *
     * Noapte (00-05): trafic minim
     * Dimineata (06-09): trafic moderat, creste
     * Zi (10-18): trafic maxim
     * Seara (19-23): trafic mediu, scade
     */
    private function hourBaseBytes(int $hour): array
    {
        return match(true) {
            $hour >= 0  && $hour <= 5  => [   10_000,    80_000],  // ~10 KB/min RX, ~80 KB/min TX
            $hour >= 6  && $hour <= 9  => [  150_000, 1_200_000],  // ~150 KB/min RX, ~1.2 MB/min TX
            $hour >= 10 && $hour <= 18 => [  400_000, 3_200_000],  // ~400 KB/min RX, ~3.2 MB/min TX
            default                    => [   80_000,   640_000],  // ~80 KB/min RX, ~640 KB/min TX
        };
    }
}
