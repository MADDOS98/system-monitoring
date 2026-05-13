<?php

namespace App\Console\Commands;

use App\Events\MetricCollected;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SimulateMetrics extends Command
{
    protected $signature   = 'metrics:simulate {--loop : ruleaza continuu, generand 1 punct/sec/tabel}';
    protected $description = 'Genereaza date fake pentru ram_metrics si network_metrics. Cu --loop simuleaza colectare in timp real.';

    // RAM: state persistent pentru a face miscarea "lina" (drift + zgomot)
    private float $ramPct = 0.45;

    // Network: state persistent pentru a face traficul sa aiba inertie
    private float $rxLevel = 1_800_000;
    private float $txLevel = 2_500_000;

    // Spike-uri Network in desfasurare (countdown de secunde)
    private int $rxBurstLeft = 0;
    private int $txBurstLeft = 0;
    private int $rxBurstMag  = 0;
    private int $txBurstMag  = 0;

    // Memory leak simulat la RAM (countdown)
    private int $ramLeakLeft = 0;
    private float $ramLeakRate = 0;

    // Disk IO: stare persistenta cu inertie
    private float $readLevel  = 80_000_000;
    private float $writeLevel = 30_000_000;

    // Spike-uri Disk IO in desfasurare
    private int $diskReadBurstLeft  = 0;
    private int $diskWriteBurstLeft = 0;
    private int $diskReadBurstMag   = 0;
    private int $diskWriteBurstMag  = 0;

    // Disk Usage: 500 GB, porneste la ~60%
    private int $diskTotalBytes = 536_870_912_000; // 500 GB
    private float $diskUsedBytes = 322_122_547_200;  // 60%

    // CPU: stare persistenta cu inertie
    private const CPU_CORE_COUNT = 8;
    private float $cpuTotalUsage = 25.0;
    private array $cpuCoreBias   = [];

    // Spike CPU in desfasurare (countdown secunde)
    private int $cpuSpikeLeft = 0;
    private int $cpuSpikeMag  = 0;

    // Hot core: un proces pin-uieste un core (countdown secunde)
    private int $hotCore     = -1;
    private int $hotCoreLeft = 0;

    // Stolen usage spike (hypervisor incarcat, countdown secunde)
    private int $stolenSpikeLeft = 0;
    private float $stolenSpikeMag = 0;

    public function handle(): int
    {
        // Bias per core: stabilit o data per rulare
        for ($c = 0; $c < self::CPU_CORE_COUNT; $c++) {
            $this->cpuCoreBias[$c] = mt_rand(-8, 12);
        }

        if (!$this->option('loop')) {
            $this->insertTick(time());
            $this->info('Inserted 1 row in each metrics table.');
            return self::SUCCESS;
        }

        $this->info('Loop mode. Press Ctrl+C to stop.');

        $next = microtime(true);

        while (true) {
            $now = time();
            $this->insertTick($now);

            // sleep pana la urmatoarea secunda exacta
            $next += 1.0;
            $delta = $next - microtime(true);
            if ($delta > 0) {
                usleep((int) ($delta * 1_000_000));
            } else {
                // am ramas in urma, resetam ancora
                $next = microtime(true);
            }
        }
    }

    private function insertTick(int $ts): void
    {
        $hour = (int) date('G', $ts);

        $ramRow    = $this->buildRamRow($ts, $hour);
        $netRow    = $this->buildNetworkRow($ts, $hour);
        $diskIoRow = $this->buildDiskIoRow($ts, $hour);
        $cpuRow    = $this->buildCpuRow($ts, $hour);

        DB::connection('system_metrics')->table('ram_metrics')->insert($ramRow);
        DB::connection('system_metrics')->table('network_metrics')->insert($netRow);
        DB::connection('system_metrics')->table('disk_io_metrics')->insert($diskIoRow);
        DB::connection('system_metrics')->table('cpu_metrics')->insert($cpuRow);

        // disk_usage se inregistreaza o data pe minut
        $diskUsageRow = null;
        if ($ts % 60 === 0) {
            $diskUsageRow = $this->buildDiskUsageRow($ts);
            DB::connection('system_metrics')->table('disk_usage_metrics')->insert($diskUsageRow);
        }

        try {
            MetricCollected::dispatch('ram', $ts, [
                'total_kb' => $ramRow['total_kb'],
                'used_kb'  => $ramRow['used_kb'],
            ]);

            MetricCollected::dispatch('network', $ts, [
                'rx_bytes' => $netRow['rx_bytes'],
                'tx_bytes' => $netRow['tx_bytes'],
            ]);

            MetricCollected::dispatch('disk_io', $ts, [
                'read_bytes'  => $diskIoRow['read_bytes'],
                'write_bytes' => $diskIoRow['write_bytes'],
            ]);

            MetricCollected::dispatch('cpu', $ts, [
                'total_usage'    => $cpuRow['total_usage'],
                'per_core_usage' => json_decode($cpuRow['per_core_usage'], true),
                'stolen_usage'   => $cpuRow['stolen_usage'],
            ]);

            if ($diskUsageRow !== null) {
                MetricCollected::dispatch('disk_usage', $ts, [
                    'total_bytes' => $diskUsageRow['total_bytes'],
                    'used_bytes'  => $diskUsageRow['used_bytes'],
                ]);
            }
        } catch (\Throwable $e) {
            // Reverb e oprit sau inaccesibil — continuam fara broadcast
            $this->warn('Broadcast failed: ' . $e->getMessage());
        }
    }

    private function buildRamRow(int $ts, int $hour): array
    {
        $totalKb = 16 * 1024 * 1024;

        // Target pe baza orei: noaptea joasa, ziua mare
        $target = match (true) {
            $hour >= 0  && $hour <= 5  => 0.30,
            $hour >= 6  && $hour <= 9  => 0.50,
            $hour >= 10 && $hour <= 18 => 0.65,
            default                    => 0.50,
        };

        // Memory leak simulat: 0.3% sansa sa porneasca, dureaza 60-180s
        if ($this->ramLeakLeft <= 0 && mt_rand(1, 1000) <= 3) {
            $this->ramLeakLeft = mt_rand(60, 180);
            $this->ramLeakRate = mt_rand(15, 40) / 10000; // crestere pe secunda
        }

        if ($this->ramLeakLeft > 0) {
            $this->ramPct += $this->ramLeakRate;
            $this->ramLeakLeft--;
        } else {
            // drift catre target + zgomot mic
            $this->ramPct += ($target - $this->ramPct) * 0.02;
            $this->ramPct += mt_rand(-15, 15) / 10000;
        }

        $this->ramPct = max(0.20, min(0.95, $this->ramPct));

        return [
            'collected_at' => $ts,
            'total_kb'     => $totalKb,
            'used_kb'      => (int) ($totalKb * $this->ramPct),
        ];
    }

    private function buildNetworkRow(int $ts, int $hour): array
    {
        // Baseline traffic per minut (folosit ca level "natural")
        [$baseRx, $baseTx] = match (true) {
            $hour >= 0  && $hour <= 5  => [  200_000,    300_000],
            $hour >= 6  && $hour <= 9  => [ 1_500_000,  2_000_000],
            $hour >= 10 && $hour <= 18 => [ 3_500_000,  4_500_000],
            default                    => [ 1_000_000,  1_400_000],
        };

        // Burst-uri independente RX / TX: ~0.5% sansa sa porneasca
        if ($this->rxBurstLeft <= 0 && mt_rand(1, 200) <= 1) {
            $this->rxBurstLeft = mt_rand(3, 12);
            $this->rxBurstMag  = mt_rand(3_000_000, 10_000_000);
        }
        if ($this->txBurstLeft <= 0 && mt_rand(1, 200) <= 1) {
            $this->txBurstLeft = mt_rand(5, 15);
            $this->txBurstMag  = mt_rand(3_000_000, 12_000_000);
        }

        // Drift catre baseline + zgomot mediu
        $this->rxLevel += ($baseRx - $this->rxLevel) * 0.1;
        $this->rxLevel += mt_rand(-120_000, 120_000);
        $this->txLevel += ($baseTx - $this->txLevel) * 0.1;
        $this->txLevel += mt_rand(-150_000, 150_000);

        $rx = (int) max(2_000, $this->rxLevel);
        $tx = (int) max(5_000, $this->txLevel);

        if ($this->rxBurstLeft > 0) {
            $rx += (int) ($this->rxBurstMag * mt_rand(70, 100) / 100);
            $this->rxBurstLeft--;
        }
        if ($this->txBurstLeft > 0) {
            $tx += (int) ($this->txBurstMag * mt_rand(70, 100) / 100);
            $this->txBurstLeft--;
        }

        return [
            'collected_at' => $ts,
            'rx_bytes'     => $rx,
            'tx_bytes'     => $tx,
        ];
    }

    private function buildDiskIoRow(int $ts, int $hour): array
    {
        [$baseRead, $baseWrite] = match (true) {
            $hour >= 0  && $hour <= 5  => [   5_000_000,   2_000_000],
            $hour >= 6  && $hour <= 9  => [  40_000_000,  15_000_000],
            $hour >= 10 && $hour <= 18 => [ 120_000_000,  50_000_000],
            default                    => [  25_000_000,  20_000_000],
        };

        // Burst-uri independente read/write: ~0.5% sansa sa porneasca
        if ($this->diskReadBurstLeft <= 0 && mt_rand(1, 200) <= 1) {
            $this->diskReadBurstLeft = mt_rand(3, 10);
            $this->diskReadBurstMag  = mt_rand(200_000_000, 800_000_000);
        }
        if ($this->diskWriteBurstLeft <= 0 && mt_rand(1, 200) <= 1) {
            $this->diskWriteBurstLeft = mt_rand(3, 10);
            $this->diskWriteBurstMag  = mt_rand(100_000_000, 400_000_000);
        }

        // Drift catre baseline + zgomot
        $this->readLevel  += ($baseRead  - $this->readLevel)  * 0.1;
        $this->readLevel  += mt_rand(-1, 1) * mt_rand(0, (int) ($baseRead  * 0.08));
        $this->writeLevel += ($baseWrite - $this->writeLevel) * 0.1;
        $this->writeLevel += mt_rand(-1, 1) * mt_rand(0, (int) ($baseWrite * 0.08));

        $read  = (int) max(50_000, $this->readLevel);
        $write = (int) max(10_000, $this->writeLevel);

        if ($this->diskReadBurstLeft > 0) {
            $read += (int) ($this->diskReadBurstMag * mt_rand(70, 100) / 100);
            $this->diskReadBurstLeft--;
        }
        if ($this->diskWriteBurstLeft > 0) {
            $write += (int) ($this->diskWriteBurstMag * mt_rand(70, 100) / 100);
            $this->diskWriteBurstLeft--;
        }

        return [
            'collected_at' => $ts,
            'read_bytes'   => $read,
            'write_bytes'  => $write,
        ];
    }

    private function buildDiskUsageRow(int $ts): array
    {
        // Crestere lenta ~1 KB/s (log-uri, sesiuni, cache)
        $this->diskUsedBytes += mt_rand(500, 2_000);

        // Zgomot mic: fisiere temporare create/sterse
        $this->diskUsedBytes += mt_rand(-200_000, 300_000);

        // Ocazional un deploy / dump DB (+100-500 MB, 0.05% sansa)
        if (mt_rand(1, 2000) === 1) {
            $this->diskUsedBytes += mt_rand(100_000_000, 500_000_000);
        }

        // Ocazional o curatare de log-uri (-200 MB - 1 GB, 0.025% sansa)
        if (mt_rand(1, 4000) === 1) {
            $this->diskUsedBytes -= mt_rand(200_000_000, 1_000_000_000);
        }

        $this->diskUsedBytes = max(
            $this->diskTotalBytes * 0.05,
            min($this->diskTotalBytes * 0.97, $this->diskUsedBytes)
        );

        return [
            'collected_at' => $ts,
            'total_bytes'  => $this->diskTotalBytes,
            'used_bytes'   => (int) $this->diskUsedBytes,
        ];
    }

    private function buildCpuRow(int $ts, int $hour): array
    {
        // Target pe baza orei
        $target = match (true) {
            $hour >= 0  && $hour <= 5  => 12.0,
            $hour >= 6  && $hour <= 9  => 32.0,
            $hour >= 10 && $hour <= 18 => 55.0,
            default                    => 28.0,
        };

        // Spike CPU: build job / request burst (~0.3% sansa)
        if ($this->cpuSpikeLeft <= 0 && mt_rand(1, 300) === 1) {
            $this->cpuSpikeLeft = mt_rand(5, 20);
            $this->cpuSpikeMag  = mt_rand(20, 40);
        }

        // Drift catre target + zgomot
        $this->cpuTotalUsage += ($target - $this->cpuTotalUsage) * 0.05;
        $this->cpuTotalUsage += mt_rand(-200, 200) / 100;

        if ($this->cpuSpikeLeft > 0) {
            $this->cpuTotalUsage += $this->cpuSpikeMag * mt_rand(70, 100) / 100 * 0.3;
            $this->cpuSpikeLeft--;
        }

        $this->cpuTotalUsage = max(2.0, min(98.0, $this->cpuTotalUsage));

        // Hot core: ~0.1% sansa sa porneasca, dureaza 30-180s
        if ($this->hotCoreLeft <= 0 && mt_rand(1, 1000) === 1) {
            $this->hotCore     = mt_rand(0, self::CPU_CORE_COUNT - 1);
            $this->hotCoreLeft = mt_rand(30, 180);
        }

        // Distribuie pe cores
        $perCore = [];
        for ($c = 0; $c < self::CPU_CORE_COUNT; $c++) {
            $coreUsage = $this->cpuTotalUsage + $this->cpuCoreBias[$c] + mt_rand(-500, 500) / 100;

            if ($c === $this->hotCore && $this->hotCoreLeft > 0) {
                $coreUsage = max($coreUsage, mt_rand(80, 100));
            }

            $perCore[] = round(max(0, min(100, $coreUsage)), 1);
        }

        if ($this->hotCoreLeft > 0) {
            $this->hotCoreLeft--;
        }

        // Stolen usage: pe mediu 1-5%, spike rar 3-15% (hypervisor incarcat)
        if ($this->stolenSpikeLeft <= 0 && mt_rand(1, 400) === 1) {
            $this->stolenSpikeLeft = mt_rand(3, 15);
            $this->stolenSpikeMag  = mt_rand(300, 1500) / 100;
        }

        $stolenUsage = mt_rand(100, 500) / 100;
        if ($this->stolenSpikeLeft > 0) {
            $stolenUsage = $this->stolenSpikeMag;
            $this->stolenSpikeLeft--;
        }

        return [
            'collected_at'   => $ts,
            'total_usage'    => round($this->cpuTotalUsage, 2),
            'per_core_usage' => json_encode($perCore),
            'stolen_usage'   => round($stolenUsage, 2),
        ];
    }
}
