<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_reputations', function (Blueprint $table) {
            $table->id();
            $table->text('ip');
            $table->text('host');
            $table->unsignedTinyInteger('status'); // 1 = trusted, 2 = warning, 3 = danger
            $table->text('reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_reputations');
    }
};
