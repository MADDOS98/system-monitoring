<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dedup defensiv: daca exista deja randuri (alert_rule_id, window_start, window_end)
        // identice, le pastram pe cel mai vechi (MIN(id)) si stergem restul. Necesar inainte
        // de a aplica UNIQUE constraint pentru ca altfel migrarea pica pe date deja prezente.
        DB::statement('
            DELETE FROM alerts
            WHERE id NOT IN (
                SELECT MIN(id) FROM alerts
                GROUP BY alert_rule_id, window_start, window_end
            )
        ');

        Schema::table('alerts', function (Blueprint $table) {
            $table->unique(
                ['alert_rule_id', 'window_start', 'window_end'],
                'alerts_rule_window_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropUnique('alerts_rule_window_unique');
        });
    }
};
