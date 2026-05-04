<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApacheLogsSeeder extends Seeder
{
    public function run(): void
    {
        $total = 10000;
        $chunk = 1000; // insert în batch-uri pentru performanță

        $methods = ['GET', 'POST', 'PUT', 'DELETE'];
        $protocols = ['HTTP/1.1', 'HTTP/2'];
        $statuses = [200, 201, 204, 301, 302, 400, 401, 403, 404, 500, 502, 503];

        $uris = [
            '/', '/login', '/register', '/api/users', '/api/orders',
            '/products', '/products/1', '/search?q=test',
        ];

        $referers = [
            '-', 'https://google.com', 'https://bing.com', 'https://facebook.com'
        ];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) Gecko/20100101 Firefox/120.0',
            'curl/7.68.0',
        ];

        // start time (acum - 10k secunde)
        $startTime = now()->timestamp - $total;

        for ($i = 0; $i < $total; $i += $chunk) {
            $rows = [];

            for ($j = 0; $j < $chunk && ($i + $j) < $total; $j++) {
                $time = $startTime + $i + $j;

                $rows[] = [
                    'log_time'    => $time,
                    'remote_host' => $this->randomIp(),
                    'ident'       => '-',
                    'user'        => rand(0, 10) > 8 ? 'user' . rand(1, 50) : '-',
                    'method'      => $methods[array_rand($methods)],
                    'uri'         => $uris[array_rand($uris)],
                    'protocol'    => $protocols[array_rand($protocols)],
                    'status'      => $statuses[array_rand($statuses)],
                    'bytes_sent'  => rand(200, 50000),
                    'referer'     => $referers[array_rand($referers)],
                    'user_agent'  => $userAgents[array_rand($userAgents)],
                ];
            }

            DB::table('apache_logs')->insert($rows);
        }
    }

    private function randomIp(): string
    {
        return rand(1, 255) . '.' .
               rand(0, 255) . '.' .
               rand(0, 255) . '.' .
               rand(0, 255);
    }
}