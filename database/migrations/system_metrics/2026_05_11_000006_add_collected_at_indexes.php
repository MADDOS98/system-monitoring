<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'system_metrics';

    private array $tables = [
        'ram_metrics',
        'network_metrics',
        'cpu_metrics',
        'disk_io_metrics',
        'disk_usage_metrics',
        'connection_metrics',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::connection('system_metrics')->table($table, function (Blueprint $t) {
                $t->index('collected_at');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::connection('system_metrics')->table($table, function (Blueprint $t) {
                $t->dropIndex(['collected_at']);
            });
        }
    }
};
