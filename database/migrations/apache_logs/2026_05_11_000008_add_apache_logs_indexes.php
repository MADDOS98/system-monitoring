<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apache_logs', function (Blueprint $table) {
            // Primary: orice query filtreaza pe log_time, plus ORDER BY log_time DESC in ApacheLogsTable.
            $table->index('log_time');

            // StatusTable: GROUP BY status intr-o fereastra de timp.
            $table->index(['log_time', 'status']);

            // TopIpsTable: GROUP BY remote_host intr-o fereastra de timp.
            $table->index(['log_time', 'remote_host']);
        });
    }

    public function down(): void
    {
        Schema::table('apache_logs', function (Blueprint $table) {
            $table->dropIndex(['log_time']);
            $table->dropIndex(['log_time', 'status']);
            $table->dropIndex(['log_time', 'remote_host']);
        });
    }
};
