<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill formations organizer from created_by when missing.
        DB::table('formations')
            ->whereNull('organizer_user_id')
            ->whereNotNull('created_by')
            ->update(['organizer_user_id' => DB::raw('created_by')]);

        // Backfill communications organizer from created_by when missing.
        DB::table('communications')
            ->whereNull('organizer_user_id')
            ->whereNotNull('created_by')
            ->update(['organizer_user_id' => DB::raw('created_by')]);

        $missingFormations = DB::table('formations')->whereNull('organizer_user_id')->count();
        $missingCommunications = DB::table('communications')->whereNull('organizer_user_id')->count();

        if ($missingFormations > 0 || $missingCommunications > 0) {
            throw new RuntimeException('Impossible de rendre organizer_user_id obligatoire: enregistrements sans organisateur.');
        }

        Schema::table('formations', function (Blueprint $table) {
            $table->foreignId('organizer_user_id')->nullable(false)->change();
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->foreignId('organizer_user_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('formations', function (Blueprint $table) {
            $table->foreignId('organizer_user_id')->nullable()->change();
        });

        Schema::table('communications', function (Blueprint $table) {
            $table->foreignId('organizer_user_id')->nullable()->change();
        });
    }
};
