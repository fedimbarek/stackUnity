<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outage_risks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('weather_forecast_id')->constrained()->cascadeOnDelete();
            $table->string('risk_level', 20)->default('faible'); // faible | moyen | eleve
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outage_risks');
    }
};