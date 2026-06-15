<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'system_metrics';

    public function up(): void
    {
        Schema::connection('system_metrics')->create('retention_settings', function (Blueprint $table) {
            $table->string('constant')->primary();
            $table->integer('minutes');
        });
    }

    public function down(): void
    {
        Schema::connection('system_metrics')->dropIfExists('retention_settings');
    }
};
