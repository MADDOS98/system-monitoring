<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DiskMetricsSeeder extends Seeder
{
    public function run(): void
    {
        // 7 zile, câte un punct la fiecare minut => 10080 rânduri per tabel
        $totalPoints = 7 * 24 * 60;
        $chunk       = 1000;

        $now   = now()->timestamp;
        $start = $now - ($totalPoints * 60);

        // Disk usage: 500 GB total, porneste la ~60% folosit
        $totalBytes  = 500 * 1024 * 1024 * 1024;
        $usedBytes   = (int) ($totalBytes * 0.60);

        $ioRows    = [];
        $usageRows = [];

        for ($i = 0; $i < $totalPoints; $i++) {
            $ts   = $start + ($i * 60);
            $hour = (int) date('G', $ts);

            // ── disk_io_metrics ──────────────────────────────────────────────
            [$baseRead, $baseWrite] = $this->hourBaseIo($hour);

            // Variatie lenta (val sinusoidal pe parcursul zilei)
            $wave      = sin($i / 200 * M_PI);
            $readWave  = (int) ($wave * $baseRead * 0.15);
            $writeWave = (int) ($wave * $baseWrite * 0.15);

            // Zgomot per minut
            $readNoise  = mt_rand(-1, 1) * mt_rand(0, (int) ($baseRead  * 0.10));
            $writeNoise = mt_rand(-1, 1) * mt_rand(0, (int) ($baseWrite * 0.10));

            $readBytes  = max(50_000, $baseRead  + $readWave  + $readNoise);
            $writeBytes = max(10_000, $baseWrite + $writeWave + $writeNoise);

            // Spike: job de backup / indexare DB (~0.5% sansa)
            if (mt_rand(1, 200) === 1) {
                $readBytes  += mt_rand(200_000_000, 800_000_000); // 200-800 MB burst
                $writeBytes += mt_rand(100_000_000, 400_000_000);
            }

            // Spike: scriere log mare / deploy (~0.3% sansa)
            if (mt_rand(1, 300) === 1) {
                $writeBytes += mt_rand(50_000_000, 200_000_000);
            }

            $ioRows[] = [
                'collected_at' => $ts,
                'read_bytes'   => (int) $readBytes,
                'write_bytes'  => (int) $writeBytes,
            ];

            // ── disk_usage_metrics ───────────────────────────────────────────
            // Disk-ul creste lent (~0.0008% per minut = ~1.15% pe zi)
            $usedBytes += (int) ($totalBytes * 0.000008);

            // Zgomot mic: fisiere temporare create/sterse
            $usedBytes += mt_rand(-500_000, 800_000);

            // Ocazional un deploy / dump DB adauga cateva sute de MB (~0.1%)
            if (mt_rand(1, 1000) === 1) {
                $usedBytes += mt_rand(100_000_000, 500_000_000);
            }

            // Ocazional o curatare de log-uri scade spatiul (~0.05%)
            if (mt_rand(1, 2000) === 1) {
                $usedBytes -= mt_rand(200_000_000, 1_000_000_000);
            }

            $usedBytes = max((int) ($totalBytes * 0.05), min((int) ($totalBytes * 0.97), $usedBytes));

            $usageRows[] = [
                'collected_at' => $ts,
                'total_bytes'  => $totalBytes,
                'used_bytes'   => $usedBytes,
            ];

            if (count($ioRows) === $chunk) {
                DB::connection('system_metrics')->table('disk_io_metrics')->insert($ioRows);
                DB::connection('system_metrics')->table('disk_usage_metrics')->insert($usageRows);
                $ioRows    = [];
                $usageRows = [];
            }
        }

        if (!empty($ioRows)) {
            DB::connection('system_metrics')->table('disk_io_metrics')->insert($ioRows);
            DB::connection('system_metrics')->table('disk_usage_metrics')->insert($usageRows);
        }
    }

    /**
     * [read_bytes/min, write_bytes/min] in functie de ora.
     *
     * Noapte (00-05): activitate minima, ceva cron-uri si log rotation
     * Dimineata (06-09): start servicii, citiri mari la boot/cache warm-up
     * Zi (10-18): trafic maxim — query-uri DB, servire fisiere statice
     * Seara (19-23): moderata, backup-uri incrementale
     */
    private function hourBaseIo(int $hour): array
    {
        return match (true) {
            $hour >= 0  && $hour <= 5  => [   5_000_000,   2_000_000],  //  5 MB/min R,  2 MB/min W
            $hour >= 6  && $hour <= 9  => [  40_000_000,  15_000_000],  // 40 MB/min R, 15 MB/min W
            $hour >= 10 && $hour <= 18 => [ 120_000_000,  50_000_000],  // 120 MB/min R, 50 MB/min W
            default                    => [  25_000_000,  20_000_000],  // 25 MB/min R, 20 MB/min W (backup seara)
        };
    }
}
