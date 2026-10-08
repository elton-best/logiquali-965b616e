<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->string('criticality_level', 20)->nullable()->after('criticality');
            $table->integer('actual_occurrences')->default(0)->after('estimated_occurrences');
            $table->timestamp('last_occurrence_at')->nullable()->after('actual_occurrences');
        });
    }

    public function down(): void
    {
        Schema::table('risks', function (Blueprint $table) {
            $table->dropColumn(['criticality_level', 'actual_occurrences', 'last_occurrence_at']);
        });
    }
};
