<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'process.view',
            'process.create',
            'process.edit',
            'process.delete',
            'process.verify',
            'process.validate',
            'process.reject',
        ];

        foreach ($permissions as $permission) {
            \DB::table('permissions')->insert([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::table('permissions')->whereIn('name', [
            'process.view',
            'process.create',
            'process.edit',
            'process.delete',
            'process.verify',
            'process.validate',
            'process.reject',
        ])->delete();
    }
};
