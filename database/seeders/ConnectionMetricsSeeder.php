<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConnectionMetricsSeeder extends Seeder
{
    /**
     * Profil per IP local:
     *   baseConn  - numar tipic de conexiuni la varf
     *   svcPorts  - porturi de servicii ce apar pe acel IP
     *   states    - distributie probabilistica a starilor TCP (suma = 1.0)
     */
    private const IP_PROFILES = [
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

    public function run(): void
    {
        // 7 zile, cate un snapshot pe minut. La fiecare snapshot ~6 IP-uri.
        // => ~60480 randuri
        $totalPoints = 7 * 24 * 60;
        $chunk       = 1000;

        $now   = now()->timestamp;
        $start = $now - ($totalPoints * 60);

        $rows = [];

        for ($i = 0; $i < $totalPoints; $i++) {
            $ts         = $start + ($i * 60);
            $hour       = (int) date('G', $ts);
            $hourFactor = $this->hourFactor($hour);

            foreach (self::IP_PROFILES as $ip => $profile) {
                $base  = $profile['baseConn'] * $hourFactor;
                $total = max(0, (int) round($base + mt_rand(-2, 3)));

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
                    'port_counts'       => json_encode($this->distributePorts($total, $profile['svcPorts'])),
                    'state_counts'      => json_encode($this->distributeStates($total, $profile['states'])),
                ];

                if (count($rows) === $chunk) {
                    DB::connection('system_metrics')->table('connection_metrics')->insert($rows);
                    $rows = [];
                }
            }
        }

        if (!empty($rows)) {
            DB::connection('system_metrics')->table('connection_metrics')->insert($rows);
        }
    }

    /**
     * Multiplier per ora pentru a simula trafic mai mare ziua.
     */
    private function hourFactor(int $hour): float
    {
        return match (true) {
            $hour >= 0  && $hour <= 5  => 0.30,
            $hour >= 6  && $hour <= 9  => 0.70,
            $hour >= 10 && $hour <= 18 => 1.25,
            default                    => 0.65,
        };
    }

    /**
     * Distribuie $total conexiuni intre porturi:
     *  - 65% catre porturi de servicii cunoscute
     *  - 35% catre porturi efemere (32768-65535)
     */
    private function distributePorts(int $total, array $svcPorts): array
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

    /**
     * Distribuie $total conexiuni intre stari TCP dupa ponderi.
     * Ultima stare absoarbe resturile de rotunjire.
     */
    private function distributeStates(int $total, array $weights): array
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
}
