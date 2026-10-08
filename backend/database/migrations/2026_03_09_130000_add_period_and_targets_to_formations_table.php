<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->json('target_user_ids')->nullable()->after('cibles');
            $table->string('period_mode', 20)->default('custom')->after('date_fin');
            $table->string('period_label', 255)->nullable()->after('period_mode');
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->dropColumn(['target_user_ids', 'period_mode', 'period_label']);
        });
    }
};

