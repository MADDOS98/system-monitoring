<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'system_metrics';

    public function up(): void
    {
        Schema::connection('system_metrics')->create('connection_metrics', function (Blueprint $table) {
            $table->id();
            $table->integer('collected_at');
            $table->text('local_ip');
            $table->integer('total_connections');
            $table->text('port_counts');
            $table->text('state_counts');
        });
    }

    public function down(): void
    {
        Schema::connection('system_metrics')->dropIfExists('connection_metrics');
    }
};
