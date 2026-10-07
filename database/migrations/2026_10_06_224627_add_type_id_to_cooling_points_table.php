<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cooling_points', function (Blueprint $table) {
            $table->foreignId('cooling_point_type_id')
                ->nullable()
                ->after('id')
                ->constrained('cooling_point_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cooling_points', function (Blueprint $table) {
            $table->dropForeign(['cooling_point_type_id']);
            $table->dropColumn('cooling_point_type_id');
        });
    }
};
