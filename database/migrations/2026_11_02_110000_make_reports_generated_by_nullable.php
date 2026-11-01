<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['generated_by']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('generated_by')->nullable()->change();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('reports')->whereNull('generated_by')->delete();

        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['generated_by']);
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('generated_by')->nullable(false)->change();
            $table->foreign('generated_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};