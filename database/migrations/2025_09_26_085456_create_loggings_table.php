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
        Schema::create('loggings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('delay_median_ms');
            $table->float('delay_p95_ms');
            $table->float('jitter_avg_ms');
            $table->float('loss_pct');
            $table->float('bitrate_sent_mbps');
            $table->string('ssid')->nullable();
            $table->string('bssid')->nullable();
            $table->string('band')->nullable();
            $table->string('channel')->nullable();
            $table->integer('rssi')->nullable();
            $table->float('score_qos');
            $table->string('category_qos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loggings');
    }
};