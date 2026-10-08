<?php

namespace App\Imports;

use App\Models\OnboardingStep;
use App\Models\User;
use App\Notifications\CollaboratorWelcomeNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\Security\RoleScopeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Spatie\Permission\Models\Role;

class UserImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsEmptyRows, WithChunkReading, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures, SkipsErrors;

    private int $createdUsersCount = 0;
    private int $pendingApprovalUsersCount = 0;
    private ?RoleScopeService $roleScopeService = null;

    public function __construct(
        private readonly int $enterpriseId,
        private readonly int $siteId,
        private readonly array $defaultPermissions = [],
        private readonly bool $sendWelcomeEmail = true,
        private readonly string $defaultRole = 'lecteur',
        private readonly bool $restrictPrivilegedRoles = false,
        private readonly ?int $createdByUserId = null,
        private readonly bool $requiresManualApproval = false
    ) {}

    public function onRow(Row $row): void
    {
        $data = $row->toArray();

        $firstName = trim((string) ($data['first_name'] ?? $data['prenoms'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? $data['nom'] ?? ''));
        $name = trim((string) ($data['name'] ?? $firstName . ' ' . $lastName));
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        // Validation stricte de l'email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Invalid or missing email in import', [
                'row' => $row->getIndex(),
                'email' => $email,
            ]);
            return; // Skip cette ligne
        }

        // Vérifier que l'email n'existe pas déjà
        if (User::where('email', $email)->exists()) {
            Log::warning('Email already exists in import', [
                'row' => $row->getIndex(),
                'email' => $email,
            ]);
            return; // Skip cette ligne
        }

        $role = trim((string) ($data['role'] ?? $this->defaultRole));

        $resolvedRole = Role::query()->where('name', $role)->first();

        // Valider que le rôle existe réellement dans Spatie
        if (!$resolvedRole) {
            Log::warning('Invalid role in import, using default', [
                'row' => $row->getIndex(),
                'role' => $role,
                'default' => $this->defaultRole,
            ]);
            $role = $this->defaultRole;
            $resolvedRole = Role::query()->where('name', $role)->first();
        }

        if (!$this->isRoleAllowedForImport($role, $resolvedRole)) {
            Log::warning('Role outside collaborator catalog in import, using default', [
                'row' => $row->getIndex(),
                'role' => $role,
                'default' => $this->defaultRole,
            ]);
            $role = $this->defaultRole;
            $resolvedRole = Role::query()->where('name', $role)->first();
        }

        if ($this->restrictPrivilegedRoles && $this->isForbiddenRoleForRestrictedSiteManager($role, $resolvedRole)) {
            Log::warning('Forbidden role in import for restricted site manager, using default', [
                'row' => $row->getIndex(),
                'role' => $role,
                'default' => $this->defaultRole,
            ]);
            $defaultRole = Role::query()->where('name', $this->defaultRole)->first();
            $role = $this->isForbiddenRoleForRestrictedSiteManager($this->defaultRole, $defaultRole)
                ? 'lecteur'
                : $this->defaultRole;
        }

        $phone = $data['phone'] ?? $data['telephone'] ?? null;
        $jobTitle = $data['job_title'] ?? $data['poste'] ?? null;
        if (empty($jobTitle)) {
            Log::warning('Missing job title in import, row skipped', [
                'row' => $row->getIndex(),
                'email' => $email,
            ]);
            return;
        }
        $address = $data['address'] ?? $data['adresse'] ?? null;
        $startDate = $data['start_date'] ?? $data['date_prise_service'] ?? null;

        // Générer username unique
        if (!isset($data['username']) || empty($data['username'])) {
            $base = strtolower(preg_replace('/\s+/', '', $firstName . '.' . $lastName));
            $username = $base;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $counter;
                $counter++;
            }
        } else {
            $username = $data['username'];
        }

        // Générer mot de passe temporaire
        $tempPassword = Str::random(16);

        try {
            $user = User::create([
                'name' => $name,
                'first_name' => $firstName ?: $name,
                'last_name' => $lastName ?: $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($tempPassword),
                'phone' => $phone,
                'address' => $address,
                'job_title' => $jobTitle,
                'start_date' => $startDate ?: null,
                'user_type' => User::TYPE_COMPANY,
                'enterprise_id' => $this->enterpriseId,
                'site_id' => $this->siteId,
                'is_active' => !$this->requiresManualApproval,
                'must_change_password' => true,
                'collaborator_approval_status' => $this->requiresManualApproval ? 'pending_admin_approval' : 'approved',
                'collaborator_requested_by' => $this->requiresManualApproval ? $this->createdByUserId : null,
                'collaborator_requested_at' => $this->requiresManualApproval ? now() : null,
                'collaborator_approved_by' => null,
                'collaborator_approved_at' => null,
                'collaborator_rejected_by' => null,
                'collaborator_rejected_at' => null,
                'collaborator_rejection_reason' => null,
            ]);

            // Assigner le rôle
            if ($role) {
                try {
                    $user->assignRole($role);
                } catch (\Exception $e) {
                    Log::warning('Failed to assign role during import', [
                        'user_id' => $user->id,
                        'role' => $role,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Assigner les permissions
            if ($role === 'admin_entreprise') {
                $user->syncPermissions([]);
            } elseif (!empty($this->defaultPermissions)) {
                try {
                    $user->syncPermissions($this->defaultPermissions);
                } catch (\Exception $e) {
                    Log::warning('Failed to assign permissions during import', [
                        'user_id' => $user->id,
                        'permissions' => $this->defaultPermissions,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Créer l'étape d'onboarding
            OnboardingStep::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'password_changed' => false,
                    'activity_domain_selected' => false,
                    'onboarding_completed' => false,
                ]
            );

            // Envoyer l'email de bienvenue
            if ($this->sendWelcomeEmail && !$this->requiresManualApproval) {
                try {
                    $createdBy = $this->createdByUserId ? User::find($this->createdByUserId) : null;
                    $user->notify(new CollaboratorWelcomeNotification(
                        $tempPassword,
                        $createdBy
                    ));
                } catch (\Exception $e) {
                    Log::error('Failed to send welcome email during import', [
                        'user_id' => $user->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Send verification link immediately after user creation/import.
            if (!$this->requiresManualApproval && !$user->hasVerifiedEmail()) {
                try {
                    $user->notify(new VerifyEmailNotification());
                } catch (\Exception $e) {
                    Log::error('Failed to send verification email during import', [
                        'user_id' => $user->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('User imported successfully', [
                'user_id' => $user->id,
                'email' => $email,
                'role' => $role,
            ]);

            $this->createdUsersCount++;
            if ($this->requiresManualApproval) {
                $this->pendingApprovalUsersCount++;
            }
        } catch (\Exception $e) {
            Log::error('Failed to create user during import', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function prepareForValidation($data, $index)
    {
        $firstName = $data['first_name'] ?? $data['prenoms'] ?? $data['prenom'] ?? null;
        $lastName = $data['last_name'] ?? $data['nom'] ?? $data['nom_de_famille'] ?? null;
        $email = $data['email'] ?? $data['e_mail'] ?? $data['courriel'] ?? $data['adresse_e_mail'] ?? $data['adresse_email'] ?? null;
        $jobTitle = $data['job_title'] ?? $data['poste'] ?? $data['fonction'] ?? $data['titre'] ?? null;

        $data['first_name'] = $firstName;
        $data['prenoms'] = $firstName;
        $data['last_name'] = $lastName;
        $data['nom'] = $lastName;
        $data['email'] = $email;
        $data['job_title'] = $jobTitle;
        $data['poste'] = $jobTitle;
        
        $data['role'] = $data['role'] ?? $data['profil'] ?? null;
        $data['phone'] = $data['phone'] ?? $data['telephone'] ?? $data['tel'] ?? $data['num'] ?? null;
        $data['address'] = $data['address'] ?? $data['adresse'] ?? $data['lieu'] ?? null;
        $data['start_date'] = $data['start_date'] ?? $data['date_prise_service'] ?? $data['date_embauche'] ?? $data['date_d_embauche'] ?? null;

        return $data;
    }

    public function rules(): array
    {
        return [
            '*.email' => 'required|email|unique:users,email',
            '*.first_name' => 'nullable|string|max:255',
            '*.last_name' => 'nullable|string|max:255',
            '*.prenoms' => 'nullable|string|max:255',
            '*.nom' => 'nullable|string|max:255',
            '*.job_title' => 'required_without:*.poste|string|max:255',
            '*.poste' => 'required_without:*.job_title|string|max:255',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getCreatedUsersCount(): int
    {
        return $this->createdUsersCount;
    }

    public function getPendingApprovalUsersCount(): int
    {
        return $this->pendingApprovalUsersCount;
    }

    private function isForbiddenRoleForRestrictedSiteManager(string $roleName, ?Role $role = null): bool
    {
        return in_array($roleName, ['admin_entreprise', 'site_manager'], true)
            || $this->isAnyCustomEnterpriseRole($roleName, $role);
    }

    private function isRoleAllowedForImport(string $roleName, ?Role $role = null): bool
    {
        $catalogRoleNames = array_keys(config('role_catalog.roles', []));
        if (in_array($roleName, $catalogRoleNames, true)) {
            return true;
        }

        $role ??= Role::query()->where('name', $roleName)->first();
        if (!$role) {
            return false;
        }

        if ($this->roleScopeService()->isLegacyCustomRoleWithoutEnterpriseScope($role, $roleName)) {
            return false;
        }

        if ($this->roleScopeService()->isRoleStrictlyScopedToEnterprise($role, $this->enterpriseId)) {
            return true;
        }

        if ($this->roleScopeService()->isAnyCustomEnterpriseRoleName($roleName)) {
            return false;
        }

        return false;
    }

    private function isAnyCustomEnterpriseRole(string $roleName, ?Role $role = null): bool
    {
        $role ??= Role::query()->where('name', $roleName)->first();
        return $role && (int) ($role->enterprise_id ?? 0) > 0;
    }

    private function roleScopeService(): RoleScopeService
    {
        if ($this->roleScopeService === null) {
            $this->roleScopeService = app(RoleScopeService::class);
        }

        return $this->roleScopeService;
    }
}
