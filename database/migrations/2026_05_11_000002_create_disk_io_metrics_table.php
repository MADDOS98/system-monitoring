<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'system_metrics';

    public function up(): void
    {
        Schema::connection('system_metrics')->create('disk_io_metrics', function (Blueprint $table) {
            $table->id();
            $table->integer('collected_at');
            $table->integer('read_bytes');
            $table->integer('write_bytes');
        });
    }

    public function down(): void
    {
        Schema::connection('system_metrics')->dropIfExists('disk_io_metrics');
    }
};
