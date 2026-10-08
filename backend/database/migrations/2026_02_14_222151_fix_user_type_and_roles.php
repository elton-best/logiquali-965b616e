<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Corriger les user_type incorrects
        // Changer 'admin' en 'super_admin' pour les admins plateforme
        DB::table('users')
            ->where('user_type', 'admin')
            ->update(['user_type' => 'super_admin']);
            
        // 2. Retirer les rôles Spatie des super admins (ils n'en ont pas besoin)
        // Les super admins utilisent user_type pour l'accès, pas les rôles Spatie
        $superAdmins = DB::table('users')
            ->where('user_type', 'super_admin')
            ->pluck('id');
            
        if ($superAdmins->isNotEmpty()) {
            DB::table('model_has_roles')
                ->whereIn('model_id', $superAdmins)
                ->where('model_type', 'App\\Models\\User')
                ->delete();
        }
    }

    public function down(): void
    {
        // Revenir à l'ancien système (si nécessaire)
        DB::table('users')
            ->where('user_type', 'super_admin')
            ->update(['user_type' => 'admin']);
    }
};
