<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pour PostgreSQL, on doit supprimer la contrainte puis la recréer
        DB::statement('ALTER TABLE processes ALTER COLUMN type DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE processes ALTER COLUMN type SET NOT NULL');
    }
};
