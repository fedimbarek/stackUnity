<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooling_points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('park');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('source')->default('osm');
            $table->string('osm_id')->nullable();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooling_points');
    }
};
