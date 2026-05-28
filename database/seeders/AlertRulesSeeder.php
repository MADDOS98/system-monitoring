<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use Illuminate\Database\Seeder;

class AlertRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            // CPU group (>)
            ['name' => 'CPU critical', 'metric' => 'cpu', 'operator' => '>', 'threshold' => 90, 'level' => 'critical'],
            ['name' => 'CPU warning',  'metric' => 'cpu', 'operator' => '>', 'threshold' => 75, 'level' => 'warning'],
            ['name' => 'CPU info',     'metric' => 'cpu', 'operator' => '>', 'threshold' => 50, 'level' => 'info'],

            // CPU stolen group (>) — detecta VM contention / "noisy neighbor"
            ['name' => 'CPU stolen critical', 'metric' => 'cpu_stolen', 'operator' => '>', 'threshold' => 15, 'level' => 'critical'],
            ['name' => 'CPU stolen warning',  'metric' => 'cpu_stolen', 'operator' => '>', 'threshold' => 5,  'level' => 'warning'],
            ['name' => 'CPU stolen info',     'metric' => 'cpu_stolen', 'operator' => '>', 'threshold' => 2,  'level' => 'info'],

            // RAM group (>)
            ['name' => 'RAM critical', 'metric' => 'ram', 'operator' => '>', 'threshold' => 90, 'level' => 'critical'],
            ['name' => 'RAM warning',  'metric' => 'ram', 'operator' => '>', 'threshold' => 75, 'level' => 'warning'],
            ['name' => 'RAM info',     'metric' => 'ram', 'operator' => '>', 'threshold' => 50, 'level' => 'info'],

            // Disk write group (>) — MB/s
            ['name' => 'Disk write critical', 'metric' => 'disk_io_write', 'operator' => '>', 'threshold' => 200, 'level' => 'critical'],
            ['name' => 'Disk write warning',  'metric' => 'disk_io_write', 'operator' => '>', 'threshold' => 100, 'level' => 'warning'],
            ['name' => 'Disk write info',     'metric' => 'disk_io_write', 'operator' => '>', 'threshold' => 50,  'level' => 'info'],

            // Disk read group (>) — MB/s
            ['name' => 'Disk read critical', 'metric' => 'disk_io_read', 'operator' => '>', 'threshold' => 200, 'level' => 'critical'],
            ['name' => 'Disk read warning',  'metric' => 'disk_io_read', 'operator' => '>', 'threshold' => 100, 'level' => 'warning'],
            ['name' => 'Disk read info',     'metric' => 'disk_io_read', 'operator' => '>', 'threshold' => 50,  'level' => 'info'],

            // Network in group (>) — Mbps
            ['name' => 'Network in critical', 'metric' => 'network_in', 'operator' => '>', 'threshold' => 100, 'level' => 'critical'],
            ['name' => 'Network in warning',  'metric' => 'network_in', 'operator' => '>', 'threshold' => 50,  'level' => 'warning'],
            ['name' => 'Network in info',     'metric' => 'network_in', 'operator' => '>', 'threshold' => 25,  'level' => 'info'],

            // Network out group (>) — Mbps
            ['name' => 'Network out critical', 'metric' => 'network_out', 'operator' => '>', 'threshold' => 100, 'level' => 'critical'],
            ['name' => 'Network out warning',  'metric' => 'network_out', 'operator' => '>', 'threshold' => 50,  'level' => 'warning'],
            ['name' => 'Network out info',     'metric' => 'network_out', 'operator' => '>', 'threshold' => 25,  'level' => 'info'],
        ];

        foreach ($rules as $rule) {
            AlertRule::updateOrCreate(
                [
                    'metric'   => $rule['metric'],
                    'operator' => $rule['operator'],
                    'level'    => $rule['level'],
                ],
                [
                    'name'               => $rule['name'],
                    'threshold'          => $rule['threshold'],
                    'window_sec'         => 60,
                    'ratio'              => 0.6,
                    'inactive_reset_sec' => 15,
                    'is_active'          => true,
                ]
            );
        }
    }
}
