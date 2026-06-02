<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'process_metrics';

    public function up(): void
    {
        Schema::connection('process_metrics')->create('process_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_name_id');
            $table->integer('collected_at');
            $table->integer('count');
            $table->integer('ram_kb');
            $table->float('cpu_pct');
            $table->integer('read_bytes');
            $table->integer('write_bytes');

            $table->foreign('process_name_id')->references('id')->on('process_names');
            $table->index('collected_at');
            $table->index(['process_name_id', 'collected_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('process_metrics')->dropIfExists('process_metrics');
    }
};
