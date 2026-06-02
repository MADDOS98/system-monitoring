<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'process_metrics';

    public function up(): void
    {
        // Agregari pe fereastra temporala grupate dupa proces (top consumers de CPU/RAM/IO,
        // numar de procese active intr-un interval). Mirror al pattern-ului (log_time, X)
        // din apache_logs.
        Schema::connection('process_metrics')->table('process_metrics', function (Blueprint $t) {
            $t->index(['collected_at', 'process_name_id']);
        });

        // Cautare/filtrare dupa textul comenzii in UI (autocomplete, LIKE 'prefix%').
        Schema::connection('process_metrics')->table('process_commands', function (Blueprint $t) {
            $t->index('command');
        });
    }

    public function down(): void
    {
        Schema::connection('process_metrics')->table('process_metrics', function (Blueprint $t) {
            $t->dropIndex(['collected_at', 'process_name_id']);
        });

        Schema::connection('process_metrics')->table('process_commands', function (Blueprint $t) {
            $t->dropIndex(['command']);
        });
    }
};
