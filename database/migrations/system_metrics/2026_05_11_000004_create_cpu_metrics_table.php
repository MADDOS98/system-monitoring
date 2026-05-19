<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'system_metrics';

    public function up(): void
    {
        Schema::connection('system_metrics')->create('cpu_metrics', function (Blueprint $table) {
            $table->id();
            $table->integer('collected_at');
            $table->float('total_usage');
            $table->text('per_core_usage');
            $table->float('stolen_usage');
        });
    }

    public function down(): void
    {
        Schema::connection('system_metrics')->dropIfExists('cpu_metrics');
    }
};
