<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->text('ai_summary')->nullable();
            $table->timestamp('ai_generated_at')->nullable();
        });

        Schema::table('outage_reports', function (Blueprint $table) {
            $table->float('trust_weight')->default(1);
            $table->json('trust_flags')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['ai_summary', 'ai_generated_at']);
        });
        Schema::table('outage_reports', function (Blueprint $table) {
            $table->dropColumn(['trust_weight', 'trust_flags']);
        });
    }
};