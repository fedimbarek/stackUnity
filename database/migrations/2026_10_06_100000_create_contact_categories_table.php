<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon', 50)->default('bi-telephone');
            $table->string('color', 7)->default('#e8590c');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_categories');
    }
};
