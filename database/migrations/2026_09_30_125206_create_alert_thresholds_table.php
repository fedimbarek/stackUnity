<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->unique(); // warning | canicule
            $table->float('temp_max');             // température max prévue qui déclenche ce niveau
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_thresholds');
    }
};