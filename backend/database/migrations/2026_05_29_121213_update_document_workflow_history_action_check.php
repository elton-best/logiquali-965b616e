<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement("ALTER TABLE document_workflow_history DROP CONSTRAINT IF EXISTS document_workflow_history_action_check");
            
            DB::statement("ALTER TABLE document_workflow_history ADD CONSTRAINT document_workflow_history_action_check CHECK (action::text IN (
                'submitted'::text,
                'verified'::text,
                'approved'::text,
                'rejected'::text,
                'rejection_confirmed'::text,
                'rejection_cancelled'::text,
                'delegated'::text,
                'reminded'::text,
                'generated'::text,
                'regenerated'::text,
                'downloaded'::text,
                'previewed'::text
            ))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'pgsql') {
            DB::statement("ALTER TABLE document_workflow_history DROP CONSTRAINT IF EXISTS document_workflow_history_action_check");
            
            DB::statement("ALTER TABLE document_workflow_history ADD CONSTRAINT document_workflow_history_action_check CHECK (action::text IN (
                'submitted'::text,
                'verified'::text,
                'approved'::text,
                'rejected'::text,
                'rejection_confirmed'::text,
                'rejection_cancelled'::text,
                'delegated'::text,
                'reminded'::text
            ))");
        }
    }
};
