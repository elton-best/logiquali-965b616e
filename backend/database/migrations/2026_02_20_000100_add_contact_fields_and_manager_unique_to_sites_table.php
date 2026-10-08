<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'city')) {
                $table->string('city')->nullable()->after('location');
            }
            if (!Schema::hasColumn('sites', 'phone')) {
                $table->string('phone', 30)->nullable()->after('city');
            }
            if (!Schema::hasColumn('sites', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });

        // Nettoyage préventif: en cas de doublons historiques de manager actif,
        // conserver le site le plus récent et désassigner les autres.
        $duplicateManagerIds = DB::table('sites')
            ->select('manager_id')
            ->whereNull('deleted_at')
            ->whereNotNull('manager_id')
            ->groupBy('manager_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('manager_id');

        foreach ($duplicateManagerIds as $managerId) {
            $keptSiteId = DB::table('sites')
                ->where('manager_id', $managerId)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->value('id');

            $siteIdsToReset = DB::table('sites')
                ->where('manager_id', $managerId)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->skip(1)
                ->pluck('id');

            if ($siteIdsToReset->isNotEmpty()) {
                DB::table('sites')
                    ->whereIn('id', $siteIdsToReset)
                    ->update(['manager_id' => null]);
            }

            if ($keptSiteId) {
                DB::table('users')
                    ->where('id', $managerId)
                    ->update(['site_id' => $keptSiteId]);
            }
        }

        Schema::table('sites', function (Blueprint $table) {
            // Un responsable de site ne peut gérer qu'un seul site actif (soft deletes exclus)
            $table->unique(['manager_id', 'deleted_at'], 'sites_manager_active_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropUnique('sites_manager_active_unique');
        });

        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('sites', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('sites', 'city')) {
                $table->dropColumn('city');
            }
        });
    }
};
