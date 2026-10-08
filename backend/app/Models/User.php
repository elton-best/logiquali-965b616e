<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Collection;

/**
 * App\Models\User
 *
 * @mixin \Spatie\Permission\Traits\HasRoles
 * @method \Illuminate\Support\Collection getPermissionsViaRoles()
 * @property \Illuminate\Support\Collection $permissions
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasAuditFields, HasReference, HasRoles;

    public const TYPE_SUPER_ADMIN = 'super_admin';
    public const TYPE_COMPANY = 'company';
    public const TYPE_CLIENT_B = 'clientb';

    /**
     * Cache local par requete pour eviter des requetes repetitives sur les permissions.
     *
     * @var array<string,bool>
     */
    private array $permissionExistsCache = [];
    private ?array $catalogReadPermissionCache = null;
    private ?int $assignedActionsCountCache = null;

    protected $fillable = [
        'ref',
        'name',
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'phone',
        'address',
        'photo_path',
        'signature_path',
        'signature_uploaded_at',
        'user_type',
        'enterprise_id',
        'site_id',
        'role',
        'job_title',
        'start_date',
        'is_active',
        'collaborator_approval_status',
        'collaborator_requested_by',
        'collaborator_requested_at',
        'collaborator_approved_by',
        'collaborator_approved_at',
        'collaborator_rejected_by',
        'collaborator_rejected_at',
        'collaborator_rejection_reason',
        'last_login_at',
        'must_change_password',
        'password_changed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Accesseurs à inclure automatiquement dans la sérialisation JSON
     */
    protected $appends = [
        'signature_url',
        'photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'collaborator_requested_at' => 'datetime',
            'collaborator_approved_at' => 'datetime',
            'collaborator_rejected_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'signature_uploaded_at' => 'datetime',
            'start_date' => 'date',
        ];
    }

    public function setUserTypeAttribute($value): void
    {
        $normalized = is_string($value) ? mb_strtolower(trim($value)) : $value;

        if ($normalized === 'client_b' || $normalized === 'clientb') {
            $normalized = self::TYPE_CLIENT_B;
        }

        $this->attributes['user_type'] = $normalized;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === self::TYPE_SUPER_ADMIN;
    }

    public function isCompanyUser(): bool
    {
        return $this->user_type === self::TYPE_COMPANY;
    }

    public function isClientB(): bool
    {
        return $this->user_type === self::TYPE_CLIENT_B;
    }

    public function getSignatureUrlAttribute(): ?string
    {
        if (empty($this->signature_path)) {
            return null;
        }

        if (str_starts_with($this->signature_path, 'http://') || str_starts_with($this->signature_path, 'https://')) {
            return $this->signature_path;
        }

        return asset('storage/' . ltrim($this->signature_path, '/'));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
            return $this->photo_path;
        }

        return asset('storage/' . ltrim($this->photo_path, '/'));
    }

    public function hasUploadedSignature(): bool
    {
        return !empty(trim((string) $this->signature_path));
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    // NOTE : La relation permissions() custom (table user_permissions) a été supprimée.
    // Le système est en mode RBAC pur. Les permissions viennent uniquement des rôles
    // via les tables Spatie (model_has_roles, role_has_permissions).
    // Utiliser $user->getAllPermissions() ou $user->getPermissionsViaRoles() à la place.

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function jobDescription()
    {
        return $this->hasOne(JobDescription::class);
    }

    public function responsibilities()
    {
        return $this->hasMany(Responsibility::class);
    }

    public function teamMemberships()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function evaluations()
    {
        return $this->hasMany(EmployeeEvaluation::class);
    }

    public function conductedEvaluations()
    {
        return $this->hasMany(EmployeeEvaluation::class, 'evaluator_id');
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function onboardingStep()
    {
        return $this->hasOne(OnboardingStep::class);
    }

    public function collaboratorRequestedBy()
    {
        return $this->belongsTo(User::class, 'collaborator_requested_by');
    }

    public function collaboratorApprovedBy()
    {
        return $this->belongsTo(User::class, 'collaborator_approved_by');
    }

    public function collaboratorRejectedBy()
    {
        return $this->belongsTo(User::class, 'collaborator_rejected_by');
    }

    public function habilitations()
    {
        return $this->hasMany(Habilitation::class);
    }

    public function competencesAcquises()
    {
        return $this->hasMany(CompetenceAcquise::class);
    }

    /**
     * Enregistrer une nouvelle connexion
     */
    public function recordLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Anonymiser les données personnelles (RGPD)
     */
    public function anonymize(): void
    {
        $token = Str::random(8);
        $this->update([
            'name' => 'Anonyme ' . $token,
            'first_name' => null,
            'last_name' => null,
            'email' => "anonyme_{$token}@example.local",
            'phone' => null,
            'photo_path' => null,
        ]);
    }

    /**
     * Vérifier si l'email est vérifié
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Obtenir l'email pour vérification
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    /**
     * Marquer l'email comme vérifié
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function canAccessSubModule(string $subModuleCode, string $action = 'read'): bool
    {
        // Admin entreprise: accès total aux sub-modules de TOUTES les subscriptions actives
        if ($this->isEnterpriseAdmin()) {
            $subscriptions = $this->site?->getActiveSubscriptions();
            if (!$subscriptions || $subscriptions->isEmpty()) return false;

            $subModule = SubModule::where('code', $subModuleCode)->first();
            if (!$subModule) return false;

            // Vérifier si sub-module dans au moins une subscription
            foreach ($subscriptions as $subscription) {
                if ($subscription->canAccessSubModule($subModule->id)) {
                    return true;
                }
            }
            return false;
        }

        // Collaborateur: vérifie souscription ET permission
        $subscriptions = $this->site?->getActiveSubscriptions();
        if (!$subscriptions || $subscriptions->isEmpty()) return false;

        $subModule = SubModule::with('module:id,code')
            ->where('code', $subModuleCode)
            ->first();
        if (!$subModule) return false;

        // Vérifier si sub-module accessible via au moins une subscription
        $hasSubModuleAccess = false;
        foreach ($subscriptions as $subscription) {
            if ($subscription->canAccessSubModule($subModule->id)) {
                $hasSubModuleAccess = true;
                break;
            }
        }

        if (!$hasSubModuleAccess) return false;

        $moduleCode = $subModule->module?->code;
        if (!$moduleCode) {
            return false;
        }

        $candidates = [
            "{$moduleCode}.manage",
            "{$moduleCode}.{$action}",
            "{$moduleCode}.{$subModuleCode}.manage",
            "{$moduleCode}.{$subModuleCode}.{$action}",
        ];

        foreach ($candidates as $permission) {
            if ($this->canPermissionSafely($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessSection(string $sectionCode, string $action = 'read'): bool
    {
        // Admin entreprise: accès total aux sections de TOUTES les subscriptions actives
        if ($this->isEnterpriseAdmin()) {
            $subscriptions = $this->site?->getActiveSubscriptions();
            if (!$subscriptions || $subscriptions->isEmpty()) return false;

            $section = SubModuleSection::where('code', $sectionCode)->first();
            if (!$section) return false;

            // Vérifier si section dans au moins une subscription
            foreach ($subscriptions as $subscription) {
                if ($subscription->canAccessSection($section->id)) {
                    return true;
                }
            }
            return false;
        }

        // Collaborateur: vérifie souscription + permission sous-module + permission section
        $subscriptions = $this->site?->getActiveSubscriptions();
        if (!$subscriptions || $subscriptions->isEmpty()) return false;

        $section = SubModuleSection::with('subModule.module:id,code')
            ->where('code', $sectionCode)
            ->first();
        if (!$section) return false;

        // Vérifier accès sous-module parent (prérequis)
        if (!$this->canAccessSubModule($section->subModule->code, 'read')) {
            return false;
        }

        // Vérifier si section accessible via au moins une subscription
        $hasSectionAccess = false;
        foreach ($subscriptions as $subscription) {
            if ($subscription->canAccessSection($section->id)) {
                $hasSectionAccess = true;
                break;
            }
        }

        if (!$hasSectionAccess) return false;

        $moduleCode = $section->subModule?->module?->code;
        $subModuleCode = $section->subModule?->code;
        if (!$moduleCode || !$subModuleCode) {
            return false;
        }

        $candidates = [
            "{$moduleCode}.manage",
            "{$moduleCode}.{$action}",
            "{$moduleCode}.{$subModuleCode}.manage",
            "{$moduleCode}.{$subModuleCode}.{$action}",
            "{$moduleCode}.{$subModuleCode}.{$sectionCode}.manage",
            "{$moduleCode}.{$subModuleCode}.{$sectionCode}.{$action}",
        ];

        foreach ($candidates as $permission) {
            if ($this->canPermissionSafely($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isEnterpriseAdmin(): bool
    {
        return $this->hasRole('admin_entreprise');
    }

    public function isSiteManager(): bool
    {
        if ($this->hasRole('site_manager')) {
            return true;
        }

        if (!$this->site_id) {
            return false;
        }

        $site = $this->relationLoaded('site')
            ? $this->site
            : $this->site()->select('id', 'manager_id')->first();

        return $site && !empty($site->manager_id) && (int) $site->manager_id === (int) $this->id;
    }

    public function syncSubModulePermissions(array $subModuleIds, array $actions = ['read']): void
    {
        $permissions = [];
        foreach ($subModuleIds as $subModuleId) {
            $subModule = SubModule::find($subModuleId);
            if ($subModule) {
                foreach ($actions as $action) {
                    $permission = "{$subModule->code}.{$action}";
                    if ($this->permissionExists($permission)) {
                        $permissions[] = $permission;
                    }
                }
            }
        }
        $this->syncPermissions($permissions);
    }

    /**
     * Vérifier si l'utilisateur peut accéder à un module avec une action spécifique
     */
    public function canAccessModule(string $moduleCode, string $action = 'read'): bool
    {
        // Admin entreprise: accès total
        if ($this->isEnterpriseAdmin()) {
            return true;
        }

        // Collaborateur: vérifie permission
        $candidates = [
            "{$moduleCode}.manage",
            "{$moduleCode}.{$action}",
        ];

        foreach ($candidates as $permission) {
            if ($this->canPermissionSafely($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Permissions effectives: RBAC (roles + direct).
     */
    public function getEffectivePermissionNames(): Collection
    {
        return $this->getAllPermissions()
            ->pluck('name')
            ->filter()
            ->values();
    }

    public function getRolePermissionNames(): Collection
    {
        return $this->getPermissionsViaRoles()
            ->pluck('name')
            ->filter()
            ->unique()
            ->values();
    }

    public function getActiveScopedPermissionNames(): Collection
    {
        if (!$this->isCompanyUser()) {
            return collect();
        }

        $activeCodes = $this->getActiveCatalogCodes();
        $systemPrefixes = self::getSystemPermissionPrefixes();
        $allowedCodes = $activeCodes->merge($systemPrefixes)->unique()->values();
        if ($allowedCodes->isEmpty()) {
            return collect();
        }

        return $this->getEffectivePermissionNames()
            ->filter(function ($permissionName) use ($allowedCodes) {
                $permission = mb_strtolower(trim((string) $permissionName));
                if ($permission === '') {
                    return false;
                }

                $segments = explode('.', $permission);
                $code = $segments[0] ?? '';
                if ($code === '') {
                    return false;
                }

                // Keep module/sub-module/system prefixes that are active for the site.
                if ($allowedCodes->contains($code)) {
                    return true;
                }

                // Keep canonical resource-level permissions (ex: actions.read, audits.read).
                // These permissions gate many dashboard routes and should not disappear from
                // active_scoped_permissions when a site has active subscriptions.
                return count($segments) === 2;
            })
            ->values();
    }

    public static function getSystemPermissionPrefixes(): Collection
    {
        $configured = config('authz.system_permission_prefixes', []);
        if (!is_array($configured)) {
            return collect();
        }

        return collect($configured)
            ->map(fn ($prefix) => mb_strtolower(trim((string) $prefix)))
            ->filter()
            ->unique()
            ->values();
    }

    public function getAssignedActionsCount(): int
    {
        if ($this->assignedActionsCountCache !== null) {
            return $this->assignedActionsCountCache;
        }

        $this->assignedActionsCountCache = Action::query()
            ->where('responsible_id', (int) $this->id)
            ->whereNull('deleted_at')
            ->count();

        return $this->assignedActionsCountCache;
    }

    public function hasAssignedActions(): bool
    {
        return $this->getAssignedActionsCount() > 0;
    }

    public function requiresCollaboratorApproval(): bool
    {
        return $this->isCompanyUser()
            && $this->collaborator_approval_status === 'pending_admin_approval';
    }

    /**
     * Verifie une permission sans lever d'exception si elle est absente du catalogue.
     */
    private function canPermissionSafely(string $permission): bool
    {
        if (!$this->permissionExists($permission)) {
            return false;
        }

        try {
            return (bool) $this->hasPermissionTo($permission);
        } catch (\Throwable) {
            return false;
        }
    }

    private function permissionExists(string $permission): bool
    {
        if (array_key_exists($permission, $this->permissionExistsCache)) {
            return $this->permissionExistsCache[$permission];
        }

        $exists = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->exists();

        $this->permissionExistsCache[$permission] = $exists;
        return $exists;
    }

    /**
     * Permissions de lecture déduites du catalogue d'accès du site courant.
     *
     * @return array<int, string>
     */
    private function catalogReadPermissions(): array
    {
        if ($this->catalogReadPermissionCache !== null) {
            return $this->catalogReadPermissionCache;
        }

        if (!$this->isCompanyUser() || !$this->site_id) {
            $this->catalogReadPermissionCache = [];
            return $this->catalogReadPermissionCache;
        }

        $site = $this->site()->first();
        if (!$site) {
            $this->catalogReadPermissionCache = [];
            return $this->catalogReadPermissionCache;
        }

        $subscriptions = $site->getActiveSubscriptions();
        if ($subscriptions->isEmpty()) {
            $this->catalogReadPermissionCache = [];
            return $this->catalogReadPermissionCache;
        }

        $permissions = [];

        foreach ($subscriptions as $subscription) {
            foreach ($subscription->getAccessibleModules() as $module) {
                $permission = "{$module->code}.read";
                if ($this->permissionExists($permission)) {
                    $permissions[] = $permission;
                }
            }

            foreach ($subscription->getAccessibleSubModules() as $subModule) {
                $moduleCode = $subModule->module?->code ?? null;
                if (!$moduleCode) {
                    continue;
                }
                $permission = "{$moduleCode}.{$subModule->code}.read";
                if ($this->permissionExists($permission)) {
                    $permissions[] = $permission;
                }
            }

            foreach ($subscription->getAccessibleSections() as $section) {
                $moduleCode = $section->subModule?->module?->code ?? null;
                $subModuleCode = $section->subModule?->code ?? null;
                if (!$moduleCode || !$subModuleCode) {
                    continue;
                }
                $permission = "{$moduleCode}.{$subModuleCode}.{$section->code}.read";
                if ($this->permissionExists($permission)) {
                    $permissions[] = $permission;
                }
            }
        }

        $this->catalogReadPermissionCache = array_values(array_unique($permissions));
        return $this->catalogReadPermissionCache;
    }

    private function getActiveCatalogCodes(): Collection
    {
        if (!$this->isCompanyUser() || !$this->site_id) {
            return collect();
        }

        $site = $this->site()->first();
        if (!$site) {
            return collect();
        }

        $subscriptions = $site->getActiveSubscriptions();
        if ($subscriptions->isEmpty()) {
            return collect();
        }

        $codes = collect();

        foreach ($subscriptions as $subscription) {
            $codes = $codes
                ->merge($subscription->getAccessibleModules()->pluck('code'))
                ->merge($subscription->getAccessibleSubModules()->pluck('code'))
                ->merge($subscription->getAccessibleSections()->pluck('code'));
        }

        return $codes
            ->map(fn($code) => mb_strtolower(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();
    }
}
