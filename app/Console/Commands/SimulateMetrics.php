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
    private float $rxLevel = 100_000;
    private float $txLevel = 800_000;

    // Spike-uri Network in desfasurare (countdown de secunde)
    private int $rxBurstLeft = 0;
    private int $txBurstLeft = 0;
    private int $rxBurstMag  = 0;
    private int $txBurstMag  = 0;

    // Memory leak simulat la RAM (countdown)
    private int $ramLeakLeft = 0;
    private float $ramLeakRate = 0;

    public function handle(): int
    {
        if (!$this->option('loop')) {
            $this->insertTick(time());
            $this->info('Inserted 1 RAM + 1 Network row.');
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

        $ramRow = $this->buildRamRow($ts, $hour);
        $netRow = $this->buildNetworkRow($ts, $hour);

        DB::connection('system_metrics')->table('ram_metrics')->insert($ramRow);
        DB::connection('system_metrics')->table('network_metrics')->insert($netRow);

        try {
            MetricCollected::dispatch('ram', $ts, [
                'total_kb' => $ramRow['total_kb'],
                'used_kb'  => $ramRow['used_kb'],
            ]);

            MetricCollected::dispatch('network', $ts, [
                'rx_bytes' => $netRow['rx_bytes'],
                'tx_bytes' => $netRow['tx_bytes'],
            ]);
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
            $hour >= 0  && $hour <= 5  => [   10_000,    80_000],
            $hour >= 6  && $hour <= 9  => [  150_000, 1_200_000],
            $hour >= 10 && $hour <= 18 => [  400_000, 3_200_000],
            default                    => [   80_000,   640_000],
        };

        // Burst-uri independente RX / TX: ~0.5% sansa sa porneasca
        if ($this->rxBurstLeft <= 0 && mt_rand(1, 200) <= 1) {
            $this->rxBurstLeft = mt_rand(3, 10);
            $this->rxBurstMag  = mt_rand(800_000, 3_000_000);
        }
        if ($this->txBurstLeft <= 0 && mt_rand(1, 200) <= 1) {
            $this->txBurstLeft = mt_rand(5, 15);
            $this->txBurstMag  = mt_rand(3_000_000, 12_000_000);
        }

        // Drift catre baseline + zgomot mediu
        $this->rxLevel += ($baseRx - $this->rxLevel) * 0.1;
        $this->rxLevel += mt_rand(-15_000, 15_000);
        $this->txLevel += ($baseTx - $this->txLevel) * 0.1;
        $this->txLevel += mt_rand(-120_000, 120_000);

        $rx = (int) max(1_000, $this->rxLevel);
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
}
