<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApacheLogsSeeder extends Seeder
{
    private array $repeatIps;
    private array $botIps;

    public function run(): void
    {
        $total = 10000;
        $chunk = 1000;

        $this->repeatIps = $this->generateRepeatIps(50);
        $this->botIps = $this->generateBotIps(10);

        $methods = ['GET', 'POST', 'PUT', 'DELETE'];

        $protocols = ['HTTP/1.1', 'HTTP/2'];

        $statusWeights = [
            200 => 70,
            201 => 5,
            204 => 5,
            301 => 5,
            302 => 5,
            400 => 4,
            401 => 3,
            403 => 2,
            404 => 4,
            500 => 1,
            502 => 0.5,
            503 => 0.5,
        ];

        $uris = [
            '/',
            '/login',
            '/register',
            '/dashboard',
            '/api/users',
            '/api/orders',
            '/products',
            '/products/1',
            '/search?q=test',
            '/checkout'
        ];

        $startTime = now()->timestamp - $total;

        for ($i = 0; $i < $total; $i += $chunk) {
            $rows = [];

            for ($j = 0; $j < $chunk && ($i + $j) < $total; $j++) {

                $time = $startTime + $i + $j;
                $ip = $this->getRealisticIp();

                $rows[] = [
                    'log_time'    => $time,
                    'remote_host' => $ip,
                    'ident'       => '-',
                    'user'        => rand(0, 10) > 7 ? 'user' . rand(1, 80) : '-',
                    'method'      => $this->randomFrom($methods),
                    'uri'         => $this->weightedUri($uris, $ip),
                    'protocol'    => $this->randomFrom($protocols),
                    'status'      => $this->weightedStatus($statusWeights),
                    'bytes_sent'  => rand(200, 50000),
                    'referer'     => $this->randomReferer(),
                    'user_agent'  => $this->randomUserAgent($ip),
                ];
            }

            DB::connection('apache_logs')->table('apache_logs')->insert($rows);
        }
    }

    private function getRealisticIp(): string
    {
        $roll = rand(1, 100);

        if ($roll <= 10) {
            return $this->botIps[array_rand($this->botIps)];
        }

        if ($roll <= 40) {
            return $this->repeatIps[array_rand($this->repeatIps)];
        }

        return $this->randomIp();
    }

    private function randomIp(): string
    {
        return rand(1, 255) . '.' .
            rand(0, 255) . '.' .
            rand(0, 255) . '.' .
            rand(0, 255);
    }

    private function generateRepeatIps(int $count): array
    {
        $ips = [];
        for ($i = 0; $i < $count; $i++) {
            $ips[] = "192.168." . rand(0, 50) . "." . rand(1, 254);
        }
        return $ips;
    }

    private function generateBotIps(int $count): array
    {
        $ips = [];
        for ($i = 0; $i < $count; $i++) {
            $ips[] = "66.249." . rand(64, 255) . "." . rand(1, 254);
        }
        return $ips;
    }

    private function weightedStatus(array $weights)
    {
        $sum = array_sum($weights);
        $rand = mt_rand(1, (int)($sum * 100)) / 100;

        $current = 0;
        foreach ($weights as $status => $weight) {
            $current += $weight;
            if ($rand <= $current) {
                return $status;
            }
        }

        return 200;
    }

    private function randomFrom(array $arr)
    {
        return $arr[array_rand($arr)];
    }

    private function randomReferer(): string
    {
        $refs = ['-', 'https://google.com', 'https://facebook.com', 'https://bing.com'];
        return $refs[array_rand($refs)];
    }

    private function randomUserAgent(string $ip): string
    {
        $agents = [
            'Mozilla/5.0 Chrome/120',
            'Mozilla/5.0 Firefox/120',
            'curl/7.68.0',
            'PostmanRuntime/7.32.0'
        ];

        return $agents[array_rand($agents)];
    }

    private function weightedUri(array $uris, string $ip): string
    {
        // bot behavior
        if (str_starts_with($ip, '66.249')) {
            return '/robots.txt';
        }

        return $uris[array_rand($uris)];
    }
}