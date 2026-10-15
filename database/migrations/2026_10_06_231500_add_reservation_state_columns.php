<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements', function (Blueprint $table) {
            $table->string('etat')->default('disponible');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('confirmee_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('confirmee_at');
        });

        Schema::table('equipements', function (Blueprint $table) {
            $table->dropColumn('etat');
        });
    }
};
