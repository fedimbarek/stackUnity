<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('equipements', 'etat')) {
            Schema::table('equipements', function (Blueprint $table) {
                $table->string('etat')->default('disponible');
            });
        }

        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('equipement_id')->constrained('equipements')->cascadeOnDelete();
                $table->string('nom');
                $table->string('prenom');
                $table->string('email');
                $table->string('numero', 30);
                $table->date('date_debut');
                $table->date('date_fin');
                $table->timestamp('confirmee_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Rien à défaire ici : ces objets sont déjà gérés par les 2 migrations du 2026_10_06.
    }
};