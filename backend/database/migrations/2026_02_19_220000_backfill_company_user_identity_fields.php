<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill first_name / last_name for existing company users.
        $users = DB::table('users')
            ->select('id', 'name', 'username', 'email', 'first_name', 'last_name')
            ->where('user_type', 'company')
            ->get();

        foreach ($users as $user) {
            $firstName = $user->first_name;
            $lastName = $user->last_name;

            if (empty($firstName)) {
                $fallback = $user->name ?: $user->username ?: $user->email;
                $fallback = trim((string) $fallback);
                $firstName = str_contains($fallback, '@')
                    ? explode('@', $fallback)[0]
                    : $fallback;
            }

            if (is_null($lastName)) {
                $lastName = '';
            }

            $displayName = trim($firstName . ' ' . $lastName);
            if ($displayName === '') {
                $displayName = $user->name ?: $user->username ?: 'Utilisateur';
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => $displayName,
                ]);
        }

        // Backfill job title from role for enterprise admin and site manager.
        $adminUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->where('roles.name', 'admin_entreprise')
            ->pluck('model_has_roles.model_id');

        if ($adminUserIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $adminUserIds)
                ->where(function ($query) {
                    $query->whereNull('job_title')->orWhere('job_title', '');
                })
                ->update(['job_title' => 'Admin entreprise']);
        }

        $siteManagerIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->where('roles.name', 'site_manager')
            ->pluck('model_has_roles.model_id');

        if ($siteManagerIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $siteManagerIds)
                ->where(function ($query) {
                    $query->whereNull('job_title')->orWhere('job_title', '');
                })
                ->update(['job_title' => 'Responsable de site']);
        }
    }

    public function down(): void
    {
        // Intentionally no-op: backfill should not be reverted automatically.
    }
};

