<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds CHECK constraint to ensure consistency between status and approval_status.
     * 
     * Business Rules:
     * - approval_status='pending' → status MUST be 'pending'
     * - approval_status='approved' → status can be 'active' or 'suspended'
     * - approval_status='rejected' → status MUST be 'rejected'
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE enterprises 
            ADD CONSTRAINT enterprises_status_approval_consistency 
            CHECK (
                (approval_status = 'pending' AND status = 'pending') OR
                (approval_status = 'approved' AND status IN ('active', 'suspended')) OR
                (approval_status = 'rejected' AND status = 'rejected')
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE enterprises 
            DROP CONSTRAINT IF EXISTS enterprises_status_approval_consistency
        ");
    }
};
