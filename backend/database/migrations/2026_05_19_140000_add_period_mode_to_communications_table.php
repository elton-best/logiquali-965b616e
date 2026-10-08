<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('communications', 'period_mode')) {
            return;
        }

        Schema::table('communications', function (Blueprint $table) {
            $table->string('period_mode', 20)->default('custom')->after('date_fin');
        });

        DB::table('communications')
            ->where(function ($query) {
                $query->whereNull('date_debut')->orWhereNull('date_fin');
            })
            ->update(['period_mode' => 'standard']);
    }

    public function down(): void
    {
        if (!Schema::hasColumn('communications', 'period_mode')) {
            return;
        }

        Schema::table('communications', function (Blueprint $table) {
            $table->dropColumn('period_mode');
        });
    }
};
