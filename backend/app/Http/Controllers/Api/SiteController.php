<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteResource;
use App\Notifications\CollaboratorWelcomeNotification;
use App\Notifications\SiteManagerAssignedNotification;
use App\Notifications\VerifyEmailNotification;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        // Vérification Policy
        $this->authorize('viewAny', Site::class);
        
        $user = $request->user();
        
        // Filter by user's enterprise
        $query = Site::query();
        
        // Client B (external clients) can see all active sites for complaint/survey forms
        if ($user->isClientB()) {
            $query->where('is_active', true)
                  ->select('id', 'ref', 'name', 'location', 'is_active');
        } else {
            // Enterprise users see only their enterprise's sites
            $query->with([
                'enterprise',
                'manager',
                'processes',
                'users',
                'subscription.offer',
                'subscriptions' => function ($q) {
                    $q->where('is_active', true)
                        ->where('start_date', '<=', now())
                        ->where('expiration_date', '>', now())
                        ->with('offer.norms')
                        ->orderBy('expiration_date');
                },
            ])
                  ->withCount(['processes', 'users']);
            
            if ($user->enterprise_id) {
                $query->where('enterprise_id', $user->enterprise_id);
            }
            
            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('ref', 'like', "%{$search}%");
                });
            }

            if ($request->filled('site_id')) {
                $query->where('id', (int) $request->input('site_id'));
            }
            
            // Apply is_active filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
        }

        // Dynamic pagination
        $perPage = $request->input('per_page', 50);
        $perPage = min(max((int)$perPage, 1), 100); // Between 1 and 100
        
        $sites = $query->paginate($perPage);

        // For Client B, return simple array format
        if ($user->isClientB()) {
            return response()->json([
                'data' => $sites->items(),
                'total' => $sites->total(),
                'current_page' => $sites->currentPage(),
                'per_page' => $sites->perPage(),
            ]);
        }

        return SiteResource::collection($sites);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Site::class);
        
        $user = $request->user();
        
        $validated = $request->validate([
            'enterprise_id' => 'sometimes|exists:enterprises,id',
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'is_headquarter' => 'boolean',
            'is_active' => 'boolean',
            'manager_id' => 'nullable|exists:users,id',
            'new_manager' => 'nullable|array',
            'new_manager.first_name' => 'required_with:new_manager|string|max:255',
            'new_manager.last_name' => 'required_with:new_manager|string|max:255',
            'new_manager.email' => 'required_with:new_manager|email|unique:users,email',
            'new_manager.phone' => 'nullable|string|max:20',
            'new_manager.position' => 'required_with:new_manager|string|max:255',
            'new_manager.address' => 'nullable|string|max:255',
            'new_manager.start_date' => 'nullable|date',
            'role' => 'nullable|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        // Force enterprise_id to user's enterprise if not superadmin
        if ($user->user_type !== 'super_admin' && $user->enterprise_id) {
            $validated['enterprise_id'] = $user->enterprise_id;
        }

        if (empty($validated['enterprise_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'enterprise_id est requis pour créer un site.'
            ], 422);
        }

        if ($this->rolesOnlyModeEnabled() && $this->hasNonEmptyPermissionPayload($validated['permissions'] ?? null)) {
            return $this->directPermissionsDisabledResponse();
        }

        // Start transaction
        DB::beginTransaction();
        try {
            // Create or handle manager
            $managerId = null;
            $selectedRole = $validated['role'] ?? null;
            $resolvedRole = null;
            if ($selectedRole) {
                $resolvedRole = SpatieRole::query()
                    ->where('name', $selectedRole)
                    ->where('guard_name', 'web')
                    ->value('name');
            }
            $requestedPermissions = collect($validated['permissions'] ?? [])
                ->filter(fn ($perm) => is_string($perm) && $perm !== '')
                ->values()
                ->all();
            
            if (isset($validated['new_manager'])) {
                // Create new manager
                $email = $validated['new_manager']['email'];
                $baseUsername = explode('@', $email)[0]; // Generate username from email
                $username = $baseUsername;
                $counter = 1;
                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }
                $generatedPassword = Str::random(16);
                
                $newManager = User::create([
                    'first_name' => $validated['new_manager']['first_name'],
                    'last_name' => $validated['new_manager']['last_name'],
                    'name' => $validated['new_manager']['first_name'] . ' ' . $validated['new_manager']['last_name'],
                    'email' => $email,
                    'username' => $username,
                    'phone' => $validated['new_manager']['phone'] ?? null,
                    'address' => $validated['new_manager']['address'] ?? null,
                    'user_type' => 'company',
                    'role' => $validated['new_manager']['position'] ?? 'Responsable de site', // Utiliser position comme role
                    'job_title' => $validated['new_manager']['position'] ?? 'Responsable de site',
                    'start_date' => $validated['new_manager']['start_date'] ?? null,
                    'enterprise_id' => $validated['enterprise_id'],
                    'password' => bcrypt($generatedPassword), // Temporary password
                    'must_change_password' => true,
                    'is_active' => true,
                ]);
                
                // New managers are created for site assignment: default role is site_manager.
                $roleToAssign = $resolvedRole ?: 'site_manager';
                if ($roleToAssign) {
                    $newManager->syncRoles([$roleToAssign]);
                } elseif ($selectedRole) {
                    Log::warning("Role '{$selectedRole}' not found for site manager assignment.");
                }
                
                // Apply custom permissions if provided (only those existing in DB)
                if (!empty($requestedPermissions)) {
                    $permissionsToApply = $this->sanitizeDirectPermissionsForRole(
                        $requestedPermissions,
                        $roleToAssign
                    );
                    $newManager->syncPermissions($permissionsToApply);
                }
                
                $managerId = $newManager->id;

                // Send credentials to new manager
                try {
                    $newManager->notify(new CollaboratorWelcomeNotification($generatedPassword, $user));
                } catch (\Exception $notificationException) {
                    Log::error('Failed to send site manager welcome notification: ' . $notificationException->getMessage());
                }

                if (!$newManager->hasVerifiedEmail()) {
                    try {
                        $newManager->notify(new VerifyEmailNotification());
                    } catch (\Exception $notificationException) {
                        Log::error('Failed to send site manager verification notification: ' . $notificationException->getMessage());
                    }
                }
            } elseif (isset($validated['manager_id'])) {
                $manager = User::where('id', $validated['manager_id'])
                    ->where('enterprise_id', $validated['enterprise_id'])
                    ->first();

                if (!$manager) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Le responsable sélectionné doit appartenir à la même entreprise.'
                    ], 422);
                }

                // Check if user is already manager of another site
                $existingManager = Site::where('manager_id', $validated['manager_id'])->first();
                if ($existingManager) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce collaborateur est déjà responsable du site "' . $existingManager->name . '". Un collaborateur ne peut gérer qu\'un seul site à la fois.'
                    ], 422);
                }
                
                $managerId = $validated['manager_id'];

                // Never mutate enterprise admin role from site assignment flows.
                $managerIsEnterpriseAdmin = $manager->hasRole('admin_entreprise');
                if ($resolvedRole && !$managerIsEnterpriseAdmin) {
                    $manager->syncRoles([$resolvedRole]);
                } elseif ($resolvedRole && $managerIsEnterpriseAdmin) {
                    Log::info("Skipped role override for enterprise admin user {$manager->id} during site manager assignment.");
                } elseif ($selectedRole) {
                    Log::warning("Role '{$selectedRole}' not found for existing manager assignment.");
                }
                if (!empty($requestedPermissions) && !$managerIsEnterpriseAdmin) {
                    $permissionsToApply = $this->sanitizeDirectPermissionsForRole(
                        $requestedPermissions,
                        $resolvedRole ?: null
                    );
                    $manager->syncPermissions($permissionsToApply);
                } elseif (!empty($requestedPermissions) && $managerIsEnterpriseAdmin) {
                    Log::info("Skipped direct permission override for enterprise admin user {$manager->id} during site manager assignment.");
                }
            }

            // Règle métier: pour le siège social, le responsable par défaut est l'admin d'entreprise
            if (
                !$managerId
                && ($validated['is_headquarter'] ?? false)
                && !empty($validated['enterprise_id'])
            ) {
                $defaultEnterpriseAdmin = User::query()
                    ->where('enterprise_id', $validated['enterprise_id'])
                    ->where('user_type', 'company')
                    ->where('is_active', true)
                    ->role('admin_entreprise')
                    ->orderBy('id')
                    ->first();

                if ($defaultEnterpriseAdmin) {
                    $managerId = $defaultEnterpriseAdmin->id;
                }
            }

            // Create site
            $site = Site::create([
                'enterprise_id' => $validated['enterprise_id'],
                'manager_id' => $managerId,
                'name' => $validated['name'],
                'location' => $validated['location'],
                'city' => $validated['city'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'is_headquarter' => $validated['is_headquarter'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // If manager exists, update their site_id to assign them to this site
            if ($managerId) {
                User::where('id', $managerId)->update(['site_id' => $site->id]);

                $managerUser = User::find($managerId);
                if ($managerUser) {
                    try {
                        $managerUser->notify(new SiteManagerAssignedNotification($site, $user, 'assigned'));
                    } catch (\Exception $notificationException) {
                        Log::error('Failed to send site manager assignment notification: ' . $notificationException->getMessage());
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => new SiteResource($site->load(['enterprise', 'manager', 'processes', 'users']))
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Site creation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du site: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Site $site)
    {
        $user = $request->user();

        // Verify user has access to this site
        if ($user->user_type !== 'super_admin' && $site->enterprise_id !== $user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new SiteResource($site->load(['enterprise', 'processes', 'users'])->loadCount(['processes', 'users']))
        ]);
    }

    public function update(Request $request, Site $site)
    {
        $this->authorize('update', $site);
        
        $user = $request->user();

        // Verify user has access to this site
        if ($user->user_type !== 'super_admin' && $site->enterprise_id !== $user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'enterprise_id' => 'sometimes|exists:enterprises,id',
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'is_headquarter' => 'boolean',
            'is_active' => 'boolean',
            'manager_id' => 'nullable|exists:users,id',
            'role' => 'nullable|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        // Prevent changing enterprise_id if not superadmin
        if ($user->user_type !== 'super_admin' && isset($validated['enterprise_id'])) {
            unset($validated['enterprise_id']);
        }

        if ($this->rolesOnlyModeEnabled() && $this->hasNonEmptyPermissionPayload($validated['permissions'] ?? null)) {
            return $this->directPermissionsDisabledResponse();
        }

        $managerChanged = isset($validated['manager_id']) && $validated['manager_id'] !== $site->manager_id;

        // Check if manager_id is being changed and validate uniqueness
        if ($managerChanged && !empty($validated['manager_id'])) {
            $manager = User::where('id', $validated['manager_id'])
                ->where('enterprise_id', $site->enterprise_id)
                ->first();

            if (!$manager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le responsable sélectionné doit appartenir à la même entreprise.'
                ], 422);
            }

            $existingManager = Site::where('manager_id', $validated['manager_id'])
                ->where('id', '!=', $site->id)
                ->first();
                
            if ($existingManager) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce collaborateur est déjà responsable du site "' . $existingManager->name . '". Un collaborateur ne peut gérer qu\'un seul site à la fois.'
                ], 422);
            }

        }

        if ($managerChanged) {
            // Update the new manager's site_id
            if (!empty($validated['manager_id'])) {
                User::where('id', $validated['manager_id'])->update(['site_id' => $site->id]);
            }

            // Clear the old manager's site_id if exists
            if ($site->manager_id) {
                User::where('id', $site->manager_id)->update(['site_id' => null]);
            }
        }

        // Apply role/permissions to the effective manager when requested
        $effectiveManagerId = array_key_exists('manager_id', $validated)
            ? $validated['manager_id']
            : $site->manager_id;
        if ($effectiveManagerId) {
            $effectiveManager = User::where('id', $effectiveManagerId)
                ->where('enterprise_id', $site->enterprise_id)
                ->first();

            if ($effectiveManager) {
                if (!empty($validated['role']) && !$effectiveManager->hasRole('admin_entreprise')) {
                    $effectiveManager->syncRoles([$validated['role']]);
                } elseif (!empty($validated['role']) && $effectiveManager->hasRole('admin_entreprise')) {
                    Log::info("Skipped role override for enterprise admin user {$effectiveManager->id} during site update.");
                }

                if (array_key_exists('permissions', $validated) && !$effectiveManager->hasRole('admin_entreprise')) {
                    $requestedPermissions = collect($validated['permissions'] ?? [])
                        ->filter(fn ($perm) => is_string($perm) && $perm !== '')
                        ->values()
                        ->all();
                    $permissionsToApply = $this->sanitizeDirectPermissionsForRole(
                        $requestedPermissions,
                        (string) ($validated['role'] ?? $effectiveManager->getRoleNames()->first())
                    );
                    $effectiveManager->syncPermissions($permissionsToApply);
                } elseif (array_key_exists('permissions', $validated) && $effectiveManager->hasRole('admin_entreprise')) {
                    Log::info("Skipped direct permission override for enterprise admin user {$effectiveManager->id} during site update.");
                }
            }
        }

        $site->update($validated);

        if ($managerChanged && !empty($validated['manager_id'])) {
            $newManager = User::find($validated['manager_id']);
            if ($newManager) {
                try {
                    $newManager->notify(new SiteManagerAssignedNotification($site, $user, 'changed'));
                } catch (\Exception $notificationException) {
                    Log::error('Failed to send site manager change notification: ' . $notificationException->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => new SiteResource($site->load(['enterprise', 'manager', 'processes', 'users']))
        ]);
    }

    public function destroy(Request $request, Site $site)
    {
        $user = $request->user();

        // Verify user has access to this site
        if ($user->user_type !== 'super_admin' && $site->enterprise_id !== $user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $site->delete();

        return response()->json([
            'success' => true,
            'message' => 'Site deleted successfully'
        ]);
    }

    /**
     * Toggle site active status
     */
    public function toggleActive(Request $request, $id)
    {
        try {
            $user = $request->user();
            $site = Site::findOrFail($id);

            // Verify user has access to this site
            if ($user->user_type !== 'super_admin' && $site->enterprise_id !== $user->enterprise_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $site->is_active = !$site->is_active;
            $site->save();

            return response()->json([
                'success' => true,
                'data' => new SiteResource($site->load('enterprise')),
                'message' => 'Site status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling site status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Keep only valid permissions not already inherited from the selected role.
     *
     * @param array<int, string> $requestedPermissions
     * @return array<int, string>
     */
    private function sanitizeDirectPermissionsForRole(array $requestedPermissions, ?string $roleName = null): array
    {
        $requested = SpatiePermission::query()
            ->whereIn('name', $requestedPermissions)
            ->pluck('name')
            ->filter()
            ->unique()
            ->values();

        if (empty($roleName)) {
            return $requested->all();
        }

        $role = SpatieRole::query()
            ->with('permissions:id,name')
            ->where('name', $roleName)
            ->first();

        if (!$role) {
            return $requested->all();
        }

        $inheritedSet = array_flip(
            $role->permissions
                ->pluck('name')
                ->filter()
                ->unique()
                ->values()
                ->all()
        );

        return $requested
            ->reject(fn ($permission) => isset($inheritedSet[$permission]))
            ->values()
            ->all();
    }

    /**
     * Récupère l'abonnement actif d'un site
     */
    public function subscription(Request $request, $id)
    {
        try {
            $user = $request->user();
            $site = Site::findOrFail($id);

            // Verify user has access to this site
            if ($user->user_type !== 'super_admin' && $site->enterprise_id !== $user->enterprise_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $subscription = \App\Models\EnterpriseSubscription::where('site_id', $id)
                ->with('offer')
                ->where('is_active', true)
                ->where('expiration_date', '>', now())
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun abonnement actif trouvé pour ce site',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\EnterpriseSubscriptionResource($subscription)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'abonnement: ' . $e->getMessage()
            ], 500);
        }
    }

    private function rolesOnlyModeEnabled(): bool
    {
        return (bool) config('authz.enforce_roles_only_permissions', false);
    }

    private function hasNonEmptyPermissionPayload(mixed $permissions): bool
    {
        if (!is_array($permissions)) {
            return false;
        }

        return collect($permissions)
            ->contains(fn($permission) => is_string($permission) && trim($permission) !== '');
    }

    private function directPermissionsDisabledResponse()
    {
        return response()->json([
            'success' => false,
            'message' => "Le mode RBAC 'roles-only' est actif: les permissions directes sont désactivées.",
            'code' => 'RBAC_DIRECT_PERMISSIONS_DISABLED',
        ], 422);
    }
}
