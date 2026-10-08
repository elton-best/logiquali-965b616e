<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Events\User\PasswordResetRequested;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\EnterpriseAdminPermissionService;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use App\Services\PermissionNormMappingService;
use App\Services\Security\RoleScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\Settings\SuperAdminSettingsService;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role as SpatieRole;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class UserController extends Controller
{
    public function __construct(
        private readonly RoleScopeService $roleScopeService
    ) {}

    /**
     * Endpoint dédié au sélecteur de collaborateurs pour la fiche de poste.
     * Option A:
     * - Admin entreprise: tous les collaborateurs de l'entreprise
     * - Responsable de site: uniquement collaborateurs de son site
     */
    public function jobDescriptionCollaborators(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $currentUser = $request->user();
        $requestedSiteId = $request->integer('site_id');
        $requestedDepartment = trim((string) $request->input('department', ''));
        $requestedJobTitle = trim((string) $request->input('job_title', ''));
        $requestedSearch = trim((string) $request->input('search', ''));

        $query = User::query()
            ->with([
                'site:id,name,is_headquarter',
                'jobDescription:id,user_id,department,job_title',
            ])
            ->where('user_type', 'company')
            ->where('is_active', true);

        // Isolation enterprise (hors super admin)
        if ($currentUser->user_type !== 'super_admin') {
            if (!$currentUser->enterprise_id) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }
            $query->where('enterprise_id', $currentUser->enterprise_id);
        }

        // Option A: responsable de site limité à son site (sauf admin entreprise)
        if (
            $currentUser->user_type === 'company'
            && $currentUser->hasRole('site_manager')
            && !$currentUser->hasRole('admin_entreprise')
        ) {
            $query->where('site_id', $currentUser->site_id);
        } elseif ($requestedSiteId > 0) {
            // Admin entreprise / super admin: possibilité de filtrer par site sélectionné
            $query->where('site_id', $requestedSiteId);
        }

        if ($requestedDepartment !== '') {
            $query->whereHas('jobDescription', function ($jobDescriptionQuery) use ($requestedDepartment) {
                $jobDescriptionQuery->where('department', 'like', '%' . $requestedDepartment . '%');
            });
        }

        if ($requestedJobTitle !== '') {
            $query->where(function ($userQuery) use ($requestedJobTitle) {
                $userQuery->where('job_title', 'like', '%' . $requestedJobTitle . '%')
                    ->orWhereHas('jobDescription', function ($jobDescriptionQuery) use ($requestedJobTitle) {
                        $jobDescriptionQuery->where('job_title', 'like', '%' . $requestedJobTitle . '%');
                    });
            });
        }

        if ($requestedSearch !== '') {
            $query->where(function ($searchQuery) use ($requestedSearch) {
                $searchQuery->where('first_name', 'like', '%' . $requestedSearch . '%')
                    ->orWhere('last_name', 'like', '%' . $requestedSearch . '%')
                    ->orWhere('name', 'like', '%' . $requestedSearch . '%')
                    ->orWhere('email', 'like', '%' . $requestedSearch . '%')
                    ->orWhere('job_title', 'like', '%' . $requestedSearch . '%')
                    ->orWhereHas('jobDescription', function ($jobDescriptionQuery) use ($requestedSearch) {
                        $jobDescriptionQuery->where('department', 'like', '%' . $requestedSearch . '%')
                            ->orWhere('job_title', 'like', '%' . $requestedSearch . '%');
                    });
            });
        }

        $users = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id',
                'first_name',
                'last_name',
                'name',
                'email',
                'job_title',
                'site_id',
                'signature_path',
            ]);

        $payload = $users->map(function (User $user) {
            $firstName = $user->first_name ?? '';
            $lastName = $user->last_name ?? '';
            $fullName = trim($lastName . ' ' . $firstName);
            if ($fullName === '') {
                $fullName = $user->name ?: $user->email;
            }

            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $fullName,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'department' => $user->jobDescription?->department,
                'job_description_title' => $user->jobDescription?->job_title,
                'site_id' => $user->site_id,
                'site_name' => $user->site?->name,
                'is_headquarter_site' => (bool) ($user->site?->is_headquarter ?? false),
                'signature_path' => $user->signature_path,
                'signature_url' => $user->signature_url,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    public function index(Request $request)
    {
        // Vérification Policy
        $this->authorize('viewAny', User::class);

        $user = $request->user();

        // Filter by user's enterprise
        $query = User::with([
            'enterprise',
            'site',
            'permissions',
            'roles.permissions',
            'collaboratorRequestedBy:id,name,email',
            'collaboratorApprovedBy:id,name,email',
            'collaboratorRejectedBy:id,name,email',
        ]);

        if ($user->enterprise_id) {
            $query->where('enterprise_id', $user->enterprise_id);
        }

        // Site manager is strictly scoped to their own site.
        if (
            $user->user_type === 'company'
            && $user->hasRole('site_manager')
            && !$user->hasRole('admin_entreprise')
            && $user->site_id
        ) {
            $query->where('site_id', $user->site_id);
        }

        // Filter by site_id if provided
        if ($request->has('site_id') && $request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        $users = $query->paginate(20);

        return UserResource::collection($users);
    }

    public function exportDocx(Request $request)
    {
        // Vérification Policy
        $this->authorize('viewAny', User::class);

        $user = $request->user();

        // Filter by user's enterprise
        $query = User::with(['site']);

        if ($user->enterprise_id) {
            $query->where('enterprise_id', $user->enterprise_id);
        }

        // Site manager is strictly scoped to their own site.
        if (
            $user->user_type === 'company'
            && $user->hasRole('site_manager')
            && !$user->hasRole('admin_entreprise')
            && $user->site_id
        ) {
            $query->where('site_id', $user->site_id);
        }

        // Filter by site_id if provided
        if ($request->has('site_id') && $request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        $users = $query->orderBy('last_name')->orderBy('first_name')->get();

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection(['orientation' => 'landscape']);
        $phpWord->addTitleStyle(1, ['bold' => true, 'size' => 16, 'color' => '2E3B55'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addTitle('Liste du Personnel', 1);
        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 50,
        ];
        $firstRowStyle = [
            'bgColor' => '2E3B55',
        ];
        $firstRowTextStyle = [
            'color' => 'FFFFFF',
            'bold' => true,
        ];
        $phpWord->addTableStyle('Personnel Table', $tableStyle, $firstRowStyle);
        $table = $section->addTable('Personnel Table');

        // Header
        $table->addRow();
        $table->addCell(2500)->addText('Nom complet', $firstRowTextStyle);
        $table->addCell(2500)->addText('Email', $firstRowTextStyle);
        $table->addCell(2000)->addText('Poste', $firstRowTextStyle);
        $table->addCell(2000)->addText('Site', $firstRowTextStyle);
        $table->addCell(1500)->addText('Téléphone', $firstRowTextStyle);
        $table->addCell(1500)->addText('Prise de service', $firstRowTextStyle);

        foreach ($users as $u) {
            $fullName = trim($u->last_name . ' ' . $u->first_name);
            if (empty($fullName)) $fullName = $u->name;
            
            $table->addRow();
            $table->addCell(2500)->addText($fullName ?: 'N/A');
            $table->addCell(2500)->addText($u->email);
            $table->addCell(2000)->addText($u->job_title ?: $u->role ?: 'N/A');
            $table->addCell(2000)->addText($u->site?->name ?: 'N/A');
            $table->addCell(1500)->addText($u->phone ?: '-');
            $table->addCell(1500)->addText($u->start_date ? \Carbon\Carbon::parse($u->start_date)->format('d/m/Y') : '-');
        }

        $fileName = 'personnel_' . date('Ymd_His') . '.docx';
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempPath = $tempDir . '/' . $fileName;

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return response()->download($tempPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        // Vérification Policy
        $this->authorize('create', User::class);

        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users',
            'email' => ['required', 'string', 'max:255', 'email', 'regex:/^[^@\s]+@[^@\s]+\.[^@\s]+$/', 'unique:users,email'],
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
            'access_roles' => 'nullable|array',
            'access_roles.*' => 'string|exists:roles,name',
            'photo_path' => 'nullable|string',
            'job_title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'user_type' => 'nullable|in:super_admin,company,clientb,clientB,client_b',
            'enterprise_id' => 'nullable|exists:enterprises,id',
            'site_id' => 'required|exists:sites,id',
            'additional_sites' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'generate_password' => 'boolean',
            'send_welcome_email' => 'nullable|boolean',
        ]);

        // Force enterprise_id to user's enterprise if not super_admin
        if ($user->user_type !== 'super_admin' && $user->enterprise_id) {
            $validated['enterprise_id'] = $user->enterprise_id;
        }

        if ($user->user_type !== 'super_admin') {
            $validated['user_type'] = 'company';
        } elseif (empty($validated['user_type'])) {
            $validated['user_type'] = 'company';
        }

        // Site managers can only create users on their own site.
        if (
            $user->user_type === 'company'
            && $user->hasRole('site_manager')
            && !$user->hasRole('admin_entreprise')
            && $user->site_id
        ) {
            $validated['site_id'] = $user->site_id;
        }

        // Construire le nom complet à partir de first_name et last_name
        if (empty($validated['name'])) {
            $validated['name'] = trim($validated['first_name'] . ' ' . $validated['last_name']);
        }

        // Générer le username si non fourni : prenom.nom
        if (empty($validated['username'])) {
            $baseUsername = strtolower($validated['first_name'] . '.' . $validated['last_name']);
            $baseUsername = preg_replace('/\s+/', '', $baseUsername); // Retirer espaces

            // Vérifier unicité et ajouter numéro si nécessaire
            $username = $baseUsername;
            $counter = 1;
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            $validated['username'] = $username;
        }

        // Pour les collaborateurs, le mot de passe est toujours auto-généré
        // afin que le créateur n'ait jamais connaissance des identifiants.
        $isCollaborator = ($validated['user_type'] ?? null) !== 'super_admin';
        $requiresManualApproval = $this->requiresManualCollaboratorApproval($user, $validated['user_type'] ?? null);

        if ($isCollaborator) {
            $validated['password'] = \Illuminate\Support\Str::random(16);
            $validated['must_change_password'] = true;
            $validated['send_welcome_email'] = !$requiresManualApproval;
        } elseif (!empty($validated['generate_password']) || empty($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Str::random(16);
            $validated['must_change_password'] = true;
        }

        $validated['password'] = Hash::make($validated['password']);

        // Normaliser le poste (legacy + nouveau champ)
        if (!empty($validated['job_title']) && empty($validated['role'])) {
            $validated['role'] = $validated['job_title'];
        }
        if (!empty($validated['role']) && empty($validated['job_title'])) {
            $validated['job_title'] = $validated['role'];
        }

        // Set is_active, default to true if not provided.
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['collaborator_approval_status'] = 'approved';
        $validated['collaborator_rejection_reason'] = null;

        $requestedAccessRoles = $validated['access_roles'] ?? [];
        $targetIsEnterpriseAdmin = in_array('admin_entreprise', $requestedAccessRoles)
            || (!array_key_exists('access_roles', $validated) && $user->hasRole('admin_entreprise'));

        if ($requiresManualApproval) {
            $validated['is_active'] = false;
            $validated['collaborator_approval_status'] = 'pending_admin_approval';
            $validated['collaborator_requested_by'] = $user->id;
            $validated['collaborator_requested_at'] = now();
            $validated['send_welcome_email'] = false;
        }

        $newUser = User::create($validated);

        $requestedAccessRoles = $validated['access_roles'] ?? [];
        if (!empty($requestedAccessRoles)) {
            foreach ($requestedAccessRoles as $requestedAccessRole) {
                if (
                    $this->isRestrictedSiteManager($user)
                    && is_string($requestedAccessRole)
                    && $this->isForbiddenForRestrictedSiteManager($requestedAccessRole)
                ) {
                    $newUser->delete();
                    return response()->json([
                        'success' => false,
                        'message' => "Le rôle '{$requestedAccessRole}' ne peut pas être assigné par un responsable de site.",
                    ], 422);
                }

                if (!$this->isRoleEligibleForSite((string) $requestedAccessRole, (int) $newUser->site_id, $user)) {
                    $newUser->delete();
                    return response()->json([
                        'success' => false,
                        'message' => "Le rôle '{$requestedAccessRole}' n'est pas éligible pour les normes actives de ce site.",
                    ], 422);
                }
            }

            try {
                $this->attachRolesToUser($newUser, $requestedAccessRoles, 'user_created_role_assigned');
            } catch (\Exception $e) {
                Log::warning('Failed to assign access roles: ' . $e->getMessage());
            }
        } else {
            // Rôle par défaut : lecteur
            try {
                $defaultRole = \Spatie\Permission\Models\Role::where('name', 'lecteur')->exists()
                    ? 'lecteur'
                    : null;

                if ($defaultRole) {
                    $this->attachRolesToUser($newUser, [$defaultRole], 'user_created_default_role_assigned');
                }
            } catch (\Exception $e) {
                Log::warning('Failed to assign default role: ' . $e->getMessage());
            }
        }

        // RBAC pur : les permissions viennent uniquement des rôles.
        // Les permissions directes sur les utilisateurs sont désactivées.
        if (in_array('admin_entreprise', $requestedAccessRoles)) {
            app(EnterpriseAdminPermissionService::class)->sync(false);
        }
        $newUser->syncPermissions([]);

        $activationEmailSent = false;
        $verificationEmailSent = false;
        $approvalRequired = $requiresManualApproval;

        if (!$approvalRequired && $isCollaborator) {
            [
                'activation_sent' => $activationEmailSent,
                'verification_sent' => $verificationEmailSent,
                'admin_notified_email_failure' => $adminNotifiedEmailFailure,
            ]
                = $this->dispatchCollaboratorActivationInvitations($newUser);
        }

        if ($approvalRequired) {
            \App\Models\SecurityAuditLog::logEvent(
                eventType: 'user_management',
                action: 'collaborator_creation_pending_admin_approval',
                resourceType: User::class,
                resourceId: $newUser->id,
                metadata: [
                    'requested_by' => $user->id,
                    'target_user_id' => $newUser->id,
                    'site_id' => $newUser->site_id,
                    'enterprise_id' => $newUser->enterprise_id,
                ],
                riskLevel: 'high'
            );
        }

        $creationMessage = $approvalRequired
            ? "Demande de création envoyée. Un admin d'entreprise doit approuver avant l'activation du compte."
            : ($activationEmailSent
                ? 'User created successfully'
                : 'Utilisateur créé, mais l’email d’activation n’a pas pu être envoyé. Les admins ont été notifiés.');

        return response()->json([
            'success' => true,
            'data' => new UserResource($newUser->load(['enterprise', 'site', 'permissions'])),
            'message' => $creationMessage,
            'notifications' => [
                'activation_email_sent' => $activationEmailSent,
                'verification_email_sent' => $verificationEmailSent,
                'admin_notified_email_failure' => $adminNotifiedEmailFailure ?? false,
                'approval_required' => $approvalRequired,
            ],
        ], 201);
    }

    public function pendingApprovals(Request $request)
    {
        $actor = $request->user();
        if (!$this->canApproveCollaborators($actor)) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $query = User::query()
            ->with(['site:id,name', 'collaboratorRequestedBy:id,name,email'])
            ->where('enterprise_id', $actor->enterprise_id)
            ->where('user_type', User::TYPE_COMPANY)
            ->where('collaborator_approval_status', 'pending_admin_approval')
            ->orderByDesc('collaborator_requested_at');

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->input('site_id'));
        }

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($query->paginate(20)),
        ]);
    }

    public function approveCollaborator(Request $request, int $id)
    {
        $actor = $request->user();
        if (!$this->canApproveCollaborators($actor)) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $targetUser = User::query()
            ->where('id', $id)
            ->where('enterprise_id', $actor->enterprise_id)
            ->firstOrFail();

        if ($targetUser->collaborator_approval_status !== 'pending_admin_approval') {
            return response()->json([
                'message' => "Cette demande n'est plus en attente de validation.",
            ], 422);
        }

        DB::transaction(function () use ($targetUser, $actor): void {
            $targetUser->forceFill([
                'collaborator_approval_status' => 'approved',
                'collaborator_approved_by' => $actor->id,
                'collaborator_approved_at' => now(),
                'collaborator_rejected_by' => null,
                'collaborator_rejected_at' => null,
                'collaborator_rejection_reason' => null,
                'is_active' => true,
            ])->save();
        });

        [
            'activation_sent' => $activationEmailSent,
            'verification_sent' => $verificationEmailSent,
            'admin_notified_email_failure' => $adminNotifiedEmailFailure,
        ]
            = $this->dispatchCollaboratorActivationInvitations($targetUser);

        \App\Models\SecurityAuditLog::logEvent(
            eventType: 'user_management',
            action: 'collaborator_creation_approved',
            resourceType: User::class,
            resourceId: $targetUser->id,
            metadata: [
                'approved_by' => $actor->id,
                'requested_by' => $targetUser->collaborator_requested_by,
                'target_user_id' => $targetUser->id,
                'activation_email_sent' => $activationEmailSent,
                'verification_email_sent' => $verificationEmailSent,
            ],
            riskLevel: 'high'
        );

        return response()->json([
            'success' => true,
            'message' => 'Demande approuvée. Le collaborateur a reçu son invitation d’activation.',
            'data' => new UserResource($targetUser->fresh()->load(['enterprise', 'site', 'permissions'])),
            'notifications' => [
                'activation_email_sent' => $activationEmailSent,
                'verification_email_sent' => $verificationEmailSent,
                'admin_notified_email_failure' => $adminNotifiedEmailFailure,
            ],
        ]);
    }

    public function rejectCollaborator(Request $request, int $id)
    {
        $actor = $request->user();
        if (!$this->canApproveCollaborators($actor)) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:2000',
        ]);

        $targetUser = User::query()
            ->where('id', $id)
            ->where('enterprise_id', $actor->enterprise_id)
            ->firstOrFail();

        if ($targetUser->collaborator_approval_status !== 'pending_admin_approval') {
            return response()->json([
                'message' => "Cette demande n'est plus en attente de validation.",
            ], 422);
        }

        DB::transaction(function () use ($targetUser, $actor, $validated): void {
            $targetUser->forceFill([
                'collaborator_approval_status' => 'rejected',
                'collaborator_rejected_by' => $actor->id,
                'collaborator_rejected_at' => now(),
                'collaborator_rejection_reason' => $validated['reason'],
                'is_active' => false,
            ])->save();
        });

        \App\Models\SecurityAuditLog::logEvent(
            eventType: 'user_management',
            action: 'collaborator_creation_rejected',
            resourceType: User::class,
            resourceId: $targetUser->id,
            metadata: [
                'rejected_by' => $actor->id,
                'requested_by' => $targetUser->collaborator_requested_by,
                'target_user_id' => $targetUser->id,
                'reason' => $validated['reason'],
            ],
            riskLevel: 'high'
        );

        return response()->json([
            'success' => true,
            'message' => 'Demande rejetée.',
            'data' => new UserResource($targetUser->fresh()->load(['enterprise', 'site', 'permissions'])),
        ]);
    }

    public function show(Request $request, User $user)
    {
        // Vérification Policy
        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['enterprise', 'site', 'permissions']))
        ]);
    }

    public function update(Request $request, User $user)
    {
        Log::info('[UserController] update() called', [
            'request_id' => $request->route('user'),
            'targetUser_id' => $user->id ?? 'NULL',
            'targetUser_exists' => $user->exists ?? false,
        ]);

        // Vérification Policy
        $this->authorize('update', $user);

        $currentUser = $request->user();

        if (
            $user->collaborator_approval_status === 'pending_admin_approval'
            && !$currentUser->isSuperAdmin()
            && !$this->canApproveCollaborators($currentUser)
        ) {
            return response()->json([
                'success' => false,
                'message' => "Seul un admin d'entreprise peut modifier un collaborateur en attente de validation.",
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:8',
            'phone' => 'nullable|string',
            'photo_path' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'user_type' => 'sometimes|in:super_admin,company,clientb,clientB,client_b',
            'enterprise_id' => 'nullable|exists:enterprises,id',
            'site_id' => 'nullable|exists:sites,id',
            'role' => 'nullable|string',
            'access_roles' => 'nullable|array',
            'access_roles.*' => 'string|exists:roles,name',
            'job_title' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        // Prevent changing enterprise_id if not super_admin
        if ($currentUser->user_type !== 'super_admin' && isset($validated['enterprise_id'])) {
            unset($validated['enterprise_id']);
        }

        if ($currentUser->user_type !== 'super_admin' && isset($validated['user_type'])) {
            unset($validated['user_type']);
        }

        // Site managers cannot move users to another site.
        if (
            $currentUser->user_type === 'company'
            && $currentUser->hasRole('site_manager')
            && !$currentUser->hasRole('admin_entreprise')
            && array_key_exists('site_id', $validated)
        ) {
            $validated['site_id'] = $currentUser->site_id;
        }

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['must_change_password'] = false;
            $validated['password_changed_at'] = now();
        }

        // Normaliser le poste (legacy + nouveau champ)
        if (!empty($validated['job_title']) && empty($validated['role'])) {
            $validated['role'] = $validated['job_title'];
        }
        if (!empty($validated['role']) && empty($validated['job_title'])) {
            $validated['job_title'] = $validated['role'];
        }

        // Rebuild display name when first/last name are updated.
        if (isset($validated['first_name']) || isset($validated['last_name'])) {
            $firstName = $validated['first_name'] ?? $user->first_name ?? '';
            $lastName = $validated['last_name'] ?? $user->last_name ?? '';
            $validated['name'] = trim($firstName . ' ' . $lastName);
        }

        if (isset($validated['access_roles'])) {
            $requestedAccessRoles = $validated['access_roles'] ?? [];
            foreach ($requestedAccessRoles as $requestedAccessRole) {
                if (
                    $this->isRestrictedSiteManager($currentUser)
                    && $this->isForbiddenForRestrictedSiteManager((string) $requestedAccessRole)
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => "Le rôle '{$requestedAccessRole}' ne peut pas être assigné par un responsable de site.",
                    ], 422);
                }

                $targetSiteId = (int) ($validated['site_id'] ?? $user->site_id ?? 0);
                if (!$this->isRoleEligibleForSite((string) $requestedAccessRole, $targetSiteId, $currentUser)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Le rôle '{$requestedAccessRole}' n'est pas éligible pour les normes actives de ce site.",
                    ], 422);
                }
            }
        }

        if ($user->collaborator_approval_status === 'pending_admin_approval') {
            if (array_key_exists('is_active', $validated) && (bool) $validated['is_active'] === true) {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible d'activer un collaborateur en attente hors workflow d'approbation.",
                ], 422);
            }

            // Keep pending collaborators inactive until explicit approval endpoint.
            $validated['is_active'] = false;
        }

        $user->update($validated);

        if (isset($validated['access_roles'])) {
            $requestedAccessRoles = $validated['access_roles'] ?? [];
            if (!empty($requestedAccessRoles)) {
                $this->attachRolesToUser($user, $requestedAccessRoles, 'user_updated_role_assigned');
            } else {
                // If access_roles array is passed but empty, clear all roles.
                $user->syncRoles([]);
            }
        }

        // RBAC pur : les permissions viennent uniquement des rôles.
        $targetIsEnterpriseAdmin = isset($validated['access_roles']) 
            ? in_array('admin_entreprise', $validated['access_roles']) 
            : $user->hasRole('admin_entreprise');
            
        if ($targetIsEnterpriseAdmin) {
            app(EnterpriseAdminPermissionService::class)->sync(false);
        }
        $user->syncPermissions([]);

        if (array_key_exists('access_roles', $validated)) {
            $this->refreshAuthorizationState($user, 'user_access_updated');
        }

        return response()->json([
            'success' => true,
            'data' => new UserResource($user->load(['enterprise', 'site', 'permissions'])),
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        // Vérification Policy
        $this->authorize('delete', $user);

        $user->delete();

        return response()->noContent();
    }

    /**
     * Toggle user active status
     */
    /**
     * Toggle a user's active status.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleActive(Request $request, $id)
    {
        try {
            $currentUser = $request->user();
            $user = User::findOrFail($id);

            // Verify user has access
            if ($currentUser->user_type !== 'super_admin' && $user->enterprise_id !== $currentUser->enterprise_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Prevent self-deactivation
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate your own account'
                ], 400);
            }

            if ($user->collaborator_approval_status === 'pending_admin_approval') {
                return response()->json([
                    'success' => false,
                    'message' => "Impossible d'activer un collaborateur en attente hors workflow d'approbation.",
                ], 422);
            }

            $user->is_active = !$user->is_active;
            $user->save();

            if (!$user->is_active) {
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'data' => new UserResource($user->load('enterprise')),
                'message' => 'User status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error toggling user status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    /**
     * Update the authenticated user's profile.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string',
                'photo_path' => 'nullable|string',
                'password' => app(SuperAdminSettingsService::class)->passwordRules(false),
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
                $validated['must_change_password'] = false;
                $validated['password_changed_at'] = now();
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'data' => new UserResource($user->load(['enterprise', 'site'])),
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a user's signature image.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadSignature(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = $request->user();

        $canUpdateSelf = (int) $currentUser->id === (int) $targetUser->id;
        if (!$canUpdateSelf) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le collaborateur concerné peut importer sa signature depuis ses paramètres.',
            ], 403);
        }

        if (
            $currentUser->user_type !== 'super_admin'
            && (int) $targetUser->enterprise_id !== (int) $currentUser->enterprise_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $validated = $request->validate([
            'signature' => 'required|file|mimes:png,jpg,jpeg|max:1024',
        ]);

        if (!empty($targetUser->signature_path) && !str_starts_with($targetUser->signature_path, 'http')) {
            Storage::disk('public')->delete($targetUser->signature_path);
        }

        $file = $validated['signature'];
        $filename = 'signature_' . $targetUser->id . '_' . now()->timestamp . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('signatures/users/' . $targetUser->id, $filename, 'public');

        $targetUser->forceFill([
            'signature_path' => $storedPath,
            'signature_uploaded_at' => now(),
        ])->save();

        $targetUser->onboardingStep()->updateOrCreate(
            ['user_id' => $targetUser->id],
            [
                'signature_uploaded' => true,
                'signature_data' => $storedPath,
                'signature_uploaded_at' => now(),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Signature uploaded successfully',
            'data' => [
                'signature_path' => $targetUser->signature_path,
                'signature_url' => $targetUser->signature_url,
                'signature_uploaded_at' => $targetUser->signature_uploaded_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Upload a user's profile photo.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPhoto(Request $request, $id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = $request->user();

        $canUpdateSelf = (int) $currentUser->id === (int) $targetUser->id;
        if (!$canUpdateSelf) {
            $this->authorize('update', $targetUser);
        }

        if (
            $currentUser->user_type !== 'super_admin'
            && (int) $targetUser->enterprise_id !== (int) $currentUser->enterprise_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied',
            ], 403);
        }

        $validated = $request->validate([
            'photo' => 'required|file|mimes:png,jpg,jpeg,gif|max:2048',
        ]);

        if (!empty($targetUser->photo_path) && !str_starts_with($targetUser->photo_path, 'http')) {
            Storage::disk('public')->delete($targetUser->photo_path);
        }

        $file = $validated['photo'];
        $filename = 'photo_' . $targetUser->id . '_' . now()->timestamp . '.' . $file->getClientOriginalExtension();
        $storedPath = $file->storeAs('photos/users/' . $targetUser->id, $filename, 'public');

        $targetUser->forceFill([
            'photo_path' => $storedPath,
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'data' => [
                'photo_path' => $targetUser->photo_path,
                'photo_url' => $targetUser->photo_url,
            ],
        ]);
    }

    /**
     * Assign roles to user
     */
    /**
     * Assign roles to a user (bulk).
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRoles(Request $request, $id)
    {
        return $this->addRole($request, $id);
    }

    /**
     * Convertit un payload de rôles en noms de rôles Spatie.
     *
     * @param array<string, mixed> $validated
     * @return array<int, string>
     */
    private function resolveRoleNamesFromPayload(array $validated): array
    {
        if (!empty($validated['roles']) && is_array($validated['roles'])) {
            return SpatieRole::query()
                ->whereIn('id', $validated['roles'])
                ->pluck('name')
                ->values()
                ->all();
        }

        if (!empty($validated['role_id'])) {
            $roleName = SpatieRole::query()
                ->whereKey((int) $validated['role_id'])
                ->value('name');

            return $roleName ? [$roleName] : [];
        }

        if (!empty($validated['role_name'])) {
            return [(string) $validated['role_name']];
        }

        return [];
    }

    /**
     * Vérifie les contraintes métier avant l'assignation d'un ou plusieurs rôles.
     *
     * @param array<int, string> $roleNames
     */
    private function ensureRolesCanBeAssigned(User $currentUser, User $user, array $roleNames): void
    {
        $siteId = (int) ($user->site_id ?? 0);
        foreach ($roleNames as $roleName) {
            if (
                $this->isRestrictedSiteManager($currentUser)
                && $this->isForbiddenForRestrictedSiteManager($roleName)
            ) {
                abort(422, "Le rôle '{$roleName}' ne peut pas être assigné par un responsable de site.");
            }

            if (!$this->isRoleEligibleForSite($roleName, $siteId, $currentUser)) {
                abort(422, "Le rôle '{$roleName}' n'est pas éligible pour les normes actives de ce site.");
            }
        }
    }

    /**
     * Assigne un ou plusieurs rôles sans en retirer d'autres.
     *
     * @param array<int, string> $roleNames
     */
    private function attachRolesToUser(User $user, array $roleNames, string $reason): void
    {
        $normalizedRoleNames = collect($roleNames)
            ->filter(fn ($roleName) => is_string($roleName) && trim($roleName) !== '')
            ->map(fn ($roleName) => trim($roleName))
            ->unique()
            ->values()
            ->all();

        if ($normalizedRoleNames === []) {
            return;
        }

        $user->assignRole($normalizedRoleNames);

        if (in_array('admin_entreprise', $normalizedRoleNames, true)) {
            app(EnterpriseAdminPermissionService::class)->sync(false);
        }

        $user->syncPermissions([]);
        $this->refreshAuthorizationState($user, $reason);
    }

    /**
     * Obtenir les rôles disponibles pour les collaborateurs
     */
    /**
     * Return available roles for assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableRoles()
    {
        $this->authorize('viewAny', User::class);

        $request = request();
        $currentUser = $request->user();
        $roleCatalog = config('role_catalog.roles', []);
        $roleNames = array_keys($roleCatalog);

        $siteId = (int) ($request->query('site_id') ?: ($currentUser->site_id ?? 0));
        if ($siteId <= 0) {
            $siteId = (int) ($currentUser->site_id ?? 0);
        }

        $contextEnterpriseId = $this->resolveRoleContextEnterpriseId($currentUser, $siteId);

        $activeDomains = [];
        if ($siteId > 0) {
            $subscriptions = \App\Models\EnterpriseSubscription::query()
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->whereIn('status', ['active', 'trial'])
                ->where(function ($query) {
                    $query->where(function ($trial) {
                        $trial->where('is_trial', true)
                            ->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>', now());
                    })->orWhere(function ($paid) {
                        $paid->where('is_trial', false)
                            ->whereNotNull('expiration_date')
                            ->where('expiration_date', '>', now());
                    });
                })
                ->with('offer.norms:id,domain')
                ->get();

            $activeDomains = $subscriptions
                ->flatMap(fn($subscription) => $subscription->offer?->norms?->pluck('domain') ?? collect())
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $roles = \Spatie\Permission\Models\Role::query()
            ->whereIn('name', $roleNames)
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $customEnterpriseRoles = collect();
        if ($contextEnterpriseId > 0) {
            $customEnterpriseRoles = \Spatie\Permission\Models\Role::query()
                ->where('enterprise_id', $contextEnterpriseId)
                ->with('permissions')
                ->orderBy('name')
                ->get();
        }

        $roles = $roles
            ->merge($customEnterpriseRoles)
            ->unique('name')
            ->values();

        $filteredRoles = $roles->filter(function ($role) use ($currentUser, $contextEnterpriseId, $roleCatalog, $activeDomains) {
            // Super admin can always see every role in the unified catalog.
            if ($currentUser?->isSuperAdmin()) {
                return true;
            }

            if (
                $this->isRestrictedSiteManager($currentUser)
                && $this->isForbiddenForRestrictedSiteManager((string) $role->name)
            ) {
                return false;
            }

            if ($this->isEnterpriseCustomRoleModel($role, $contextEnterpriseId)) {
                return !$this->isRestrictedSiteManager($currentUser);
            }

            $definition = $roleCatalog[$role->name] ?? null;
            if (!$definition) {
                return false;
            }

            $requiredDomains = $definition['requires_domains'] ?? [];
            if (empty($requiredDomains)) {
                return true;
            }

            return !empty(array_intersect($requiredDomains, $activeDomains));
        })->values();

        return response()->json([
            'roles' => $filteredRoles->map(function ($role) use ($roleCatalog, $contextEnterpriseId) {
                $definition = $roleCatalog[$role->name] ?? [];
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => $definition['label']
                        ?? ($this->isEnterpriseCustomRoleModel($role, $contextEnterpriseId)
                            ? 'Rôle personnalisé'
                            : $role->name),
                    'permissions_count' => $role->permissions->count(),
                    'permissions' => $role->permissions->pluck('name')->unique()->values()->toArray(),
                ];
            }),
        ]);
    }

    /**
     * Obtenir toutes les permissions disponibles
     */
    /**
     * Return available permissions (catalog) for roles/users.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailablePermissions()
    {
        $this->authorize('viewAny', User::class);

        $permissions = \Spatie\Permission\Models\Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function ($perm) {
                return explode('.', $perm->name)[0];
            });

        return response()->json([
            'permissions' => $permissions->map(function ($perms, $module) {
                return [
                    'module' => $module,
                    'permissions' => $perms->map(function ($perm) {
                        $segments = explode('.', $perm->name);
                        $resource = count($segments) > 2 ? $segments[1] : null;
                        $action = count($segments) > 1 ? $segments[count($segments) - 1] : '';
                        return [
                            'name' => $perm->name,
                            'resource' => $resource,
                            'action' => $action,
                        ];
                    })->unique('name')->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Obtenir les permissions ACTIVES filtrées par subscriptions
     * 
     * Retourne uniquement les permissions liées aux modules/sub-modules souscris du site.
     * Option 2 (Recommended): nouveau endpoint dédié pour les permissions "actives" (souscrites).
     * 
     * ⚠️ AUDIT: Rate-limited (60 req/min) pour éviter enumeration attacks.
     */
    /**
     * Get active scoped permissions for the current site/user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivePermissions(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService,
        PermissionNormMappingService $permissionNormMappingService
    ) {
        $this->authorize('viewAny', User::class);

        $user = $request->user();
        $siteId = $siteContextService->extractSiteId($request);

        // AUDIT: Log access for compliance & monitoring
        \Illuminate\Support\Facades\Log::info('permission_catalog_accessed', [
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'site_id' => $siteId,
            'enterprise_id' => $user->enterprise_id,
            'action' => 'getActivePermissions',
        ]);

        // Récupérer le catalogue filtré par subscriptions actives (utile pour meta de contexte)
        $accessCatalogService->buildCatalog($user, $siteId);
        $activeRows = $permissionNormMappingService->getActivePermissionsWithMappingsForUser($user, $siteId);
        $allRows = $activeRows
            ->filter(fn ($row) => collect($row['mappings'] ?? [])->isNotEmpty())
            ->unique(fn ($row) => $row['permission']->id)
            ->values();

        // Grouper strictement par module issu de la matrice norme/module.
        $groupedPermissions = $allRows->groupBy(function ($row) {
            $mapping = $row['mappings']->first();
            if ($mapping && $mapping->module) {
                return (string) $mapping->module->code;
            }

            return 'autres';
        });

        return response()->json([
            'permissions' => $groupedPermissions->map(function ($rows, $module) {
                return [
                    'module' => $module,
                    'permissions' => $rows->map(function ($row) {
                        $perm = $row['permission'];
                        $segments = explode('.', (string) $perm->name);
                        $resource = count($segments) > 2 ? $segments[1] : null;
                        $action = count($segments) > 1 ? $segments[count($segments) - 1] : '';
                        return [
                            'name' => $perm->name,
                            'resource' => $resource,
                            'action' => $action,
                            'is_shared' => (bool) ($row['is_shared'] ?? false),
                            'mappings' => collect($row['mappings'] ?? [])->map(function ($mapping) {
                                return [
                                    'norm_id' => $mapping->norm_id,
                                    'norm_code' => $mapping->norm?->code,
                                    'module_id' => $mapping->module_id,
                                    'module_code' => $mapping->module?->code,
                                    'sub_module_id' => $mapping->sub_module_id,
                                    'sub_module_code' => $mapping->subModule?->code,
                                    'section_id' => $mapping->section_id,
                                    'section_code' => $mapping->section?->code,
                                    'is_shared' => (bool) $mapping->is_shared,
                                ];
                            })->values(),
                        ];
                    })->unique('name')->values(),
                ];
            })->values(),
            'meta' => [
                // AUDIT: Masquer les metadata sensibles (active_subscriptions count, norms list)
                // pour prévenir information disclosure (timing attacks, enumeration)
                'permission_count' => $allRows->count(),
                'filtered_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Obtenir les permissions d'un utilisateur
     */
    /**
     * Get effective permissions for a user by id.
     *
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserPermissions($id)
    {
        $this->authorize('viewAny', User::class);

        $user = User::with(['roles.permissions', 'permissions'])->findOrFail($id);

        // Vérifier que l'utilisateur appartient à la même entreprise
        /** @var User|null $currentUser */
        $currentUser = request()->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (
            $currentUser->user_type !== 'super_admin' &&
            $user->enterprise_id !== $currentUser->enterprise_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'user_id' => $user->id,
            'user_type' => $user->user_type,
            'roles' => $user->roles->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
            ]),
            'role_permissions' => $user->getRolePermissionNames()->values()->all(),
            'direct_permissions' => $user->permissions->pluck('name')->values()->all(),
            'effective_permissions' => $user->getEffectivePermissionNames()->values()->all(),
            'active_scoped_permissions' => $user->getActiveScopedPermissionNames()->values()->all(),
            // Compat legacy frontend
            'all_permissions' => $user->getEffectivePermissionNames()->values()->all(),
            'direct_permissions_count' => $user->permissions->count(),
            'role_permissions_count' => $user->getRolePermissionNames()->count(),
            'effective_permissions_count' => $user->getEffectivePermissionNames()->count(),
            'active_scoped_permissions_count' => $user->getActiveScopedPermissionNames()->count(),
        ]);
    }

    /**
     * Assigner un rôle prédéfini à un collaborateur
     */
    /**
     * Assign a role to a user.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRole(Request $request, $id)
    {
        return $this->addRole($request, $id);
    }

    public function addRole(Request $request, $id)
    {
        try {
            $currentUser = $request->user();
            $user = User::findOrFail($id);

            if (!$currentUser) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            if ($currentUser->user_type !== 'super_admin' && $user->enterprise_id !== $currentUser->enterprise_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $validated = $request->validate([
                'role_name' => 'nullable|string|exists:roles,name',
                'role_id' => 'nullable|integer|exists:roles,id',
                'roles' => 'nullable|array',
                'roles.*' => 'integer|exists:roles,id',
            ]);

            $roleNames = $this->resolveRoleNamesFromPayload($validated);
            if ($roleNames === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun rôle valide fourni.',
                ], 422);
            }

            $this->ensureRolesCanBeAssigned($currentUser, $user, $roleNames);
            $this->attachRolesToUser($user, $roleNames, 'roles_attached');

            return response()->json([
                'success' => true,
                'data' => new UserResource($user->load(['enterprise', 'site', 'permissions'])),
                'message' => 'Role assigned successfully',
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            return response()->json([
                'success' => false,
                'message' => 'Error assigning role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeRole(Request $request, $id, string $roleName)
    {
        try {
            $currentUser = $request->user();
            $user = User::findOrFail($id);

            if (!$currentUser) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            if ($currentUser->user_type !== 'super_admin' && $user->enterprise_id !== $currentUser->enterprise_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedRoleName = $request->validate([
                'role_name' => 'sometimes|string|exists:roles,name',
            ]);

            $resolvedRoleName = $validatedRoleName['role_name'] ?? $roleName;
            $role = SpatieRole::query()->where('name', $resolvedRoleName)->firstOrFail();

            if (!$user->hasRole($role->name)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce rôle n\'est pas assigné à cet utilisateur.',
                ], 422);
            }

            if (
                $this->isRestrictedSiteManager($currentUser)
                && $this->isForbiddenForRestrictedSiteManager($role->name)
            ) {
                return response()->json([
                    'success' => false,
                    'message' => "Le rôle '{$role->name}' ne peut pas être retiré par un responsable de site.",
                ], 422);
            }

            $user->removeRole($role->name);
            $user->syncPermissions([]);
            $this->refreshAuthorizationState($user, 'role_removed');

            return response()->json([
                'success' => true,
                'data' => new UserResource($user->load(['enterprise', 'site', 'permissions'])),
                'message' => 'Role removed successfully',
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            return response()->json([
                'success' => false,
                'message' => 'Error removing role',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assigner des permissions personnalisées à un collaborateur.
     */
    public function assignCustomPermissions(Request $request, $id, \App\Services\PermissionScopeService $permissionScopeService)
    {
        try {
            $currentUser = $request->user();
            $user = User::findOrFail($id);

            if (!$currentUser) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            if ($currentUser->user_type !== 'super_admin' && $user->enterprise_id !== $currentUser->enterprise_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($user->hasRole('admin_entreprise') || $user->user_type === 'super_admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Les permissions directes ne sont pas autorisées pour cet utilisateur.'
                ], 422);
            }

            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $requestedPermissions = $validated['permissions'];

            $toAdd = [];
            foreach ($requestedPermissions as $name) {
                if (preg_match('/^(.*)\.(create|update|delete|manage)$/', $name, $matches)) {
                    $toAdd[] = $matches[1] . '.read';
                }
            }
            if (!empty($toAdd)) {
                $readPerms = \Spatie\Permission\Models\Permission::query()
                    ->whereIn('name', array_unique($toAdd))
                    ->pluck('name')
                    ->all();
                $requestedPermissions = array_unique(array_merge($requestedPermissions, $readPerms));
            }

            if ($currentUser->isSuperAdmin()) {
                $validPermissions = $requestedPermissions;
            } else {
                $assignablePermissions = $permissionScopeService->getAssignablePermissionsForAdmin($currentUser)
                    ->pluck('name')
                    ->map(fn($name) => mb_strtolower(trim($name)))
                    ->flip();

                $validPermissions = collect($requestedPermissions)
                    ->map(fn($name) => mb_strtolower(trim($name)))
                    ->filter(fn($name) => isset($assignablePermissions[$name]))
                    ->values()
                    ->all();

                $invalid = array_diff(
                    array_map(fn($n) => mb_strtolower(trim($n)), $requestedPermissions),
                    $validPermissions
                );

                if (!empty($invalid)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permissions non éligibles pour les normes actives du site: ' . implode(', ', $invalid),
                    ], 422);
                }
            }

            $user->syncPermissions($validPermissions);
            $this->refreshAuthorizationState($user, 'direct_permissions_assigned');

            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\UserResource($user->load(['enterprise', 'site', 'permissions', 'roles'])),
                'message' => 'Permissions assignées avec succès',
            ]);
        } catch (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }
            return response()->json([
                'success' => false,
                'message' => 'Error assigning custom permissions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retirer toutes les permissions d'un collaborateur
     */
    public function revokeAllPermissions($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        // Vérifier que c'est bien un collaborateur
        if ($user->user_type !== 'company') {
            return response()->json([
                'message' => 'Seuls les collaborateurs ont des permissions à révoquer'
            ], 422);
        }

        // Vérifier même entreprise
        /** @var User|null $currentUser */
        $currentUser = request()->user();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        if (
            $currentUser->user_type !== 'super_admin' &&
            $user->enterprise_id !== $currentUser->enterprise_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($user->hasRole('admin_entreprise')) {
            return response()->json([
                'message' => 'Les permissions directes ne sont pas autorisées pour admin_entreprise.'
            ], 422);
        }

        $user->syncPermissions([]);
        $this->refreshAuthorizationState($user, 'direct_permissions_revoked');

        return response()->json([
            'message' => 'Toutes les permissions ont été révoquées',
        ]);
    }

    private function refreshAuthorizationState(User $user, string $reason): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->tokens()->delete();

        \App\Models\SecurityAuditLog::logEvent(
            eventType: 'authorization',
            action: 'access_state_refreshed',
            resourceType: User::class,
            resourceId: (int) $user->id,
            metadata: [
                'reason' => $reason,
                'target_user_id' => (int) $user->id,
                'performed_by' => (int) (request()->user()?->id ?? 0),
            ],
            riskLevel: 'medium'
        );
    }

    /**
     * Check if a role is eligible for a site based on active subscription domains.
     */
    private function isRoleEligibleForSite(string $roleName, int $siteId, ?User $actor = null): bool
    {
        $roleCatalog = config('role_catalog.roles', []);
        $contextEnterpriseId = $this->resolveRoleContextEnterpriseId($actor, $siteId);
        $role = SpatieRole::query()->where('name', $roleName)->first();

        if ($this->isLegacyCustomRoleWithoutEnterpriseScope($roleName, $role)) {
            return false;
        }

        if ($this->isEnterpriseCustomRole($roleName, $contextEnterpriseId, $role)) {
            return true;
        }

        $definition = $roleCatalog[$roleName] ?? null;
        if (!$definition) {
            return false;
        }

        if ($actor?->isSuperAdmin()) {
            return true;
        }

        $requiredDomains = $definition['requires_domains'] ?? [];
        if (empty($requiredDomains)) {
            return true;
        }

        if ($siteId <= 0) {
            return false;
        }

        $activeDomains = $this->activeDomainsForSite($siteId);
        return !empty(array_intersect($requiredDomains, $activeDomains));
    }

    /**
     * Return active norm domains for a site.
     *
     * @return array<int, string>
     */
    private function activeDomainsForSite(int $siteId): array
    {
        if ($siteId <= 0) {
            return [];
        }

        $subscriptions = \App\Models\EnterpriseSubscription::query()
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->whereIn('status', ['active', 'trial'])
            ->where(function ($query) {
                $query->where(function ($trial) {
                    $trial->where('is_trial', true)
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', now());
                })->orWhere(function ($paid) {
                    $paid->where('is_trial', false)
                        ->whereNotNull('expiration_date')
                        ->where('expiration_date', '>', now());
                });
            })
            ->with('offer.norms:id,domain')
            ->get();

        return $subscriptions
            ->flatMap(fn($subscription) => $subscription->offer?->norms?->pluck('domain') ?? collect())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function requiresManualCollaboratorApproval(User $actor, ?string $targetUserType): bool
    {
        if ($actor->isSuperAdmin()) {
            return false;
        }

        if ($targetUserType !== User::TYPE_COMPANY) {
            return false;
        }

        return !$actor->isEnterpriseAdmin();
    }

    private function canApproveCollaborators(?User $actor): bool
    {
        if (!$actor) {
            return false;
        }

        return $actor->isCompanyUser()
            && $actor->isEnterpriseAdmin()
            && (int) ($actor->enterprise_id ?? 0) > 0;
    }

    /**
     * @return array{activation_sent: bool, verification_sent: bool, admin_notified_email_failure: bool}
     */
    private function dispatchCollaboratorActivationInvitations(User $collaborator): array
    {
        $activationSent = false;
        $verificationSent = false;
        $adminNotifiedEmailFailure = false;

        try {
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $collaborator->email],
                [
                    'email' => $collaborator->email,
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/')
                . '/auth/reset-password?token=' . urlencode($token)
                . '&email=' . urlencode($collaborator->email);

            event(new PasswordResetRequested($collaborator, $token, $resetUrl));
            $activationSent = true;

            if ($collaborator->collaborator_approval_status === 'approved') {
                $collaborator->forceFill([
                    'collaborator_approval_status' => 'activation_sent',
                ])->save();
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send collaborator activation email', [
                'user_id' => $collaborator->id,
                'email' => $collaborator->email,
                'error' => $e->getMessage(),
            ]);
            $adminNotifiedEmailFailure = $this->notifyEnterpriseAdminsOfCollaboratorEmailFailure(
                $collaborator,
                'activation',
                $e
            );
        }

        if (!$collaborator->hasVerifiedEmail()) {
            try {
                $collaborator->notify(new VerifyEmailNotification());
                $verificationSent = true;
            } catch (\Throwable $e) {
                Log::error('Failed to send collaborator verification email', [
                    'user_id' => $collaborator->id,
                    'email' => $collaborator->email,
                    'error' => $e->getMessage(),
                ]);
                $adminNotifiedEmailFailure = $this->notifyEnterpriseAdminsOfCollaboratorEmailFailure(
                    $collaborator,
                    'verification',
                    $e
                ) || $adminNotifiedEmailFailure;
            }
        } else {
            $verificationSent = true;
        }

        return [
            'activation_sent' => $activationSent,
            'verification_sent' => $verificationSent,
            'admin_notified_email_failure' => $adminNotifiedEmailFailure,
        ];
    }

    private function notifyEnterpriseAdminsOfCollaboratorEmailFailure(User $collaborator, string $kind, \Throwable $error): bool
    {
        $enterpriseId = (int) ($collaborator->enterprise_id ?? 0);
        if ($enterpriseId <= 0) {
            return false;
        }

        $admins = User::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('user_type', User::TYPE_COMPANY)
            ->where('is_active', true)
            ->whereHas('roles', fn($query) => $query->where('name', 'admin_entreprise'))
            ->get(['id']);

        if ($admins->isEmpty()) {
            return false;
        }

        $payload = [
            'type' => 'collaborator_email_delivery_failed',
            'title' => 'Echec d\'envoi email collaborateur',
            'message' => "L'email {$kind} n'a pas pu etre envoye au collaborateur {$collaborator->name} ({$collaborator->email}).",
            'action_url' => '/company/collaborators',
            'collaborator_id' => $collaborator->id,
            'collaborator_email' => $collaborator->email,
            'mail_kind' => $kind,
            'error' => $error->getMessage(),
        ];

        foreach ($admins as $admin) {
            UserNotification::create([
                'user_id' => $admin->id,
                'type' => 'collaborator_email_delivery_failed',
                'data' => $payload,
            ]);
        }

        return true;
    }

    private function isRestrictedSiteManager(?User $actor): bool
    {
        if (!$actor) {
            return false;
        }

        return $actor->user_type === 'company'
            && $actor->hasRole('site_manager')
            && !$actor->hasRole('admin_entreprise');
    }

    private function isForbiddenForRestrictedSiteManager(string $roleName): bool
    {
        return in_array($roleName, ['admin_entreprise', 'site_manager'], true)
            || (int) (SpatieRole::query()
                ->where('name', $roleName)
                ->value('enterprise_id') ?? 0) > 0;
    }

    private function isEnterpriseCustomRoleModel(SpatieRole $role, int $enterpriseId = 0): bool
    {
        return $this->isEnterpriseCustomRole((string) $role->name, $enterpriseId, $role);
    }

    private function isEnterpriseCustomRole(string $roleName, int $enterpriseId = 0, ?SpatieRole $role = null): bool
    {
        if ($enterpriseId <= 0) {
            return false;
        }

        if ($this->roleScopeService->isRoleStrictlyScopedToEnterprise($role, $enterpriseId)) {
            return true;
        }

        if ($role !== null) {
            return false;
        }

        $roleModel = SpatieRole::query()->where('name', $roleName)->first();
        if (!$roleModel) {
            return false;
        }

        return $this->roleScopeService->isRoleStrictlyScopedToEnterprise($roleModel, $enterpriseId);
    }

    private function isLegacyCustomRoleWithoutEnterpriseScope(string $roleName, ?SpatieRole $role = null): bool
    {
        return $this->roleScopeService->isLegacyCustomRoleWithoutEnterpriseScope($role, $roleName);
    }

    private function resolveRoleContextEnterpriseId(?User $actor, int $siteId = 0): int
    {
        if ($actor?->isSuperAdmin() && $siteId > 0) {
            return (int) (\App\Models\Site::query()
                ->whereKey($siteId)
                ->value('enterprise_id') ?? 0);
        }

        return (int) ($actor?->enterprise_id ?? 0);
    }

    /**
    /**
     * GET /api/v1/users/sidebar
     * Get dynamic sidebar for authenticated user
     * Cascade logic:
     * - Filter by active norms subscriptions
     * - Check user permissions
     * - Optionally check task assignments
     * 
     * Returns masked sidebar structure based on access level
     */
    public function getSidebar(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Get active subscriptions (norms)
        $activeSubscriptions = $user->site?->enterprise
            ?->subscriptions()
            ->with('offer.norms')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now());
            })
            ->get() ?? collect();

        $activeNormIds = $activeSubscriptions
            ->flatMap(function ($subscription) {
                return $subscription->offer?->norms?->pluck('id') ?? collect();
            })
            ->unique()
            ->values()
            ->toArray();

        // Get active permissions
        $activePermissions = $user->getPermissionsViaRoles()
            ->pluck('name')
            ->concat($user->permissions()->pluck('name'))
            ->unique()
            ->toArray();

        // Base sidebar menu structure
        $sidebar = [
            [
                'key' => 'dashboard',
                'label' => 'Accueil',
                'icon' => 'mdi-home-outline',
                'to' => '/company/dashboard',
                'permissions' => [],
                'visible' => true,
            ],
            [
                'key' => 'my-tasks',
                'label' => 'Mes Tâches',
                'icon' => 'mdi-checkbox-marked-outline',
                'to' => '/company/my-tasks',
                'permissions' => [],
                'visible' => true,
            ],
            [
                'key' => 'verification',
                'label' => 'Vérification',
                'icon' => 'mdi-check-circle-outline',
                'to' => '/company/documents/verification',
                'permissions' => ['verify_documents'],
                'visible' => in_array('verify_documents', $activePermissions),
            ],
            [
                'key' => 'approbation',
                'label' => 'Approbation',
                'icon' => 'mdi-check-decagram-outline',
                'to' => '/company/documents/approbation',
                'permissions' => ['approve_documents'],
                'visible' => in_array('approve_documents', $activePermissions),
            ],
            [
                'key' => 'plan-sm',
                'label' => 'Plan du SM',
                'icon' => 'mdi-calendar-range',
                'to' => '/company/plan-sm',
                'permissions' => ['view_all_tasks'],
                'visible' => in_array('view_all_tasks', $activePermissions),
            ],
            [
                'key' => 'processes',
                'label' => 'Processus',
                'icon' => 'mdi-call-split',
                'to' => '/company/processes',
                'permissions' => ['processes.read'],
                'visible' => in_array('processes.read', $activePermissions),
            ],
            [
                'key' => 'documents',
                'label' => 'Documents',
                'icon' => 'mdi-file-document-outline',
                'to' => '/company/documents',
                'permissions' => ['documents.read'],
                'visible' => in_array('documents.read', $activePermissions),
            ],
            [
                'key' => 'audits',
                'label' => 'Audits',
                'icon' => 'mdi-check-circle-outline',
                'to' => '/company/audits',
                'permissions' => ['audits.read'],
                'visible' => in_array('audits.read', $activePermissions),
            ],
            [
                'key' => 'actions',
                'label' => 'Actions',
                'icon' => 'mdi-lightning-bolt-outline',
                'to' => '/company/actions',
                'permissions' => ['actions.read'],
                'visible' => in_array('actions.read', $activePermissions),
            ],
            [
                'key' => 'formations',
                'label' => 'Formations',
                'icon' => 'mdi-school-outline',
                'to' => '/company/formations',
                'permissions' => ['formations.read'],
                'visible' => in_array('formations.read', $activePermissions),
            ],
            [
                'key' => 'communications',
                'label' => 'Communications',
                'icon' => 'mdi-bullhorn-outline',
                'to' => '/company/communications',
                'permissions' => ['communications.read'],
                'visible' => in_array('communications.read', $activePermissions),
            ],
            [
                'key' => 'risks',
                'label' => 'Risques',
                'icon' => 'mdi-alert-outline',
                'to' => '/company/risks',
                'permissions' => ['risks.read'],
                'visible' => in_array('risks.read', $activePermissions),
            ],
            [
                'key' => 'objectives',
                'label' => 'Objectifs',
                'icon' => 'mdi-target-outline',
                'to' => '/company/objectives',
                'permissions' => ['objectives.read'],
                'visible' => in_array('objectives.read', $activePermissions),
            ],
            [
                'key' => 'stakeholders',
                'label' => 'Parties Prenantes',
                'icon' => 'mdi-account-multiple-outline',
                'to' => '/company/stakeholders',
                'permissions' => ['stakeholders.read'],
                'visible' => in_array('stakeholders.read', $activePermissions),
            ],
            [
                'key' => 'admin',
                'label' => 'Administration',
                'icon' => 'mdi-cog-outline',
                'to' => '/company/admin',
                'permissions' => ['admin.read', 'users.read'],
                'visible' => in_array('admin.read', $activePermissions) || in_array('users.read', $activePermissions),
            ],
        ];

        // Filter sidebar by permissions
        $visibleSidebar = array_filter($sidebar, fn($item) => $item['visible']);

        return response()->json([
            'success' => true,
            'data' => [
                'sidebar' => array_values($visibleSidebar),
                'active_norms' => $activeNormIds,
                'active_permissions' => $activePermissions,
                'user_id' => $user->id,
                'enterprise_id' => $user->enterprise_id,
                'site_id' => $user->site_id,
            ],
        ]);
    }
}
