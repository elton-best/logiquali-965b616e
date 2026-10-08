<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE evaluation_requests ALTER COLUMN recipient_email DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE evaluation_requests SET recipient_email = 'noreply@bestqhse.local' WHERE recipient_email IS NULL");
        DB::statement('ALTER TABLE evaluation_requests ALTER COLUMN recipient_email SET NOT NULL');
    }
};
