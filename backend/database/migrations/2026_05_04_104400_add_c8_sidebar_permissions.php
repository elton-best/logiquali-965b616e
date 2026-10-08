<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations - Chantier 8: Add missing sidebar permissions
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Create 'view_all_tasks' permission for supervisors
            Permission::firstOrCreate(
                ['name' => 'view_all_tasks', 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );

            // Create 'manage_public_holidays' permission for admins
            Permission::firstOrCreate(
                ['name' => 'manage_public_holidays', 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );

            Permission::firstOrCreate(
                ['name' => 'verify_actions', 'guard_name' => 'web'],
                ['guard_name' => 'web']
            );
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        DB::transaction(function () {
            Permission::where('name', 'view_all_tasks')->delete();
            Permission::where('name', 'manage_public_holidays')->delete();
            Permission::where('name', 'verify_actions')->delete();
        });
    }
};
