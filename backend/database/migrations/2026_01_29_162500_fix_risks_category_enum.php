<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the enum constraint and recreate with correct values
        DB::statement("ALTER TABLE risks DROP CONSTRAINT IF EXISTS risks_category_check");
        DB::statement("ALTER TABLE risks ALTER COLUMN category TYPE varchar(50)");
        DB::statement("ALTER TABLE risks ADD CONSTRAINT risks_category_check CHECK (category IN ('strategic', 'operational', 'financial', 'compliance', 'safety', 'environmental', 'reputation', 'it', 'legal', 'other'))");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE risks DROP CONSTRAINT IF EXISTS risks_category_check");
        DB::statement("ALTER TABLE risks ADD CONSTRAINT risks_category_check CHECK (category IN ('strategic', 'operational', 'financial', 'compliance', 'hs', 'environmental'))");
    }
};
