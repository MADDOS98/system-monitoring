<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'process_metrics';

    public function up(): void
    {
        Schema::connection('process_metrics')->create('process_names', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
    }

    public function down(): void
    {
        Schema::connection('process_metrics')->dropIfExists('process_names');
    }
};
