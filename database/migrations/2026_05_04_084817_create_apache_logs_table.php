<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('apache_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('log_time');
            $table->text('remote_host');
            $table->text('ident');
            $table->text('user');
            $table->text('method');
            $table->text('uri');
            $table->text('protocol');
            $table->integer('status');
            $table->integer('bytes_sent');
            $table->text('referer');
            $table->text('user_agent');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apache_logs');
    }
};
