<?php

namespace App\Console\Commands;

use App\Models\ApacheLog;
use App\Models\ConnectionMetric;
use App\Models\CpuMetric;
use App\Models\DiskIoMetric;
use App\Models\DiskUsageMetric;
use App\Models\NetworkMetric;
use App\Models\RamMetric;
use Illuminate\Console\Command;

class SimulateData extends Command
{
    protected $signature   = 'data:simulate {--loop : ruleaza continuu, generand 1 punct/sec/tabel}';
    protected $description = 'Genereaza date fake in toate tabelele de monitorizare (metrics + apache_logs). Cu --loop simuleaza colectare in timp real.';

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

    // Connection metrics: profil per IP local
    private const CONNECTION_IP_PROFILES = [
        '127.0.0.1' => [
            'baseConn' => 14,
            'svcPorts' => [3306, 5432, 6379, 8080, 9000, 11211, 27017],
            'states'   => ['ESTABLISHED' => 0.55, 'TIME_WAIT' => 0.25, 'CLOSE_WAIT' => 0.05, 'LISTEN' => 0.10, 'CLOSE' => 0.05],
        ],
        '127.0.0.53' => [
            'baseConn' => 2,
            'svcPorts' => [53],
            'states'   => ['LISTEN' => 0.7, 'CLOSE' => 0.3],
        ],
        '0.0.0.0' => [
            'baseConn' => 5,
            'svcPorts' => [22, 80, 443, 8080, 5432, 6379],
            'states'   => ['LISTEN' => 1.0],
        ],
        '::1' => [
            'baseConn' => 3,
            'svcPorts' => [6379, 3306, 8080],
            'states'   => ['ESTABLISHED' => 0.65, 'TIME_WAIT' => 0.3, 'CLOSE_WAIT' => 0.05],
        ],
        '::' => [
            'baseConn' => 4,
            'svcPorts' => [22, 80, 443, 8080],
            'states'   => ['LISTEN' => 1.0],
        ],
        '192.168.1.10' => [
            'baseConn' => 10,
            'svcPorts' => [22, 80, 443],
            'states'   => ['ESTABLISHED' => 0.45, 'TIME_WAIT' => 0.35, 'CLOSE_WAIT' => 0.10, 'FIN_WAIT2' => 0.05, 'SYN_SENT' => 0.05],
        ],
    ];

    // Conexiuni curente per IP (stare persistenta, drift cu inertie)
    private array $connLevels = [];

    // Apache logs: pool de IP-uri "regulate" + altele random
    private const APACHE_URIS = [
        '/', '/login', '/register', '/dashboard',
        '/api/users', '/api/orders', '/api/products', '/api/auth/login',
        '/products', '/products/1', '/products/2',
        '/search', '/checkout', '/cart', '/about', '/contact',
        '/static/main.css', '/static/app.js', '/favicon.ico',
    ];
    private const APACHE_USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Firefox/120',
        'Mozilla/5.0 (Linux; Android 10) Mobile Safari/537.36',
        'curl/7.85.0',
        'PostmanRuntime/7.32.0',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    ];
    private const APACHE_REFERERS = [
        '-', '-', '-',                       // ponderat catre "fara referer"
        'https://google.com/search?q=test',
        'https://github.com/explore',
        'https://facebook.com',
    ];
    private array $apacheIpPool = [];

    public function handle(): int
    {
        // Bias per core: stabilit o data per rulare
        for ($c = 0; $c < self::CPU_CORE_COUNT; $c++) {
            $this->cpuCoreBias[$c] = mt_rand(-8, 12);
        }

        // Pornesc nivelurile de conexiuni la baseConn
        foreach (self::CONNECTION_IP_PROFILES as $ip => $profile) {
            $this->connLevels[$ip] = (float) $profile['baseConn'];
        }

        // Pool de ~25 IP-uri "frecvente" (cei care apar des in logs)
        for ($i = 0; $i < 25; $i++) {
            $this->apacheIpPool[] = mt_rand(1, 254) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);
        }

        if (!$this->option('loop')) {
            $this->insertTick(time());
            $this->info('Inserted 1 tick: metrics + apache log.');
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

        // Insert prin Eloquent — observerele inregistrate in AppServiceProvider
        // declanseaza broadcasturile MetricCollected automat. Niciun dispatch
        // manual aici, ca sa fie source-agnostic.
        RamMetric::create($this->buildRamRow($ts, $hour));
        NetworkMetric::create($this->buildNetworkRow($ts, $hour));
        DiskIoMetric::create($this->buildDiskIoRow($ts, $hour));
        CpuMetric::create($this->buildCpuRow($ts, $hour));

        // disk_usage se inregistreaza o data pe minut
        if ($ts % 60 === 0) {
            DiskUsageMetric::create($this->buildDiskUsageRow($ts));
        }

        // connection_metrics se inregistreaza o data pe minut (mai multe randuri / IP).
        // Observer-ul fire-uieste 'connections' agregat dupa fiecare row creat.
        if ($ts % 60 === 0) {
            foreach ($this->buildConnectionRows($ts, $hour) as $row) {
                ConnectionMetric::create($row);
            }
        }

        // apache_logs: 1 row/sec. Observer-ul fire-uieste 'ApacheLogCreated'.
        ApacheLog::create($this->buildApacheLogRow($ts));
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
            'per_core_usage' => $perCore, // array — cast 'array' din model il va encoda la save
            'stolen_usage'   => round($stolenUsage, 2),
        ];
    }

    /**
     * Genereaza cate un rand pentru fiecare IP local activ.
     * Numarul de conexiuni driftuieste catre baseConn * hourFactor cu inertie.
     */
    private function buildConnectionRows(int $ts, int $hour): array
    {
        $hourFactor = match (true) {
            $hour >= 0  && $hour <= 5  => 0.30,
            $hour >= 6  && $hour <= 9  => 0.70,
            $hour >= 10 && $hour <= 18 => 1.25,
            default                    => 0.65,
        };

        $rows = [];

        foreach (self::CONNECTION_IP_PROFILES as $ip => $profile) {
            $target = $profile['baseConn'] * $hourFactor;

            // Drift catre target + zgomot
            $this->connLevels[$ip] += ($target - $this->connLevels[$ip]) * 0.25;
            $this->connLevels[$ip] += mt_rand(-150, 200) / 100;

            $total = max(0, (int) round($this->connLevels[$ip]));

            // Spike ocazional (request burst): ~0.5% sansa
            if (mt_rand(1, 200) === 1) {
                $total += mt_rand(8, 25);
            }

            if ($total === 0) {
                continue;
            }

            $rows[] = [
                'collected_at'      => $ts,
                'local_ip'          => $ip,
                'total_connections' => $total,
                'port_counts'       => $this->distributeConnPorts($total, $profile['svcPorts']), // cast 'array' din model
                'state_counts'      => $this->distributeConnStates($total, $profile['states']),
            ];
        }

        return $rows;
    }

    private function distributeConnPorts(int $total, array $svcPorts): array
    {
        $counts = [];
        for ($i = 0; $i < $total; $i++) {
            if (!empty($svcPorts) && mt_rand(1, 100) <= 65) {
                $port = $svcPorts[array_rand($svcPorts)];
            } else {
                $port = mt_rand(32768, 65535);
            }
            $key = (string) $port;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        return $counts;
    }

    private function distributeConnStates(int $total, array $weights): array
    {
        if ($total === 0) {
            return [];
        }

        $result    = [];
        $remaining = $total;
        $states    = array_keys($weights);
        $lastIdx   = count($states) - 1;

        foreach ($states as $idx => $state) {
            if ($idx === $lastIdx) {
                if ($remaining > 0) {
                    $result[$state] = $remaining;
                }
                break;
            }

            $count = (int) round($total * $weights[$state]);
            $count = min($count, $remaining);
            if ($count > 0) {
                $result[$state] = $count;
                $remaining -= $count;
            }
        }

        return $result;
    }

    /**
     * Genereaza un rand de apache log realist:
     *  - 80% IP din pool-ul de "regulars", 20% IP random
     *  - Status weighted: 200 dominant, 4xx/5xx ocazional
     *  - Method weighted: GET dominant
     *  - Bytes corelat cu status
     */
    private function buildApacheLogRow(int $ts): array
    {
        $ip = mt_rand(1, 100) <= 80
            ? $this->apacheIpPool[array_rand($this->apacheIpPool)]
            : mt_rand(1, 254) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);

        $method = $this->weightedPick([
            'GET'    => 75,
            'POST'   => 15,
            'PUT'    =>  4,
            'DELETE' =>  3,
            'HEAD'   =>  3,
        ]);

        $status = $this->weightedPick([
            200 => 65,
            201 =>  4,
            204 =>  3,
            301 =>  3,
            302 =>  4,
            304 =>  4,
            400 =>  3,
            401 =>  4,
            403 =>  3,
            404 =>  5,
            500 =>  1,
            502 =>  1,
        ]);

        // Bytes corelat cu status: success-ul are payload mai mare
        $bytes = match (true) {
            $status >= 500              => mt_rand(150, 800),
            $status >= 400              => mt_rand(200, 1_500),
            $status === 304             => 0,
            $status >= 300              => mt_rand(0, 500),
            default                     => mt_rand(500, 50_000),
        };

        return [
            'log_time'    => $ts,
            'remote_host' => $ip,
            'ident'       => '-',
            'user'        => '-',
            'method'      => $method,
            'uri'         => self::APACHE_URIS[array_rand(self::APACHE_URIS)],
            'protocol'    => mt_rand(1, 10) <= 7 ? 'HTTP/1.1' : 'HTTP/2',
            'status'      => $status,
            'bytes_sent'  => $bytes,
            'referer'     => self::APACHE_REFERERS[array_rand(self::APACHE_REFERERS)],
            'user_agent'  => self::APACHE_USER_AGENTS[array_rand(self::APACHE_USER_AGENTS)],
        ];
    }

    /**
     * Pick weighted dintr-un dictionar [key => weight].
     */
    private function weightedPick(array $weights)
    {
        $total = array_sum($weights);
        $r = mt_rand(1, $total);
        $acc = 0;
        foreach ($weights as $key => $w) {
            $acc += $w;
            if ($r <= $acc) {
                return $key;
            }
        }
        return array_key_first($weights);
    }
}
