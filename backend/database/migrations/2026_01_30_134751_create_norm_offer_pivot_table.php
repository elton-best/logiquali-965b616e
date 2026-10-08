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
        // 1. Remove norm_id column from offers table if it exists
        if (Schema::hasColumn('offers', 'norm_id')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->dropForeign(['norm_id']);
                $table->dropColumn('norm_id');
            });
        }

        // 2. Create pivot table norm_offer
        Schema::create('norm_offer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norm_id')->constrained()->onDelete('cascade');
            $table->foreignId('offer_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Ensure uniqueness
            $table->unique(['norm_id', 'offer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('norm_offer');

        // Restore norm_id column to offers table
        if (!Schema::hasColumn('offers', 'norm_id')) {
            Schema::table('offers', function (Blueprint $table) {
                $table->foreignId('norm_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }
};
