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
        Schema::table('contexts', function (Blueprint $table) {
            // Drop the old enum constraint and recreate as nullable string
            $table->dropColumn('type');
        });
        
        Schema::table('contexts', function (Blueprint $table) {
            // Add type as nullable string (supports: internal, external, swot, pestel, etc.)
            $table->string('type', 50)->nullable()->after('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contexts', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        Schema::table('contexts', function (Blueprint $table) {
            $table->enum('type', ['internal', 'external'])->after('site_id');
        });
    }
};
