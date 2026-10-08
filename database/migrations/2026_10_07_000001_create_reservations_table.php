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
        Schema::table('equipements', function (Blueprint $table) {
            if (! Schema::hasColumn('equipements', 'etat')) {
                $table->string('etat')->default('disponible')->after('prix_louer');
            }
        });

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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');

        Schema::table('equipements', function (Blueprint $table) {
            if (Schema::hasColumn('equipements', 'etat')) {
                $table->dropColumn('etat');
            }
        });
    }
};
