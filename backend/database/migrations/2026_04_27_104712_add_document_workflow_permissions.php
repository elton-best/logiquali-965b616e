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
        $permissions = [
            [
                'name' => 'verify_documents',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'approve_documents',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        // Insertion des permissions (on utilise ignor pour éviter les doublons au cas où)
        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission['name'])
                ->where('guard_name', $permission['guard_name'])
                ->exists();
            if (!$exists) {
                DB::table('permissions')->insert($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('name', ['verify_documents', 'approve_documents'])->delete();
    }
};
