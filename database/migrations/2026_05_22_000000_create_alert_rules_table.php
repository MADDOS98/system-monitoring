<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('metric', 20);
            $table->string('operator', 2);
            $table->float('threshold');
            $table->string('level', 10);
            $table->integer('window_sec');
            $table->float('ratio');
            $table->integer('inactive_reset_sec')->default(15);
            $table->boolean('is_active')->default(true);
            $table->integer('last_evaluated_at')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('metric');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
