<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('neighborhood_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->float('temp_max');
            $table->float('temp_min');
            $table->string('level', 20)->default('normal'); // normal | warning | canicule
            $table->timestamps();
            $table->unique(['neighborhood_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};