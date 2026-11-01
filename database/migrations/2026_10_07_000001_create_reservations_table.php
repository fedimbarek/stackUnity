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
        if (! Schema::hasColumn('equipements', 'etat')) {
            Schema::table('equipements', function (Blueprint $table) {
                $table->string('etat')->default('disponible')->after('prix_louer');
            });
        }

        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipement_id')->constrained()->cascadeOnDelete();
                $table->string('nom');
                $table->string('prenom');
                $table->string('email');
                $table->string('numero');
                $table->date('date_debut');
                $table->date('date_fin');
                $table->timestamp('confirmee_at')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('reservations', 'confirmee_at')) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->timestamp('confirmee_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // These tables and columns are managed by earlier migrations.
    }
};
