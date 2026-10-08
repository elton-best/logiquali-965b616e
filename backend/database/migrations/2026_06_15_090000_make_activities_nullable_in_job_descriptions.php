<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Corrige le blocage SQL sur la creation de fiche de poste sans activites.
        DB::statement('ALTER TABLE job_descriptions ALTER COLUMN activities DROP NOT NULL');
    }

    public function down(): void
    {
        // Revenir en NOT NULL exige de normaliser les valeurs nulles.
        DB::statement("UPDATE job_descriptions SET activities = '' WHERE activities IS NULL");
        DB::statement('ALTER TABLE job_descriptions ALTER COLUMN activities SET NOT NULL');
    }
};

