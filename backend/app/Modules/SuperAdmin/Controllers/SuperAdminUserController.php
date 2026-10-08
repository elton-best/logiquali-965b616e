<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Settings\SuperAdminSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

class SuperAdminUserController extends Controller
{
    public function getRoles()
    {
        $roles = SpatieRole::query()
            ->where('name', 'like', 'super_admin%')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $this->formatRoleLabel((string) $role->name),
                    'permissions_count' => $role->permissions->count(),
                ];
            })->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => app(SuperAdminSettingsService::class)->passwordRules(true),
            'role_name' => 'required|string|exists:roles,name',
            'is_active' => 'nullable|boolean',
        ]);

        if (!$this->isSuperAdminRole((string) $validated['role_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Role super admin invalide.',
            ], 422);
        }

        if (empty($validated['name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        $user = User::create([
            'name' => $validated['name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_type' => User::TYPE_SUPER_ADMIN,
            'is_active' => $validated['is_active'] ?? true,
            'must_change_password' => true,
        ]);

        $user->syncRoles([$validated['role_name']]);

        return response()->json([
            'success' => true,
            'data' => $user->load(['roles']),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les comptes super admin peuvent etre modifies ici.',
            ], 422);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => app(SuperAdminSettingsService::class)->passwordRules(false),
            'role_name' => 'sometimes|string|exists:roles,name',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['role_name']) && !$this->isSuperAdminRole((string) $validated['role_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Role super admin invalide.',
            ], 422);
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['must_change_password'] = true;
            $validated['password_changed_at'] = null;
        }

        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $firstName = $validated['first_name'] ?? $user->first_name ?? '';
            $lastName = $validated['last_name'] ?? $user->last_name ?? '';
            $validated['name'] = trim($firstName . ' ' . $lastName);
        }

        $user->update($validated);

        if (isset($validated['role_name'])) {
            $user->syncRoles([$validated['role_name']]);
        }

        if (array_key_exists('is_active', $validated) && !$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'data' => $user->load(['roles']),
        ]);
    }

    private function isSuperAdminRole(string $roleName): bool
    {
        return str_starts_with($roleName, 'super_admin');
    }

    private function formatRoleLabel(string $roleName): string
    {
        $label = str_replace('_', ' ', $roleName);
        $label = trim(preg_replace('/\s+/', ' ', $label));
        return ucwords($label);
    }
}
