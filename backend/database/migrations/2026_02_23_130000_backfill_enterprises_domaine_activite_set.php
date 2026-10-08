<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('enterprises')
            ->where(function ($query) {
                $query
                    ->whereNull('domaine_activite_set')
                    ->orWhere('domaine_activite_set', false);
            })
            ->whereNotNull('field')
            ->whereRaw("TRIM(field) <> ''")
            ->update([
                'domaine_activite_set' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Intentionally no-op (data backfill migration).
    }
};

