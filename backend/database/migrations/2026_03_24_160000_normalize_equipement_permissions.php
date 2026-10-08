<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mapping = [
            'equipement.read' => 'equipements.read',
            'equipement.create' => 'equipements.create',
            'equipement.update' => 'equipements.update',
            'equipement.delete' => 'equipements.delete',
            'equipement.manage' => 'equipements.manage',
        ];

        foreach ($mapping as $legacy => $canonical) {
            $legacyPerm = DB::table('permissions')->where('name', $legacy)->first();
            if (!$legacyPerm) {
                continue;
            }

            $canonicalPerm = DB::table('permissions')->where('name', $canonical)->first();
            if ($canonicalPerm) {
                DB::table('role_has_permissions')
                    ->where('permission_id', $legacyPerm->id)
                    ->update(['permission_id' => $canonicalPerm->id]);

                DB::table('model_has_permissions')
                    ->where('permission_id', $legacyPerm->id)
                    ->update(['permission_id' => $canonicalPerm->id]);

                DB::table('permissions')->where('id', $legacyPerm->id)->delete();
                continue;
            }

            DB::table('permissions')
                ->where('id', $legacyPerm->id)
                ->update(['name' => $canonical]);
        }
    }

    public function down(): void
    {
        $mapping = [
            'equipements.read' => 'equipement.read',
            'equipements.create' => 'equipement.create',
            'equipements.update' => 'equipement.update',
            'equipements.delete' => 'equipement.delete',
            'equipements.manage' => 'equipement.manage',
        ];

        foreach ($mapping as $canonical => $legacy) {
            $canonicalPerm = DB::table('permissions')->where('name', $canonical)->first();
            if (!$canonicalPerm) {
                continue;
            }

            $legacyPerm = DB::table('permissions')->where('name', $legacy)->first();
            if ($legacyPerm) {
                continue;
            }

            DB::table('permissions')
                ->where('id', $canonicalPerm->id)
                ->update(['name' => $legacy]);
        }
    }
};
