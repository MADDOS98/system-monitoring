<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcessMetricsSeeder extends Seeder
{
    /**
     * Catalog de procese realiste pentru un server productie.
     *
     * Pentru fiecare proces:
     *   commands   - 1..N comenzi distincte cu care apare (process_commands)
     *   count      - numar tipic de instante active (process_metrics.count)
     *   baseCpu    - procent CPU per secunda mediu (single-core %). Valoarea
     *                stocata in cpu_pct e cumulativa pe fereastra de 15s,
     *                deci ~= baseCpu * 15 in mod tipic.
     *   baseRam    - RAM tipic in KB (snapshot)
     *   baseRead   - read I/O tipic in bytes/s
     *   baseWrite  - write I/O tipic in bytes/s
     *   volatility - low / mid / high — controleaza zgomotul + frecventa spike-urilor
     */
    private const PROCESSES = [
        'node' => [
            'commands' => ['node /srv/api/server.js', 'node /srv/api/auth.js'],
            'count'    => 3,
            'baseCpu'  => 3.0,    // ~ 45% cumulativ pe 15s
            'baseRam'  => 2_350_000,
            'baseRead' => 145_000,
            'baseWrite'=> 90_000,
            'volatility' => 'high',
        ],
        'python3' => [
            'commands' => ['python3 worker.py --queue=ingest', 'python3 worker.py --queue=email'],
            'count'    => 2,
            'baseCpu'  => 2.8,
            'baseRam'  => 820_000,
            'baseRead' => 125_000,
            'baseWrite'=> 35_000,
            'volatility' => 'high',
        ],
        'postgres' => [
            'commands' => ['postgres: 14/main'],
            'count'    => 1,
            'baseCpu'  => 1.6,
            'baseRam'  => 1_430_000,
            'baseRead' => 135_000,
            'baseWrite'=> 200_000,
            'volatility' => 'mid',
        ],
        'apache2' => [
            'commands' => ['/usr/sbin/apache2 -k start'],
            'count'    => 1,
            'baseCpu'  => 1.1,
            'baseRam'  => 410_000,
            'baseRead' => 180_000,
            'baseWrite'=> 30_000,
            'volatility' => 'mid',
        ],
        'elasticsearch' => [
            'commands' => ['/usr/share/elasticsearch/bin/java -Xmx2g'],
            'count'    => 1,
            'baseCpu'  => 0.9,
            'baseRam'  => 2_150_000,
            'baseRead' => 85_000,
            'baseWrite'=> 60_000,
            'volatility' => 'high',
        ],
        'java' => [
            'commands' => ['java -Dkafka.logs.dir=/var/log/kafka'],
            'count'    => 1,
            'baseCpu'  => 0.85,
            'baseRam'  => 1_840_000,
            'baseRead' => 40_000,
            'baseWrite'=> 95_000,
            'volatility' => 'mid',
        ],
        'php-fpm' => [
            'commands' => ['php-fpm: pool www', 'php-fpm: master process /etc/php/8.2/fpm/php-fpm.conf'],
            'count'    => 1,
            'baseCpu'  => 0.5,
            'baseRam'  => 286_000,
            'baseRead' => 58_000,
            'baseWrite'=> 32_000,
            'volatility' => 'mid',
        ],
        'redis-server' => [
            'commands' => ['/usr/bin/redis-server *:6379'],
            'count'    => 1,
            'baseCpu'  => 0.4,
            'baseRam'  => 209_000,
            'baseRead' => 4_000,
            'baseWrite'=> 8_000,
            'volatility' => 'low',
        ],
        'docker' => [
            'commands' => ['/usr/bin/dockerd -H fd:// --containerd=/run/containerd/containerd.sock'],
            'count'    => 1,
            'baseCpu'  => 0.3,
            'baseRam'  => 367_000,
            'baseRead' => 12_000,
            'baseWrite'=> 14_000,
            'volatility' => 'low',
        ],
        'rsyslogd' => [
            'commands' => ['/usr/sbin/rsyslogd -n -iNONE'],
            'count'    => 1,
            'baseCpu'  => 0.3,
            'baseRam'  => 14_300,
            'baseRead' => 600,
            'baseWrite'=> 320,
            'volatility' => 'low',
        ],
        'containerd' => [
            'commands' => ['/usr/bin/containerd'],
            'count'    => 1,
            'baseCpu'  => 0.22,
            'baseRam'  => 130_000,
            'baseRead' => 7_500,
            'baseWrite'=> 3_800,
            'volatility' => 'low',
        ],
        'nginx' => [
            'commands' => ['nginx: master process /usr/sbin/nginx', 'nginx: worker process'],
            'count'    => 4,
            'baseCpu'  => 0.4,
            'baseRam'  => 51_000,
            'baseRead' => 95_000,
            'baseWrite'=> 25_000,
            'volatility' => 'mid',
        ],
        'mysqld' => [
            'commands' => ['/usr/sbin/mysqld'],
            'count'    => 1,
            'baseCpu'  => 0.7,
            'baseRam'  => 920_000,
            'baseRead' => 75_000,
            'baseWrite'=> 110_000,
            'volatility' => 'mid',
        ],
        'sshd' => [
            'commands' => ['/usr/sbin/sshd -D'],
            'count'    => 1,
            'baseCpu'  => 0.08,
            'baseRam'  => 6_200,
            'baseRead' => 200,
            'baseWrite'=> 100,
            'volatility' => 'low',
        ],
        'systemd' => [
            'commands' => ['/sbin/init'],
            'count'    => 1,
            'baseCpu'  => 0.05,
            'baseRam'  => 12_500,
            'baseRead' => 120,
            'baseWrite'=> 60,
            'volatility' => 'low',
        ],
        'cron' => [
            'commands' => ['/usr/sbin/cron -f'],
            'count'    => 1,
            'baseCpu'  => 0.03,
            'baseRam'  => 3_200,
            'baseRead' => 50,
            'baseWrite'=> 25,
            'volatility' => 'low',
        ],
    ];

    public function run(): void
    {
        $db = DB::connection('process_metrics');

        // 3 zile la cadenta de 15s = 17_280 puncte / proces × 16 procese ≈ 277k randuri.
        $intervalSec = 15;
        $totalPoints = 3 * 24 * 60 * 4;
        $chunk       = 2000;

        // Aliniat la granitele de 15s pentru a fi predictibil cu cadenta colectorului.
        $now   = now()->timestamp;
        $now  -= $now % $intervalSec;
        $start = $now - ($totalPoints * $intervalSec);

        // ── 1) process_names + process_commands ──────────────────────────────
        $nameToId = [];
        foreach (array_keys(self::PROCESSES) as $name) {
            $nameToId[$name] = $db->table('process_names')->insertGetId(['name' => $name]);
        }

        $cmdRows = [];
        foreach (self::PROCESSES as $name => $profile) {
            foreach ($profile['commands'] as $cmd) {
                $cmdRows[] = ['process_name_id' => $nameToId[$name], 'command' => $cmd];
            }
        }
        $db->table('process_commands')->insert($cmdRows);

        // ── 2) State per proces (inertie + spike countdowns) ─────────────────
        $state = [];
        foreach (self::PROCESSES as $name => $profile) {
            $state[$name] = [
                'cpu'         => (float) $profile['baseCpu'],
                'ram'         => (int)   $profile['baseRam'],
                'read'        => (int)   $profile['baseRead'],
                'write'       => (int)   $profile['baseWrite'],
                'count'       => (int)   $profile['count'],
                'spikeLeft'   => 0,   // burst multi-thread (per sec scaling)
                'spikeMag'    => 0,
                'extremeLeft' => 0,   // un sample izolat unde procesul ramane pegged 15s
            ];
        }

        // ── 3) Genereaza esantioane ──────────────────────────────────────────
        $rows = [];

        for ($i = 0; $i < $totalPoints; $i++) {
            $ts         = $start + ($i * $intervalSec);
            $hour       = (int) date('G', $ts);
            $hourFactor = $this->hourFactor($hour);

            foreach (self::PROCESSES as $name => $profile) {
                $vol = $profile['volatility'];

                // ── CPU per secunda (drift catre baseline * hourFactor + zgomot) ──
                $target = $profile['baseCpu'] * $hourFactor;
                $state[$name]['cpu'] += ($target - $state[$name]['cpu']) * 0.10;
                $noise = match ($vol) {
                    'high' => mt_rand(-120, 120) / 100,
                    'mid'  => mt_rand(-60, 60)   / 100,
                    default=> mt_rand(-20, 20)   / 100,
                };
                $state[$name]['cpu'] = max(0, $state[$name]['cpu'] + $noise);
                $perSec = $state[$name]['cpu'];

                // Spike multi-thread: process trage la 30-100% per secunda pentru cateva ticks
                $spikeChance = match ($vol) {
                    'high' => 220,
                    'mid'  => 550,
                    default=> 1800,
                };
                if ($state[$name]['spikeLeft'] <= 0 && mt_rand(1, $spikeChance) === 1) {
                    $state[$name]['spikeLeft'] = mt_rand(2, 6);
                    $state[$name]['spikeMag']  = mt_rand(30, 100); // per-sec %
                }
                if ($state[$name]['spikeLeft'] > 0) {
                    $perSec = max($perSec, $state[$name]['spikeMag'] * mt_rand(70, 100) / 100);
                    $state[$name]['spikeLeft']--;
                }

                // ── Extreme: process pegged ~100% per secunda timp de toate 15s ──
                // → cpu_pct cumulativ ajunge la ~1500%. Acopera cazul tehnic descris.
                if ($state[$name]['extremeLeft'] <= 0 && $vol !== 'low' && mt_rand(1, 6000) === 1) {
                    $state[$name]['extremeLeft'] = 1;
                }
                if ($state[$name]['extremeLeft'] > 0) {
                    $perSec = mt_rand(9700, 10000) / 100; // 97-100% pegged
                    $state[$name]['extremeLeft']--;
                }

                // Conversie per-secunda → cumulativ pe 15 secunde.
                $cpuPct = round(min(1500, max(0, $perSec * 15)), 2);

                // ── RAM: snapshot, drift + memory leak ocazional ──
                $ramTarget = (int) ($profile['baseRam'] * (0.9 + 0.2 * $hourFactor));
                $state[$name]['ram'] += (int) (($ramTarget - $state[$name]['ram']) * 0.03);
                $state[$name]['ram'] += mt_rand(-3_000, 3_000);
                // Memory leak rar pe java / elasticsearch
                if (in_array($name, ['java', 'elasticsearch'], true) && mt_rand(1, 2500) === 1) {
                    $state[$name]['ram'] += mt_rand(80_000, 180_000);
                }
                $state[$name]['ram'] = max(1_000, min(8_000_000, $state[$name]['ram']));

                // ── IO: drift + burst ──
                $readTarget  = (int) ($profile['baseRead']  * $hourFactor);
                $writeTarget = (int) ($profile['baseWrite'] * $hourFactor);
                $state[$name]['read']  += (int) (($readTarget  - $state[$name]['read'])  * 0.15);
                $state[$name]['write'] += (int) (($writeTarget - $state[$name]['write']) * 0.15);
                $state[$name]['read']  += mt_rand(-8_000, 8_000);
                $state[$name]['write'] += mt_rand(-8_000, 8_000);
                if (mt_rand(1, 600) === 1) {
                    $state[$name]['read'] += mt_rand(500_000, 5_000_000);
                }
                if (mt_rand(1, 600) === 1) {
                    $state[$name]['write'] += mt_rand(500_000, 3_000_000);
                }
                $state[$name]['read']  = max(0, $state[$name]['read']);
                $state[$name]['write'] = max(0, $state[$name]['write']);

                // ── Count: random walk cu ancorare la baseline ──
                // Pe termen lung instantele converg spre baseCount; nu deriveaza
                // catre 20+ instante de cron sau nginx asa cum face un walk pur.
                $base = $profile['count'];
                if (mt_rand(1, 350) === 1) {
                    $delta = mt_rand(0, 1) ? 1 : -1;
                    $state[$name]['count'] = max(max(1, $base - 1), min($base + 2, $state[$name]['count'] + $delta));
                }
                $count = $state[$name]['count'];

                $rows[] = [
                    'process_name_id' => $nameToId[$name],
                    'collected_at'    => $ts,
                    'count'           => $count,
                    'ram_kb'          => (int) $state[$name]['ram'],
                    'cpu_pct'         => $cpuPct,
                    'read_bytes'      => (int) $state[$name]['read'],
                    'write_bytes'     => (int) $state[$name]['write'],
                ];

                if (count($rows) >= $chunk) {
                    $db->table('process_metrics')->insert($rows);
                    $rows = [];
                }
            }
        }

        if (!empty($rows)) {
            $db->table('process_metrics')->insert($rows);
        }
    }

    private function hourFactor(int $hour): float
    {
        return match (true) {
            $hour >= 0  && $hour <= 5  => 0.30,
            $hour >= 6  && $hour <= 9  => 0.75,
            $hour >= 10 && $hour <= 18 => 1.30,
            default                    => 0.80,
        };
    }
}
