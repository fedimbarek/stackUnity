<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_category_id')
                  ->constrained('contact_categories')
                  ->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_24h')->default(false);
            $table->boolean('is_priority')->default(false); // numéros "appel rapide"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};
