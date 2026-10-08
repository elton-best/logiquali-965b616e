<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\EnterpriseDocument;
use App\Models\Site;
use App\Models\User;
use App\Models\MfaChallenge;
use App\Events\User\UserCreated;
use App\Notifications\Enterprise\EnterpriseRegistrationConfirmation;
use App\Notifications\Enterprise\NewEnterpriseNotification;
use App\Notifications\User\WelcomeNotification;
use App\Notifications\VerifyEmailNotification;
use App\Notifications\WelcomeClientNotification;
use App\Services\Security\MfaService;
use App\Services\Settings\SuperAdminSettingsService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    private const MAX_SESSION_HOURS = 8;

    public function login(Request $request)
    {
        // Validation des données
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rechercher l'utilisateur par email
        $user = User::where('email', $request->email)->first();

        // Vérifier les credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Record failed attempt for IP blocking
            \App\Http\Middleware\IpBlockingMiddleware::recordFailedAttempt($request->ip());

            // Log failed login attempt
            \App\Models\SecurityAuditLog::logEvent(
                eventType: 'authentication',
                action: 'login_failed',
                metadata: [
                    'email' => $request->email,
                    'reason' => 'invalid_credentials'
                ],
                riskLevel: 'medium'
            );

            throw ValidationException::withMessages([
                'login' => 'Identifiants incorrects',
            ]);
        }

        if ($user->isCompanyUser() && $user->requiresCollaboratorApproval()) {
            return $this->pendingCollaboratorApprovalResponse();
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Compte désactivé',
            ], 403);
        }

        // Vérifier si l'email est vérifié (sauf pour SuperAdmin)
        if ($user->user_type !== 'super_admin' && !$user->hasVerifiedEmail()) {
            // Envoyer l'email de vérification
            try {
                $user->notify(new VerifyEmailNotification());
            } catch (\Exception $e) {
                Log::error('Erreur envoi email de vérification: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Votre adresse email n\'a pas été vérifiée. Un email de vérification vous a été envoyé.',
                'email_verified' => false,
                'action_required' => 'verify_email',
            ], 403);
        }

        // Vérifications spécifiques pour les utilisateurs d'entreprise
        if ($user->user_type === 'company') {
            $enterprise = $user->enterprise;
            if ($enterprise) {
                $hasActiveSiteSubscription = (bool) $user->site?->hasActiveSubscription();

                // Auto-heal legacy: si le domaine est déjà renseigné, synchroniser le flag.
                if (!$enterprise->domaine_activite_set && filled($enterprise->field ?? null)) {
                    $enterprise->forceFill(['domaine_activite_set' => true])->save();
                    $enterprise->refresh();
                }

                // Vérifier le statut de l'entreprise
                if ($enterprise->isPending()) {
                    return response()->json([
                        'message' => 'Votre demande d\'inscription est en cours de traitement.',
                        'status' => 'pending',
                    ], 403);
                }

                if ($enterprise->isRejected()) {
                    return response()->json([
                        'message' => 'Votre demande d\'inscription a été refusée.',
                        'status' => 'rejected',
                        'reason' => $enterprise->rejection_reason,
                    ], 403);
                }

                if ($enterprise->isSuspended()) {
                    return response()->json([
                        'message' => 'Votre entreprise a été suspendue.',
                        'status' => 'suspended',
                        'reason' => $enterprise->suspension_reason,
                    ], 403);
                }
            } else {
                return response()->json([
                    'message' => 'Aucune entreprise associée à ce compte.',
                    'status' => 'no_enterprise',
                ], 403);
            }
        }

        $context = $this->buildPostLoginContext($user);
        $challenge = app(MfaService::class)->issueChallenge($user, 'login', $request, $context);

        info("User {$user->email} requires MFA challenge for login", [
            'user_id' => $user->id,
            'challenge_id' => $challenge->id,
            'challenge_expires_at' => $challenge->expires_at?->toISOString(),
        ]);
        $response = [
            'mfa_required' => true,
            'mfa_token' => $challenge->token,
            'mfa_expires_at' => $challenge->expires_at?->toISOString(),
            'message' => 'Un code de verification a ete envoye par email.',
        ];
        if ($this->shouldExposeMfaOtpForE2E()) {
            $response['mfa_code'] = (string) ($challenge->getAttribute('plain_code') ?? '');
        }

        return response()->json($response);
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->buildAuthUserPayload($user),
            'role' => $user->user_type,
            'onboarding' => $this->buildOnboardingState($user),
        ]);
    }

    /**
     * Rafraîchir le token d'authentification
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $absoluteExpiry = $this->absoluteSessionExpiry($user);

        if ($absoluteExpiry->isPast()) {
            $user->currentAccessToken()?->delete();

            return $this->clearAuthCookie(response()->json([
                'success' => false,
                'message' => 'Session expiree (maximum 8 heures). Veuillez vous reconnecter.',
            ], 401));
        }

        // Supprimer l'ancien token
        $user->currentAccessToken()?->delete();

        // Créer un nouveau token
        $token = $user->createToken(
            'auth-token',
            expiresAt: $absoluteExpiry
        )->plainTextToken;

        // Logger l'activité
        // TODO: activity()->causedBy($request->user())->log('Token rafraîchi');

        return $this->withAuthCookie(
            response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $absoluteExpiry->toISOString(),
            ]),
            $request,
            $token,
            $absoluteExpiry
        );
    }

    private function buildOnboardingState(User $user): array
    {
        return [
            'password_change_required' => (bool) $user->must_change_password,
            'signature_uploaded' => !empty($user->signature_path),
            'signature_uploaded_at' => $user->signature_uploaded_at?->toISOString(),
        ];
    }

    /**
     * Construit le payload utilisateur d'auth avec un contrat permissions unifié.
     */
    private function buildAuthUserPayload(User $user): User
    {
        $user->loadMissing([
            'enterprise.sites.subscriptions',
            'permissions',
            'roles.permissions',
            'site',
        ]);

        $directPermissionNames = $user->relationLoaded('permissions')
            ? $user->permissions->pluck('name')->filter()->unique()->values()
            : $user->permissions()->pluck('name')->filter()->unique()->values();
        $rolePermissionNames = $user->getRolePermissionNames()->values();
        $effectivePermissionNames = $user->getEffectivePermissionNames()->values();
        $activeScopedPermissionNames = $user->getActiveScopedPermissionNames()->values();
        $assignedActionsCount = $user->isCompanyUser() ? $user->getAssignedActionsCount() : 0;

        $user->setAttribute('role_names', $user->getRoleNames()->values()->all());
        $user->setAttribute('direct_permissions', $directPermissionNames->all());
        $user->setAttribute('role_permissions', $rolePermissionNames->all());
        $user->setAttribute('effective_permissions', $effectivePermissionNames->all());
        $user->setAttribute('active_scoped_permissions', $activeScopedPermissionNames->all());
        $user->setAttribute('direct_permissions_count', $directPermissionNames->count());
        $user->setAttribute('role_permissions_count', $rolePermissionNames->count());
        $user->setAttribute('effective_permissions_count', $effectivePermissionNames->count());
        $user->setAttribute('active_scoped_permissions_count', $activeScopedPermissionNames->count());
        $user->setAttribute('assigned_actions_count', $assignedActionsCount);
        $user->setAttribute('has_assigned_actions', $assignedActionsCount > 0);
        $user->setAttribute('authz_enforce_active_scope', PermissionHelper::isActiveScopeEnforcedForUser($user));

        return $user;
    }

    private function buildPostLoginContext(User $user): array
    {
        if ($user->user_type !== 'company') {
            return [];
        }

        $enterprise = $user->enterprise;
        if (!$enterprise) {
            return [];
        }

        $hasActiveSiteSubscription = (bool) $user->site?->hasActiveSubscription();

        if (!$enterprise->domaine_activite_set && !$hasActiveSiteSubscription) {
            return [
                'redirect_to' => '/company/settings?tab=entreprise',
                'requires_company_setup' => true,
            ];
        }

        if (!$enterprise->canAccessPlatform()) {
            return [
                'message' => 'Souscription requise pour acceder au dashboard.',
                'redirect_to' => 'subscription',
                'requires_subscription' => true,
                'trial_expired' => $enterprise->isTrialExpired(),
            ];
        }

        return [];
    }

    private function buildLoginResponse(User $user, array $context = []): array
    {
        $tokenExpiresAt = $this->newSessionExpiry();
        $token = $user->createToken('auth-token', expiresAt: $tokenExpiresAt);
        $token->accessToken?->forceFill(['mfa_verified_at' => now()])->save();
        $plainTextToken = $token->plainTextToken;

        $user->recordLogin();

        \App\Models\SecurityAuditLog::logEvent(
            eventType: 'authentication',
            action: 'login_success',
            metadata: [
                'user_type' => $user->user_type,
                'enterprise_id' => $user->enterprise_id,
                'site_id' => $user->site_id
            ],
            riskLevel: 'low'
        );

        return array_merge([
            'user' => $this->buildAuthUserPayload($user),
            'role' => $user->user_type,
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $tokenExpiresAt->toISOString(),
            'email_verified' => $user->hasVerifiedEmail(),
            'onboarding' => $this->buildOnboardingState($user),
        ], $context);
    }

    public function verifyMfa(Request $request)
    {
        $otpLength = (int) config('mfa.otp_length', 6);

        $validated = $request->validate([
            'token' => 'required|string',
            'code' => ['required', 'string', 'size:' . $otpLength, 'regex:/^[0-9]+$/'],
        ]);

        $challenge = MfaChallenge::where('token', $validated['token'])->first();
        if (!$challenge) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire.'],
            ]);
        }

        $service = app(MfaService::class);
        if (!$service->validateChallenge($challenge, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire.'],
            ]);
        }

        $user = $challenge->user;
        if (!$user) {
            throw ValidationException::withMessages([
                'code' => ['Code invalide ou expire.'],
            ]);
        }

        if ($challenge->purpose === 'step_up') {
            $currentUser = $request->user('sanctum') ?? \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
            if (!$currentUser || $currentUser->id !== $user->id) {
                return response()->json(['message' => 'Acces non autorise.'], 403);
            }

            $service->consumeChallenge($challenge);
            $token = $currentUser->currentAccessToken();
            if (!$token) {
                return response()->json(['message' => 'Session invalide. Veuillez vous reconnecter.'], 401);
            }
            $token->forceFill(['mfa_verified_at' => now()])->save();

            return response()->json(['success' => true]);
        }

        if ($user->isCompanyUser() && $user->requiresCollaboratorApproval()) {
            return $this->pendingCollaboratorApprovalResponse();
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Compte desactive'], 403);
        }

        if ($user->user_type !== 'super_admin' && !$user->hasVerifiedEmail()) {
            try {
                $user->notify(new VerifyEmailNotification());
            } catch (\Exception $e) {
                Log::error('Erreur envoi email de verification: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Votre adresse email n\'a pas ete verifiee. Un email de verification vous a ete envoye.',
                'email_verified' => false,
                'action_required' => 'verify_email',
            ], 403);
        }

        if ($user->user_type === 'company') {
            $enterprise = $user->enterprise;
            if (!$enterprise) {
                throw ValidationException::withMessages([
                    'login' => ['Aucune entreprise associee.'],
                ]);
            }

            if ($enterprise->isPending()) {
                return response()->json([
                    'message' => 'Votre demande d\'inscription est en cours de traitement.',
                    'status' => 'pending',
                ], 403);
            }

            if ($enterprise->isRejected()) {
                return response()->json([
                    'message' => 'Votre demande d\'inscription a ete refusee.',
                    'status' => 'rejected',
                    'reason' => $enterprise->rejection_reason,
                ], 403);
            }

            if ($enterprise->isSuspended()) {
                return response()->json([
                    'message' => 'Votre entreprise a ete suspendue.',
                    'status' => 'suspended',
                    'reason' => $enterprise->suspension_reason,
                ], 403);
            }
        }

        $service->consumeChallenge($challenge);
        $context = is_array($challenge->context ?? null) ? $challenge->context : [];

        $payload = $this->buildLoginResponse($user, $context);

        return $this->withAuthCookie(
            response()->json($payload),
            $request,
            (string) ($payload['token'] ?? ''),
            now()->addHours(self::MAX_SESSION_HOURS)
        );
    }

    public function resendMfa(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $challenge = MfaChallenge::where('token', $validated['token'])->first();
        if (!$challenge || $challenge->isConsumed() || $challenge->isExpired()) {
            return response()->json([
                'message' => 'Le code a expire. Veuillez relancer la connexion.',
                'error_code' => 'MFA_CHALLENGE_INVALID',
            ], 422);
        }

        $newChallenge = app(MfaService::class)->issueChallenge(
            $challenge->user,
            $challenge->purpose,
            $request,
            is_array($challenge->context ?? null) ? $challenge->context : []
        );

        $response = [
            'mfa_token' => $newChallenge->token,
            'mfa_expires_at' => $newChallenge->expires_at?->toISOString(),
            'message' => 'Un code de verification a ete renvoye par email.',
        ];
        if ($this->shouldExposeMfaOtpForE2E()) {
            $response['mfa_code'] = (string) ($newChallenge->getAttribute('plain_code') ?? '');
        }

        return response()->json($response);
    }

    public function requestStepUpMfa(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifie'], 401);
        }

        $challenge = app(MfaService::class)->issueChallenge($user, 'step_up', $request);

        return response()->json([
            'mfa_token' => $challenge->token,
            'mfa_expires_at' => $challenge->expires_at?->toISOString(),
        ]);
    }

    private function shouldExposeMfaOtpForE2E(): bool
    {
        if (!((bool) config('mfa.expose_otp_for_e2e', false))) {
            return false;
        }

        return app()->environment(['local', 'testing']);
    }

    private function pendingCollaboratorApprovalResponse()
    {
        return response()->json([
            'message' => "Votre compte collaborateur est en attente de validation par l'admin d'entreprise.",
            'status' => 'pending_admin_approval',
        ], 403);
    }

    /**
     * Inscription d'une entreprise
     * Crée l'entreprise, le siège social, l'admin et stocke les documents
     */
    public function registerEnterprise(Request $request)
    {
        // Validation des données avec logging détaillé
        try {
            $validated = $request->validate([
                'enterprise_name' => 'required|string|max:255',
                'sigle' => 'nullable|string|max:10',
                'email' => 'required|email|unique:users,email',
                'enterprise_email' => 'nullable|email',
                'registration_number' => 'required|string|unique:enterprises,registration_number',
                'address' => 'required|string',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'job_title' => 'required|string|max:255',
                'username' => 'nullable|string|max:255|unique:users,username',
                'password' => app(SuperAdminSettingsService::class)->passwordRules(),
                'phone' => 'nullable|string|max:20',
                'field' => 'required|string|max:255',
                'ifu' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                // Numéros de documents (optionnels)
                'rccm_number' => 'nullable|string|max:255',
                'ifu_number' => 'nullable|string|max:255',
                'id_type' => 'nullable|string|max:50',
                'id_number' => 'nullable|string|max:255',
                // Documents séparés avec leurs noms spécifiques
                'logo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'rccm_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'ifu_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'id_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation échec inscription', [
                'errors' => $e->errors(),
                'email' => $request->input('email'),
                'username' => $request->input('username'),
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'registration_number' => $request->input('registration_number'),
                'has_docs' => [
                    'logo' => $request->hasFile('logo'),
                    'rccm' => $request->hasFile('rccm_document'),
                    'ifu' => $request->hasFile('ifu_document'),
                    'id' => $request->hasFile('id_document'),
                ],
            ]);
            throw $e;
        }

        try {
            logger()->info('Début inscription entreprise', [
                'enterprise_name' => $validated['enterprise_name'],
                'has_logo' => $request->hasFile('logo'),
                'has_rccm' => $request->hasFile('rccm_document'),
                'has_ifu' => $request->hasFile('ifu_document'),
                'has_id' => $request->hasFile('id_document'),
            ]);

            DB::beginTransaction();

            $enterpriseEmail = $validated['enterprise_email'] ?? $validated['email'];
            if (Enterprise::query()->where('email', $enterpriseEmail)->exists()) {
                throw ValidationException::withMessages([
                    'enterprise_email' => 'Cette adresse email entreprise est déjà utilisée.',
                ]);
            }

            $sigle = strtoupper(trim((string) ($validated['sigle'] ?? '')));
            $sigle = $sigle !== '' ? $sigle : null;

            // 1. Créer l'entreprise (statut pending par défaut)
            $enterprise = Enterprise::create([
                'name' => $validated['enterprise_name'],
                'sigle' => $sigle,
                'email' => $enterpriseEmail,
                'registration_number' => $validated['registration_number'],
                'field' => $validated['field'] ?? null,
                'domaine_activite_set' => filled($validated['field'] ?? null),
                'address' => $validated['address'],
                'address_line_1' => $validated['address'],
                'city' => $validated['city'] ?? null,
                'country' => $validated['country'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'phone_primary' => $validated['phone'] ?? null,
                'email_general' => $enterpriseEmail,
                'rccm_number' => $validated['rccm_number'] ?? null,
                'ifu_number' => $validated['ifu_number'] ?? null,
                'status' => 'pending',
                'approval_status' => 'pending',
            ]);

            logger()->info('Entreprise créée', ['id' => $enterprise->id]);

            // 2. Créer le siège social
            $site = Site::create([
                'enterprise_id' => $enterprise->id,
                'name' => 'Siège Social',
                'location' => $validated['address'],
                'is_headquarter' => true,
                'is_active' => true,
            ]);

            logger()->info('Site créé', ['id' => $site->id]);

            // 3. Créer l'administrateur de l'entreprise
            $baseUsername = $validated['username']
                ?? strtolower($validated['first_name'] . '.' . $validated['last_name']);
            $baseUsername = preg_replace('/\s+/', '', $baseUsername);
            $baseUsername = preg_replace('/[^a-z0-9._-]/', '', $baseUsername);
            if (empty($baseUsername)) {
                $baseUsername = Str::before($validated['email'], '@');
            }

            $username = $baseUsername;
            $counter = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            $admin = User::create([
                'enterprise_id' => $enterprise->id,
                'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'username' => $username,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'],
                'job_title' => $validated['job_title'] ?? 'Admin entreprise',
                'user_type' => 'company',
                'is_active' => true,
                'site_id' => $site->id,
            ]);

            // Assigner rôle admin_entreprise
            $admin->assignRole('admin_entreprise');

            logger()->info('Admin créé', ['id' => $admin->id]);

            // 4. Mettre à jour created_by dans enterprise
            $enterprise->update([
                'created_by' => $admin->id,
                'owner_user_id' => $admin->id,
            ]);

            // 4. Traiter les documents joints avec leurs numéros
            $documentsToProcess = [];

            // Logo (optionnel)
            if ($request->hasFile('logo')) {
                $documentsToProcess[] = [
                    'file' => $request->file('logo'),
                    'type' => 'other',
                    'name_prefix' => 'Logo',
                    'number' => null
                ];
            }

            // RCCM (requis)
            if ($request->hasFile('rccm_document')) {
                $documentsToProcess[] = [
                    'file' => $request->file('rccm_document'),
                    'type' => 'compliance',
                    'name_prefix' => 'Attestation RCCM',
                    'number' => $validated['rccm_number'] ?? null
                ];
            }

            // IFU (requis)
            if ($request->hasFile('ifu_document')) {
                $documentsToProcess[] = [
                    'file' => $request->file('ifu_document'),
                    'type' => 'compliance',
                    'name_prefix' => 'Attestation IFU',
                    'number' => $validated['ifu_number'] ?? null
                ];
            }

            // Pièce d'identité (requis)
            if ($request->hasFile('id_document')) {
                $documentsToProcess[] = [
                    'file' => $request->file('id_document'),
                    'type' => 'legal',
                    'name_prefix' => 'Pièce d\'identité',
                    'number' => $validated['id_number'] ?? null
                ];
            }

            // Traiter tous les documents
            foreach ($documentsToProcess as $docData) {
                try {
                    $file = $docData['file'];
                    $path = $file->store("enterprises/{$enterprise->id}/documents", 'public');

                    EnterpriseDocument::create([
                        'enterprise_id' => $enterprise->id,
                        'document_type' => $docData['type'],
                        'document_number' => $docData['number'],
                        'name' => $docData['name_prefix'] . ' - ' . $file->getClientOriginalName(),
                        'stored_path' => $path,
                        'uploaded_by' => $admin->id,
                    ]);

                    // Le logo d'inscription doit alimenter le branding entreprise.
                    if ($docData['type'] === 'other' && str_starts_with($docData['name_prefix'], 'Logo')) {
                        $enterprise->update(['logo_path' => $path]);
                    }

                    logger()->info('Document uploadé', [
                        'type' => $docData['type'],
                        'number' => $docData['number'],
                        'path' => $path
                    ]);
                } catch (\Exception $e) {
                    logger()->warning('Erreur upload document', [
                        'type' => $docData['type'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 5. Valider la transaction
            DB::commit();
            logger()->info('Transaction committée avec succès');

            // 6. Envoyer les notifications (non bloquant)
            try {
                // Notification à l'admin de l'entreprise
                $admin->notify(new EnterpriseRegistrationConfirmation($enterprise));

                // Envoyer email de vérification
                $admin->notify(new VerifyEmailNotification());

                // Notification aux super admins
                $superAdmins = User::where('user_type', 'super_admin')->get();
                foreach ($superAdmins as $superAdmin) {
                    $superAdmin->notify(new NewEnterpriseNotification($enterprise, $admin));
                }
            } catch (\Exception $e) {
                Log::warning('Erreur notification', ['error' => $e->getMessage()]);
            }

            // 7. Logger l'activité
            // TODO: Implémenter le système d'activité
            // activity()->performedOn($enterprise)->causedBy($admin)->log('Nouvelle entreprise inscrite');

            return response()->json([
                'message' => 'Votre demande d\'inscription a été soumise avec succès. Vous recevrez un email une fois validée.',
                'enterprise_id' => $enterprise->id,
                'admin_email' => $admin->email,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            logger()->error('Erreur critique inscription entreprise', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'message' => 'Erreur technique lors de l\'inscription',
                'details' => config('app.debug') ? $e->getMessage() : 'Veuillez contacter le support',
            ], 500);
        }
    }

    /**
     * Inscription d'un client (utilisateur simple sans entreprise)
     */
    public function registerClient(Request $request)
    {
        // Validation des données
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => app(SuperAdminSettingsService::class)->passwordRules(),
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Créer l'utilisateur client
            $firstName = trim((string) ($validated['first_name'] ?? ''));
            $lastName = trim((string) ($validated['last_name'] ?? ''));
            $displayName = trim($firstName . ' ' . $lastName) ?: $validated['username'];

            $user = User::create([
                'name' => $displayName,
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'user_type' => 'clientb',
                'enterprise_id' => null,
                'is_active' => true,
            ]);

            DB::commit();

            // Envoyer l'email de bienvenue (non bloquant)
            // TODO: Créer WelcomeClientNotification
            try {
                $user->notify(new WelcomeClientNotification);
            } catch (\Exception $e) {
                logger()->warning('Erreur notification bienvenue', ['error' => $e->getMessage()]);
            }

            // Envoyer l'email de vérification (non bloquant)
            try {
                $user->notify(new VerifyEmailNotification());
            } catch (\Exception $e) {
                Log::warning('Erreur envoi vérification', ['error' => $e->getMessage()]);
            }

            // Logger l'activité
            // TODO: activity()->causedBy($user)->performedOn($user)->log('Nouveau client inscrit');
            // Déclencher l'event pour envoyer l'email de bienvenue
            event(new UserCreated(
                $user,
                'clientb',
                'set_password_link',
                null,
                url('/set-password?token=' . Str::random(64))
            ));

            // Créer le token d'authentification
            $tokenExpiresAt = $this->newSessionExpiry();
            $token = $user->createToken('auth-token', expiresAt: $tokenExpiresAt)->plainTextToken;
            $user->recordLogin();

            return $this->withAuthCookie(response()->json([
                'message' => 'Inscription réussie ! Veuillez vérifier votre email.',
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => $tokenExpiresAt->toISOString(),
                'email_verified' => false,
                'verification_sent' => true,
            ], 201), $request, $token, $tokenExpiresAt);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur inscription client', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erreur technique lors de l\'inscription',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Déconnexion de l'utilisateur
     * Supprime le token actuel
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        // TODO: activity()->causedBy($request->user())->log('Déconnexion');

        return $this->clearAuthCookie(response()->json(['message' => 'Déconnexion réussie']));
    }

    private function newSessionExpiry()
    {
        return now()->addHours(self::MAX_SESSION_HOURS);
    }

    private function absoluteSessionExpiry(User $user)
    {
        $base = $user->last_login_at ?: now();
        return $base->copy()->addHours(self::MAX_SESSION_HOURS);
    }

    private function withAuthCookie($response, Request $request, string $token, $expiresAt)
    {
        if ($token === '') {
            return $response;
        }

        $minutes = max(1, now()->diffInMinutes($expiresAt, false));
        $cookieName = (string) env('AUTH_TOKEN_COOKIE_NAME', 'auth_token');
        $sameSite = (string) env('AUTH_TOKEN_COOKIE_SAME_SITE', 'lax');
        $domain = env('AUTH_TOKEN_COOKIE_DOMAIN', null);
        $secure = filter_var(env('AUTH_TOKEN_COOKIE_SECURE', $request->isSecure()), FILTER_VALIDATE_BOOL);

        return $response->withCookie(new Cookie(
            $cookieName,
            $token,
            now()->addMinutes($minutes),
            '/',
            $domain ?: null,
            $secure,
            true,
            false,
            $sameSite
        ));
    }

    private function clearAuthCookie($response)
    {
        $cookieName = (string) env('AUTH_TOKEN_COOKIE_NAME', 'auth_token');
        $domain = env('AUTH_TOKEN_COOKIE_DOMAIN', null);

        return $response->withCookie(cookie()->forget($cookieName, '/', $domain ?: null));
    }

    /**
     * Envoi du lien de réinitialisation de mot de passe
     */
    public function forgotPassword(Request $request)
    {
        // Validation
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user) {
            // Générer un token de réinitialisation
            $token = Str::random(64);

            // Stocker dans password_resets
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            // Déclencher l'event pour envoyer l'email
            $resetUrl = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/')
                . '/auth/reset-password?token=' . urlencode($token)
                . '&email=' . urlencode($user->email);

            event(new \App\Events\User\PasswordResetRequested($user, $token, $resetUrl));
        } else {
            // Uniformiser le temps de réponse pour limiter l'énumération des comptes.
            Hash::make(Str::random(40));
        }

        return response()->json([
            'message' => 'Un email de réinitialisation a été envoyé à votre adresse.'
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(Request $request)
    {
        // Validation
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => app(SuperAdminSettingsService::class)->passwordRules(),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                 if (
                    $user instanceof User
                    && $user->isCompanyUser()
                    && in_array((string) $user->collaborator_approval_status, ['approved', 'activation_sent'], true)
                ) {
                    $user->forceFill([
                        'collaborator_approval_status' => 'activated',
                    ])->save();

                    \App\Models\SecurityAuditLog::logEvent(
                        eventType: 'user_management',
                        action: 'collaborator_account_activated',
                        resourceType: User::class,
                        resourceId: $user->id,
                        metadata: [
                            'target_user_id' => $user->id,
                            'site_id' => $user->site_id,
                            'enterprise_id' => $user->enterprise_id,
                        ],
                        riskLevel: 'high'
                    );
                }

                event(new PasswordReset($user));

                // TODO: activity()->causedBy($user)->log('Mot de passe réinitialisé');
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        throw ValidationException::withMessages(['email' => [__($status)]]);
    }


    /**
     * Vérifier l'email via le lien reçu
     */
    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $redirectUrl = $this->resolveEmailVerificationRedirectUrl($request, $frontendUrl);

        // Vérifier que le hash correspond
        if (
            !hash_equals(
                (string) $request->route('hash'),
                sha1($user->getEmailForVerification())
            )
        ) {
            return redirect($frontendUrl . '/auth/email-verification-failed?error=invalid_link');
        }

        // Vérifier la signature (expiration)
        if (!$request->hasValidSignature()) {
            return redirect($frontendUrl . '/auth/email-verification-failed?error=expired_link');
        }

        // Si déjà vérifié
        if ($user->hasVerifiedEmail()) {
            return redirect($redirectUrl . '?status=already_verified');
        }

        // Marquer comme vérifié
        $user->markEmailAsVerified();

        // Logger l'activité
        // TODO: activity()->causedBy($user)->performedOn($user)->log('Email vérifié');

        return redirect($redirectUrl . '?status=success');
    }

    private function resolveEmailVerificationRedirectUrl(Request $request, string $frontendUrl): string
    {
        $defaultPath = '/auth/email-verified';
        $defaultUrl = rtrim($frontendUrl, '/') . $defaultPath;

        $requestedRedirect = (string) $request->query('redirect', '');
        if ($requestedRedirect === '') {
            return $defaultUrl;
        }

        // Relative redirects are allowed only for email verification result pages.
        if (str_starts_with($requestedRedirect, '/')) {
            if (in_array($requestedRedirect, ['/auth/email-verified', '/email-verified'], true)) {
                return rtrim($frontendUrl, '/') . $requestedRedirect;
            }

            return $defaultUrl;
        }

        $targetParts = parse_url($requestedRedirect);
        $frontendParts = parse_url($frontendUrl);
        if (!is_array($targetParts) || !is_array($frontendParts)) {
            return $defaultUrl;
        }

        $sameHost = strtolower((string) ($targetParts['host'] ?? '')) === strtolower((string) ($frontendParts['host'] ?? ''));
        $sameScheme = strtolower((string) ($targetParts['scheme'] ?? '')) === strtolower((string) ($frontendParts['scheme'] ?? ''));
        if (!$sameHost || !$sameScheme) {
            return $defaultUrl;
        }

        $path = (string) ($targetParts['path'] ?? '');
        if (!in_array($path, ['/auth/email-verified', '/email-verified'], true)) {
            return $defaultUrl;
        }

        return $requestedRedirect;
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerificationEmail(Request $request)
    {
        $user = $request->user();

        // Vérifier si l'email est déjà vérifié
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Votre email est déjà vérifié.',
                'email_verified' => true,
            ]);
        }

        // Limiter les renvois (1 par minute pour éviter le spam)
        $key = 'verification_email_sent_' . $user->id;
        if (cache()->has($key)) {
            return response()->json([
                'message' => 'Un email de vérification a déjà été envoyé récemment. Veuillez patienter 1 minute.',
            ], 429);
        }

        // Envoyer l'email de vérification
        try {
            $user->notify(new VerifyEmailNotification());
            cache()->put($key, true, now()->addMinute());

            return response()->json([
                'message' => 'Email de vérification renvoyé avec succès.',
                'verification_sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur renvoi vérification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'envoi de l\'email.',
            ], 500);
        }
    }

    /**
     * Renvoyer l'email de vérification pour un utilisateur non authentifié.
     * Toujours répondre avec un message neutre pour éviter l'énumération d'emails.
     */
    public function resendVerificationEmailPublic(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));
        $cooldownKey = 'verification_email_public_' . sha1($email . '|' . (string) $request->ip());

        if (cache()->has($cooldownKey)) {
            return response()->json([
                'message' => 'Si un compte existe pour cette adresse, un email de vérification vient d\'être envoyé.',
                'verification_sent' => true,
            ]);
        }

        cache()->put($cooldownKey, true, now()->addMinute());

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && !$user->hasVerifiedEmail()) {
            try {
                $user->notify(new VerifyEmailNotification());
            } catch (\Throwable $e) {
                Log::error('Erreur renvoi vérification public', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Si un compte existe pour cette adresse, un email de vérification a été envoyé.',
            'verification_sent' => true,
        ]);
    }
}
