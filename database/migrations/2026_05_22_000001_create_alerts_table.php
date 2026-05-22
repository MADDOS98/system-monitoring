<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_rule_id')
                  ->constrained('alert_rules')
                  ->cascadeOnDelete();
            $table->string('level', 10);
            $table->string('metric', 20);
            $table->float('threshold');
            $table->string('operator', 2);
            $table->float('ratio_required');
            $table->float('ratio_observed');
            $table->integer('sample_count');
            $table->integer('matched_count');
            $table->float('peak_value');
            $table->integer('window_start');
            $table->integer('window_end');
            $table->string('message', 255);
            $table->integer('read_at')->nullable();
            $table->timestamps();

            $table->index(['read_at', 'window_end']);
            $table->index('alert_rule_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
