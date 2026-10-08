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
        Schema::table('responsibilities', function (Blueprint $table) {
            // $table->foreignId('enterprise_id')->nullable()->after('id')->constrained('enterprises')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('responsibilities', function (Blueprint $table) {
            $table->dropForeign(['enterprise_id']);
            $table->dropColumn('enterprise_id');
        });
    }
};
