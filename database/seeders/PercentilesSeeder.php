<?php

namespace Database\Seeders;

use App\Models\AlertRule;
use App\Models\Percentile;
use Illuminate\Database\Seeder;

class PercentilesSeeder extends Seeder
{
    public function run(): void
    {
        // Display name pentru fiecare metric (folosit in coloana 'name').
        // Cheile trebuie sa fie un subset al AlertRule::METRICS.
        $displayNames = [
            'cpu'           => 'CPU',
            'cpu_stolen'    => 'CPU stolen',
            'ram'           => 'RAM',
            'disk_io_read'  => 'Disk read',
            'disk_io_write' => 'Disk write',
            'network_in'    => 'Network in',
            'network_out'   => 'Network out',
        ];

        // P95 + P99 pentru fiecare metric, window 15 min.
        $defaultPercentiles = [95.00, 99.00];

        foreach (AlertRule::METRICS as $metric) {
            $displayName = $displayNames[$metric] ?? $metric;

            foreach ($defaultPercentiles as $p) {
                Percentile::updateOrCreate(
                    [
                        'metric'     => $metric,
                        'percentile' => $p,
                    ],
                    [
                        'name'           => sprintf('%s P%d', $displayName, (int) $p),
                        'window_minutes' => 15,
                        'is_active'      => true,
                    ]
                );
            }
        }
    }
}
