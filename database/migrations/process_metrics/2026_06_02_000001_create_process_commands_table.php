<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'process_metrics';

    public function up(): void
    {
        Schema::connection('process_metrics')->create('process_commands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('process_name_id');
            $table->text('command');

            $table->unique(['process_name_id', 'command']);
            $table->foreign('process_name_id')->references('id')->on('process_names');
        });
    }

    public function down(): void
    {
        Schema::connection('process_metrics')->dropIfExists('process_commands');
    }
};
