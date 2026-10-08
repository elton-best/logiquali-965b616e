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
        Schema::table('non_conformities', function (Blueprint $table) {
            if (!Schema::hasColumn('non_conformities', 'investigator_user_ids')) {
                $table->json('investigator_user_ids')->nullable()->after('responsible_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('non_conformities', function (Blueprint $table) {
            if (Schema::hasColumn('non_conformities', 'investigator_user_ids')) {
                $table->dropColumn('investigator_user_ids');
            }
        });
    }
};
