<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RetentionSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Defaulturi initiale: 86400 minute = 60 zile pentru toate cele 3 categorii.
        // insertOrIgnore => seederul e idempotent (rulabil de mai multe ori fara duplicari).
        DB::connection('system_metrics')
            ->table('retention_settings')
            ->insertOrIgnore([
                ['constant' => 'METRICS',     'minutes' => 86400],
                ['constant' => 'PROCESSES',   'minutes' => 86400],
                ['constant' => 'APACHE_LOGS', 'minutes' => 86400],
            ]);
    }
}
