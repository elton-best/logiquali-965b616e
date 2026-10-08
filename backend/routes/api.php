<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SsoLoginController;
use App\Http\Controllers\Api\SubscribedModuleController;

// SSO Supervision : la plateforme cible valide le token et connecte l'utilisateur
Route::get('/sso/login', [SsoLoginController::class, 'login']);
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EnterpriseController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\NormController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\EnterpriseSubscriptionController;
use App\Http\Controllers\Api\SubscriptionDashboardController;
use App\Http\Controllers\Api\ProcessController;
use App\Http\Controllers\Api\ProcessReviewController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\RiskController;
use App\Http\Controllers\Api\OpportunityController;
use App\Http\Controllers\Api\ObjectiveController;
use App\Http\Controllers\Api\ActionController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\AuditProgramController;
use App\Http\Controllers\Api\NonConformityController;
use App\Http\Controllers\Api\StakeholderController;
use App\Http\Controllers\Api\ContextController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ManagementReviewController;
use App\Http\Controllers\Api\SatisfactionSurveyController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentImportController;
use App\Http\Controllers\Api\ModificationController;
use App\Http\Controllers\Api\StrategicAxisController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\JobDescriptionController;
use App\Http\Controllers\Api\JobDescriptionImportController;
use App\Http\Controllers\Api\ResponsibilityController;
use App\Http\Controllers\Api\QhsePolicyController;
use App\Http\Controllers\Api\OrgChartController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\ProcessResourceController;
use App\Http\Controllers\Api\ProcessInteractionController;
use App\Http\Controllers\Api\EmployeeEvaluationController;
use App\Http\Controllers\Api\ApplicableRequirementController;
use App\Http\Controllers\Api\ComplianceObligationAspectController;
use App\Http\Controllers\Api\ComplianceObligationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperAdminController;
use App\Http\Controllers\Api\SuperAdminNormController;
use App\Http\Controllers\Api\IndicateurController;
use App\Http\Controllers\Api\ReclamationController;
use App\Http\Controllers\Api\PlanActionController;
use App\Http\Controllers\Api\EvaluationCriteriaController;
use App\Http\Controllers\Api\EvaluationRequestController;
use App\Http\Controllers\Api\ImprovementDashboardController;
use App\Http\Controllers\Api\ClientBDashboardController;
use App\Http\Controllers\Api\ClientSatisfactionFormController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DocumentCollaborationController;
use App\Http\Controllers\Api\ConsentController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserImportController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\CodificationController;
use App\Http\Controllers\Api\EquipementController;
use App\Http\Controllers\Api\EquipementTransferController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\GeoCatalogController;
use App\Http\Controllers\Api\FileUploadController;
use App\Http\Controllers\Api\ProviderPartnerController;
use App\Http\Controllers\Api\SuperAdminSettingsController;
use App\Http\Controllers\Api\SuperAdminSessionController;
use App\Http\Controllers\Api\SuperAdminSearchController;
use App\Http\Controllers\Api\SuperAdminUserController;
use App\Http\Controllers\Api\MyTasksController;
use App\Http\Controllers\Api\ActionPlanTrackingController;
use App\Http\Controllers\Api\ValidationWorkflowController;
use App\Http\Controllers\Api\EnterpriseNormController;
use App\Http\Controllers\Api\ProcessReviewDataController;
use App\Http\Controllers\Api\EmergencyProcedureController;
use App\Http\Controllers\Api\DuerpController;


Route::prefix('/v1/')->group(function () {

    // Org chart preview/download (signed URL - no auth required, signature verified in controller)
    Route::get('/org-chart/view/{orgChart}', [OrgChartController::class, 'viewSigned'])
        ->middleware('throttle:30,1')
        ->name('org-chart.signed-view');


    // Routes publiques d'authentification
    Route::get('/auth/login', function () {
        return response()->json([
            'message' => 'Veuillez utiliser la méthode POST pour vous connecter',
            'method' => 'POST',
            'endpoint' => '/api/v1/auth/login',
            'required_fields' => ['email', 'password']
        ], 405);
    });
    Route::post('/auth/register/enterprise', [AuthController::class, 'registerEnterprise'])->middleware('throttle:login');
    Route::post('/auth/register/client', [AuthController::class, 'registerClient'])->middleware('throttle:login');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/mfa/verify', [AuthController::class, 'verifyMfa'])->middleware('throttle:login');
    Route::post('/auth/mfa/resend', [AuthController::class, 'resendMfa'])->middleware('throttle:login');

    // Routes publiques pour les catégories (nécessaires pour les formulaires)
    Route::get('documents/categories', [DocumentController::class, 'categories']);
    Route::get('processes-categories', function () {
        return response()->json([
            ['value' => 'pilotage', 'label' => 'Pilotage'],
            ['value' => 'support', 'label' => 'Support'],
            ['value' => 'operationnel', 'label' => 'Opérationnel'],
            ['value' => 'mesure_amelioration', 'label' => 'Mesure & Amélioration'],
        ]);
    });
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login');
    Route::post('/auth/resend-verification/public', [AuthController::class, 'resendVerificationEmailPublic'])->middleware('throttle:login');
    Route::get('/auth/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])->name('verification.verify');
    Route::get('/geo/countries', [GeoCatalogController::class, 'countries']);
    Route::get('/geo/countries/{countryCode}/cities', [GeoCatalogController::class, 'cities']);

    // Public offers for landing page
    Route::get('/public/offers', function () {
        $offers = \App\Models\Offer::with('norms')
            ->where('is_active', true)
            ->orderBy('price', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $offers,
        ]);
    });

    // Routes publiques pour les formulaires d'évaluation (accès via lien unique)
    Route::prefix('public/evaluation')->group(function () {
        Route::get('{token}', [EvaluationRequestController::class, 'showPublic'])->middleware('throttle:evaluation-public');
        Route::post('{token}', [EvaluationRequestController::class, 'submitPublic'])->middleware('throttle:evaluation-public');
        Route::post('{token}/reset-link', [EvaluationRequestController::class, 'requestPublicLinkReset'])->middleware('throttle:evaluation-public');
    });

    // Webhook provider payment (BegPay)
    Route::post('/provider/callback', [\App\Http\Controllers\Api\PaymentController::class, 'providerCallback'])
        ->middleware('throttle:60,1');

    // Routes protégées d'authentification
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
        Route::post('/auth/resend-verification', [AuthController::class, 'resendVerificationEmail']);
        Route::post('/auth/mfa/request', [AuthController::class, 'requestStepUpMfa']);

        // User Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\UserNotificationController::class, 'index']);
            Route::post('/mark-all-as-read', [App\Http\Controllers\Api\UserNotificationController::class, 'markAllAsRead']);
            Route::post('/{notificationId}/read', [App\Http\Controllers\Api\UserNotificationController::class, 'markAsRead']);
            Route::post('/{notificationId}/unread', [App\Http\Controllers\Api\UserNotificationController::class, 'markAsUnread']);
            Route::post('/{type}/mark-type-as-read', [App\Http\Controllers\Api\UserNotificationController::class, 'markTypeAsRead']);
            Route::get('/{notificationId}', [App\Http\Controllers\Api\UserNotificationController::class, 'show']);
            Route::delete('/{notificationId}', [App\Http\Controllers\Api\UserNotificationController::class, 'destroy']);
        });

        Route::get('entreprise/normes', [EnterpriseNormController::class, 'index']);

        Route::apiResource('validations', ValidationWorkflowController::class)->only(['index', 'store', 'show']);
        Route::post('validations/{validation}/verify', [ValidationWorkflowController::class, 'verify']);
        Route::post('validations/{validation}/approve', [ValidationWorkflowController::class, 'approve']);
        Route::post('validations/{validation}/refuse', [ValidationWorkflowController::class, 'refuse']);
    });

    // Document download (signed URL - no auth required, signature verified in controller)
    Route::get('/superadmin/enterprises/{enterprise}/documents/{document}/download', [SuperAdminController::class, 'downloadDocument'])
        ->name('superadmin.documents.download');
    Route::get('/superadmin/enterprises/{enterprise}/documents/{document}/preview', [SuperAdminController::class, 'previewDocument'])
        ->name('superadmin.documents.preview');
    Route::get('/provider-partners/{providerPartner}/contract/download/{type}', [ProviderPartnerController::class, 'downloadContract'])
        ->whereIn('type', ['generated', 'signed'])
        ->name('provider-contracts.download');
    Route::get('/provider-partners/{providerPartner}/contract/archive/{archiveId}/download/{type}', [ProviderPartnerController::class, 'downloadArchivedContract'])
        ->whereIn('type', ['generated', 'signed'])
        ->name('provider-contracts.archived.download');
    Route::get('/provider-partners/{providerPartner}/files/{providerFile}/download', [ProviderPartnerController::class, 'downloadProviderFile'])
        ->name('provider-files.download');

    // Routes SuperAdmin
    Route::prefix('superadmin')->middleware(['auth:sanctum', 'superadmin', 'force.password'])->group(function () {
        // Dashboard
        Route::get('/stats', [SuperAdminController::class, 'getDashboardStats'])->middleware('permission:dashboard.read');
        Route::get('/settings', [SuperAdminSettingsController::class, 'show'])->middleware('permission:settings.read');
        Route::get('/sessions', [SuperAdminSessionController::class, 'index'])->middleware('permission:sessions.read');
        Route::get('/search', [SuperAdminSearchController::class, 'search'])->middleware('permission:search.read');
        Route::get('/roles', [SuperAdminUserController::class, 'getRoles'])->middleware('permission:roles.read');

        // Enterprises & KYC
        Route::get('/enterprises', [SuperAdminController::class, 'getEnterprises'])->middleware('permission:enterprises.read');
        Route::get('/enterprises/{id}', [SuperAdminController::class, 'getEnterprise'])->middleware('permission:enterprises.read');

        // Norms (Complex CRUD)
        Route::get('/norms', [SuperAdminNormController::class, 'index'])->middleware('permission:norms.read');
        Route::get('/norms/download-template', [SuperAdminNormController::class, 'downloadTemplate'])->middleware('permission:norms.read');
        Route::post('/norms/import', [SuperAdminNormController::class, 'importExcel'])->middleware('permission:norms.manage');
        Route::get('/norms/{id}', [SuperAdminNormController::class, 'show'])->middleware('permission:norms.read');
        Route::get('/norms/{id}/export', [SuperAdminNormController::class, 'exportCsv'])->middleware('permission:norms.read');
        Route::post('/norms/{id}/pdf', [SuperAdminNormController::class, 'uploadPdf'])->middleware('permission:norms.manage');
        Route::delete('/norms/{id}/pdf', [SuperAdminNormController::class, 'deletePdf'])->middleware('permission:norms.manage');

        // Norm Versions
        Route::get('/norms/{normId}/versions/{versionId}', [SuperAdminNormController::class, 'getVersion'])->middleware('permission:norms.read');

        // Norm Sections

        // Offers
        Route::get('/offers', [SuperAdminController::class, 'getOffers'])->middleware('permission:offers.read');
        Route::get('/offers/{id}', [SuperAdminController::class, 'getOffer'])->middleware('permission:offers.read');

        // Subscriptions
        Route::get('/subscriptions', [SuperAdminController::class, 'getSubscriptions'])->middleware('permission:subscriptions.read');
        Route::get('/subscriptions/{id}', [SuperAdminController::class, 'getSubscription'])->middleware('permission:subscriptions.read');

        // Users Management
        Route::get('/users', [SuperAdminController::class, 'getAllUsers'])->middleware('permission:users.read');
        Route::get('/users/stats', [SuperAdminController::class, 'getUsersStats'])->middleware('permission:users.read');

        // Actions sensibles: MFA step-up requis
        Route::middleware('mfa.stepup')->group(function () {
            Route::put('/settings', [SuperAdminSettingsController::class, 'update'])->middleware('permission:settings.update');
            Route::post('/settings/email/test', [SuperAdminSettingsController::class, 'testEmail'])->middleware('permission:settings.update');
            Route::delete('/sessions/{tokenId}', [SuperAdminSessionController::class, 'destroy'])->middleware('permission:sessions.revoke');

            Route::post('/enterprises/{id}/approve', [SuperAdminController::class, 'approveEnterprise'])->middleware('permission:enterprises.update');
            Route::post('/enterprises/{id}/reject', [SuperAdminController::class, 'rejectEnterprise'])->middleware('permission:enterprises.update');
            Route::post('/enterprises/{id}/suspend', [SuperAdminController::class, 'suspendEnterprise'])->middleware('permission:enterprises.update');
            Route::post('/enterprises/{id}/reactivate', [SuperAdminController::class, 'reactivateEnterprise'])->middleware('permission:enterprises.update');
            Route::delete('/enterprises/{id}', [SuperAdminController::class, 'deleteEnterprise'])->middleware('permission:enterprises.delete');

            Route::post('/norms', [SuperAdminNormController::class, 'store'])->middleware('permission:norms.manage');
            Route::put('/norms/{id}', [SuperAdminNormController::class, 'update'])->middleware('permission:norms.manage');
            Route::delete('/norms/{id}', [SuperAdminNormController::class, 'destroy'])->middleware('permission:norms.manage');
            Route::post('/norms/{id}/publish', [SuperAdminNormController::class, 'publish'])->middleware('permission:norms.manage');
            Route::post('/norms/{id}/archive', [SuperAdminNormController::class, 'archive'])->middleware('permission:norms.manage');
            Route::post('/norms/{id}/unarchive', [SuperAdminNormController::class, 'unarchive'])->middleware('permission:norms.manage');
            Route::post('/norm-versions/{versionId}/sections', [SuperAdminNormController::class, 'storeSection'])->middleware('permission:norms.manage');
            Route::put('/norm-sections/{sectionId}', [SuperAdminNormController::class, 'updateSection'])->middleware('permission:norms.manage');
            Route::delete('/norm-sections/{sectionId}', [SuperAdminNormController::class, 'deleteSection'])->middleware('permission:norms.manage');

            Route::post('/offers', [SuperAdminController::class, 'createOffer'])->middleware('permission:offers.create');
            Route::put('/offers/{id}', [SuperAdminController::class, 'updateOffer'])->middleware('permission:offers.update');
            Route::delete('/offers/{id}', [SuperAdminController::class, 'deleteOffer'])->middleware('permission:offers.delete');

            Route::post('/subscriptions/{id}/suspend', [SuperAdminController::class, 'suspendSubscription'])->middleware('permission:subscriptions.update');
            Route::post('/subscriptions/{id}/reactivate', [SuperAdminController::class, 'reactivateSubscription'])->middleware('permission:subscriptions.update');
            Route::delete('/subscriptions/{id}', [SuperAdminController::class, 'deleteSubscription'])->middleware('permission:subscriptions.delete');

            Route::post('/users', [SuperAdminUserController::class, 'store'])->middleware('permission:users.create');
            Route::put('/users/{id}', [SuperAdminUserController::class, 'update'])->middleware('permission:users.update');
        });
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'force.password', 'force.signature', 'force.company', 'check.subscription', 'ensure.ownership'])->group(function () {
        // Search routes (Global + Advanced)
        Route::get('/search', [SearchController::class, 'globalSearch'])->middleware('throttle:search');
        Route::post('/search/advanced', [SearchController::class, 'advancedSearch'])->middleware('throttle:search');

        // Dashboard routes
        Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
        Route::get('/dashboard/kpis', [DashboardController::class, 'getKpis']);
        Route::get('/dashboard/collaborator-actions', [DashboardController::class, 'getCollaboratorActions'])
            ->name('api/v1/dashboard/collaborator-actions');
        Route::get('/dashboard/charts/{chartType}', [DashboardController::class, 'getChartData']);
        Route::get('/dashboard/layout', [DashboardController::class, 'getLayout']);
        Route::post('/dashboard/layout', [DashboardController::class, 'saveLayout']);

        // Reports routes
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports/generate', [ReportController::class, 'generate']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::get('/reports/{id}/download', [ReportController::class, 'download']);
        Route::delete('/reports/{id}', [ReportController::class, 'destroy']);

        // Users routes
        Route::get('users/job-description-collaborators', [UserController::class, 'jobDescriptionCollaborators']);
        Route::get('users/pending-approvals', [UserController::class, 'pendingApprovals']);
        Route::post('users/{id}/approve', [UserController::class, 'approveCollaborator']);
        Route::post('users/{id}/reject', [UserController::class, 'rejectCollaborator']);
        Route::get('users/export-docx', [UserController::class, 'exportDocx']);
        Route::get('users/sidebar', [UserController::class, 'getSidebar']); // Chantier 8: Dynamic sidebar
        Route::apiResource('users', UserController::class);
        Route::post('users/import', [UserImportController::class, 'import']);

        // AI routes (Grok proxy)
        Route::post('ai/generate', [AiController::class, 'generate']);
        Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/users/{id}/photo', [UserController::class, 'uploadPhoto']);
        Route::post('/users/{id}/signature', [UserController::class, 'uploadSignature']);
        Route::post('/users/{id}/roles', [UserController::class, 'addRole']);
        Route::delete('/users/{id}/roles/{roleName}', [UserController::class, 'removeRole']);
        Route::put('/auth/profile', [UserController::class, 'updateProfile']);

        // Permission management routes
        Route::get('/available-roles', [UserController::class, 'getAvailableRoles']);
        Route::get('/available-permissions/active', [UserController::class, 'getActivePermissions'])
            ->middleware('throttle:60,1'); // AUDIT: Rate-limit to prevent enumeration attacks
        Route::get('/available-permissions', [UserController::class, 'getAvailablePermissions']);
        Route::get('/users/{id}/permissions', [UserController::class, 'getUserPermissions']);
        Route::post('/users/{id}/assign-role', [UserController::class, 'assignRole']);
        Route::post('/users/{id}/assign-custom-permissions', [UserController::class, 'assignCustomPermissions']);
        Route::delete('/users/{id}/permissions', [UserController::class, 'revokeAllPermissions']);

        // Norms routes (company: read-only)
        Route::get('/norms', [NormController::class, 'index'])->middleware('permission:norm_library.read|dashboard.read');
        Route::get('/norms/{id}', [NormController::class, 'show'])
            ->whereNumber('id')
            ->middleware('permission:norm_library.read|dashboard.read');
        Route::get('/norms/{id}/chapters', [NormController::class, 'chapters'])
            ->whereNumber('id')
            ->middleware('permission:norm_library.read|dashboard.read');

        // Sites routes
        // IMPORTANT: Routes spécifiques AVANT apiResource pour éviter les conflits
        Route::get('/sites/search', [ComplaintController::class, 'searchSites']);
        Route::post('/sites/{id}/toggle-active', [SiteController::class, 'toggleActive']);
        Route::apiResource('sites', SiteController::class);

        // Other resources
        // Enterprises - specific routes before apiResource
        Route::post('/enterprises/{enterprise}/sigle/preview', [EnterpriseController::class, 'previewSigleChange'])
            ->middleware('throttle:20,1');
        Route::put('/enterprises/{enterprise}/sigle', [EnterpriseController::class, 'updateSigle'])
            ->middleware('throttle:10,1');
        Route::get('/enterprises/{enterprise}/sigle/history', [EnterpriseController::class, 'getSigleHistory'])
            ->middleware('throttle:20,1');
        Route::apiResource('enterprises', EnterpriseController::class);
        Route::apiResource('articles', ArticleController::class);

        // Complaints routes (Client B & Client A)
        Route::apiResource('complaints', ComplaintController::class);
        Route::post('/complaints/{complaint}/response', [ComplaintController::class, 'addResponse']);
        Route::get('/complaints-categories', [ComplaintController::class, 'getCategories']);

        Route::apiResource('offers', OfferController::class);
        Route::apiResource('enterprise-subscriptions', EnterpriseSubscriptionController::class);

        // Subscription routes
        Route::get('subscription/dashboard', [SubscriptionDashboardController::class, 'index']);
        Route::get('subscription/quick-actions', [SubscriptionDashboardController::class, 'quickActions']);
        Route::get('subscription/current', [EnterpriseSubscriptionController::class, 'current']);
        Route::get('subscription/accessible-modules', [EnterpriseSubscriptionController::class, 'accessibleModules']);
        Route::get('subscription/status', [\App\Http\Controllers\Api\SubscriptionGovernanceController::class, 'status']);
        Route::post('subscription/add-norm', [\App\Http\Controllers\Api\SubscriptionGovernanceController::class, 'addNorm']);
        Route::post('subscription/renew-norm', [\App\Http\Controllers\Api\SubscriptionGovernanceController::class, 'renewNorm']);
        Route::post('subscription/subscribe', [EnterpriseSubscriptionController::class, 'subscribe']);
        Route::get('subscription/alerts', [EnterpriseSubscriptionController::class, 'alerts']);

        // Onboarding routes
        Route::post('subscription/onboarding', [\App\Http\Controllers\Api\SubscriptionOnboardingController::class, 'store']);
        Route::get('subscription/onboarding/status', [\App\Http\Controllers\Api\SubscriptionOnboardingController::class, 'status']);
        Route::get('sites/{id}/subscription', [SiteController::class, 'subscription']);
        Route::get('sites/{site}/subscription/check', [EnterpriseSubscriptionController::class, 'checkSiteSubscription']);
        Route::get('sites/{site}/subscriptions', [EnterpriseSubscriptionController::class, 'getSiteSubscriptions']);
        Route::post('enterprise-subscriptions/{enterpriseSubscription}/renew', [EnterpriseSubscriptionController::class, 'renew']);

        // Modules routes
        Route::get('access/catalog', \App\Http\Controllers\Api\AccessCatalogController::class);
        Route::get('modules', [\App\Http\Controllers\Api\ModuleController::class, 'index']);
        Route::get('modules/accessible', [\App\Http\Controllers\Api\ModuleController::class, 'accessible']);
        Route::get('modules/subscribed', [SubscribedModuleController::class, 'modules']);

        // Sub-modules routes
        Route::get('sub-modules', [\App\Http\Controllers\Api\SubModuleController::class, 'index']);
        Route::get('sub-modules/accessible', [\App\Http\Controllers\Api\SubModuleController::class, 'accessible']);
        Route::get('sub-modules/subscribed', [SubscribedModuleController::class, 'subModules']);

        // Sub-module sections routes
        Route::get('sub-module-sections', [\App\Http\Controllers\Api\SubModuleSectionController::class, 'index']);
        Route::get('sub-module-sections/accessible', [\App\Http\Controllers\Api\SubModuleSectionController::class, 'accessible']);
        Route::get('sub-module-sections/subscribed', [SubscribedModuleController::class, 'sections']);

        // Document Signatures routes 
        Route::post('document-signatures/sign', [\App\Http\Controllers\Api\V1\DocumentSignatureController::class, 'sign']);
        Route::get('document-signatures', [\App\Http\Controllers\Api\V1\DocumentSignatureController::class, 'index']);
        Route::get('document-signatures/check-signed', [\App\Http\Controllers\Api\V1\DocumentSignatureController::class, 'checkSigned']);

        // Document Code Recycling routes
        Route::get('document-codes/check-availability', [\App\Http\Controllers\Api\DocumentCodeRecyclingController::class, 'checkAvailability']);
        Route::post('document-codes/release', [\App\Http\Controllers\Api\DocumentCodeRecyclingController::class, 'releaseCode']);
        Route::get('document-codes/available', [\App\Http\Controllers\Api\DocumentCodeRecyclingController::class, 'availableCodes']);
        Route::get('document-codes/{code}/history', [\App\Http\Controllers\Api\DocumentCodeRecyclingController::class, 'codeHistory']);

        // Payment routes
        Route::post('payments/request', [\App\Http\Controllers\Api\PaymentController::class, 'requestPayment']);
        Route::post('payments/check-subscription', [\App\Http\Controllers\Api\PaymentController::class, 'checkSubscriptionPayment']);
        Route::post('payments/send-otp', [\App\Http\Controllers\Api\PaymentController::class, 'sendOtp']);
        Route::post('payments/simulate', [\App\Http\Controllers\Api\PaymentController::class, 'simulate']);
        Route::get('payments/history', [\App\Http\Controllers\Api\PaymentController::class, 'history']);

        // Processes (extended QHSE)
        // Process routes
        Route::get('processes/statistics', [ProcessController::class, 'statistics']);
        Route::get('processes/{process}/export-docx', [ProcessController::class, 'exportDocx']);
        Route::post('processes/{process}/generate-draft', [ProcessController::class, 'generateDraftDocx']);
        Route::apiResource('processes', ProcessController::class);
        Route::get('processes-cartography', [ProcessController::class, 'cartography']);

        // Process Workflow
        Route::post('processes/{process}/verify', [ProcessController::class, 'verify']);
        Route::post('processes/{process}/validate', [ProcessController::class, 'validateProcess']);
        Route::post('processes/{process}/reject', [ProcessController::class, 'reject']);

        // Process Indicators & Risks
        Route::post('processes/{process}/indicators', [ProcessController::class, 'addIndicator']);
        Route::post('processes/{process}/risks-opportunities', [ProcessController::class, 'addRiskOpportunity']);
        // Canonical endpoints
        Route::get('risks-opportunities', [ProcessController::class, 'listRisksOpportunities']);
        Route::get('risks-opportunities/export-xlsx', [ProcessController::class, 'exportRisksOpportunitiesXlsx']);
        Route::get('risks-opportunities/{riskOpportunity}', [ProcessController::class, 'showRiskOpportunity']);
        Route::put('risks-opportunities/{riskOpportunity}', [ProcessController::class, 'updateRiskOpportunity']);
        Route::delete('risks-opportunities/{riskOpportunity}', [ProcessController::class, 'deleteRiskOpportunity']);
        Route::patch('risks-opportunities/{riskOpportunity}/evaluation', [ProcessController::class, 'updateRiskEvaluation']);
        // Legacy aliases (keep for compatibility)
        Route::get('process-risks-opportunities', [ProcessController::class, 'listRisksOpportunities'])
            ->middleware('legacy.deprecated:risks-opportunities,2026-12-31');
        Route::get('process-risks-opportunities/{riskOpportunity}', [ProcessController::class, 'showRiskOpportunity'])
            ->middleware('legacy.deprecated:risks-opportunities/{riskOpportunity},2026-12-31');
        Route::put('process-risks-opportunities/{riskOpportunity}', [ProcessController::class, 'updateRiskOpportunity'])
            ->middleware('legacy.deprecated:risks-opportunities/{riskOpportunity},2026-12-31');
        Route::delete('process-risks-opportunities/{riskOpportunity}', [ProcessController::class, 'deleteRiskOpportunity'])
            ->middleware('legacy.deprecated:risks-opportunities/{riskOpportunity},2026-12-31');
        Route::post('processes/{process}/iso-coverage', [ProcessController::class, 'addIsoCoverage']);
        Route::prefix('processes/{process}/reviews')->group(function () {
            Route::get('current', [ProcessReviewController::class, 'current']);
            Route::get('current/linked-data', [ProcessReviewController::class, 'linkedData']);
            Route::get('revue-data', [ProcessReviewDataController::class, 'show']);
            Route::post('current/linked-data/actions', [ProcessReviewController::class, 'createLinkedAction']);
            Route::put('current', [ProcessReviewController::class, 'upsertCurrent']);
            Route::post('current/close', [ProcessReviewController::class, 'closeCurrent']);
            Route::get('current/export-pdf', [ProcessReviewController::class, 'exportCurrentPdf']);
            Route::get('current/export-docx', [ProcessReviewController::class, 'exportCurrentDocx']);
        });
        Route::get('processus/{process}/revue-data', [ProcessReviewDataController::class, 'show']);
        Route::get('processes/{process}/metrics', [ProcessReviewController::class, 'getMetrics']); // Chantier 7
        Route::get('processes/{process}/reviews/dashboard-actions', [ProcessController::class, 'listReviewDashboardActions']);
        Route::post('indicators/{indicator}/values', [ProcessController::class, 'addIndicatorValue']);

        // Process Sequences
        Route::post('processes/{process}/sequences', [ProcessController::class, 'addSequence']);
        Route::put('processes/{process}/sequences/{sequenceId}', [ProcessController::class, 'updateSequence']);
        Route::delete('processes/{process}/sequences/{sequenceId}', [ProcessController::class, 'deleteSequence']);

        // Process Versions
        Route::get('processes/{process}/versions', [ProcessController::class, 'getVersions']);
        Route::post('processes/{process}/versions', [ProcessController::class, 'createVersion']);
        Route::post('processes/{process}/versions/{versionId}/verify', [ProcessController::class, 'verifyVersion']);
        Route::post('processes/{process}/versions/{versionId}/approve', [ProcessController::class, 'approveVersion']);

        // Process Objectives
        Route::post('processes/{process}/objectives', [ProcessController::class, 'addObjective']);
        Route::put('processes/{process}/objectives/{objectiveId}', [ProcessController::class, 'updateObjective']);
        Route::delete('processes/{process}/objectives/{objectiveId}', [ProcessController::class, 'deleteObjective']);
        // Operational Planning
        Route::get('operational-planning/overview', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'overview']);
        Route::post('operational-projects', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'storeProject']);
        Route::put('operational-projects/{project}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'updateProject']);
        Route::delete('operational-projects/{project}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'destroyProject']);
        Route::post('operational-projects/{project}/evidences', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'uploadProjectEvidence']);
        Route::delete('operational-projects/{project}/evidences/{index}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'deleteProjectEvidence']);
        Route::post('operational-projects/{project}/activities', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'storeActivity']);
        Route::put('operational-project-activities/{activity}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'updateActivity']);
        Route::delete('operational-project-activities/{activity}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'destroyActivity']);
        Route::post('operational-project-activities/{activity}/tasks', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'storeTask']);
        Route::put('operational-project-tasks/{task}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'updateTask']);
        Route::delete('operational-project-tasks/{task}', [\App\Http\Controllers\Api\OperationalPlanningController::class, 'destroyTask']);
        Route::get('iso-coverage-matrix', [ProcessController::class, 'isoCoverageMatrix']);
        Route::get('system-settings', [ProcessController::class, 'systemSettings']);
        Route::apiResource('activities', ActivityController::class);
        // Risks - Add custom routes BEFORE apiResource
        Route::get('risks/export-docx', [RiskController::class, 'exportDocx']);
        Route::get('risks/heatmap', [RiskController::class, 'heatmap']);
        Route::get('risks/matrix', [RiskController::class, 'getMatrix']);
        Route::get('risks/statistics', [RiskController::class, 'statistics']);
        Route::post('risks/{id}/assess', [RiskController::class, 'assess']);
        Route::post('risks/{id}/treat', [RiskController::class, 'treat']);
        Route::post('risks/{id}/mitigate', [RiskController::class, 'mitigate']);
        Route::apiResource('risks', RiskController::class);
        Route::apiResource('opportunities', OpportunityController::class);
        Route::get('objectives/export-xlsx', [ObjectiveController::class, 'exportXlsx']);
        Route::apiResource('objectives', ObjectiveController::class);
        Route::get('actions/templates/plan', [ActionController::class, 'downloadPlanTemplate']);
        Route::post('actions/import-plan', [ActionController::class, 'importPlan']);
        Route::apiResource('actions', ActionController::class);
        Route::get('actions/{id}/contract', [ActionController::class, 'contract']);
        Route::post('actions/{id}/reschedule', [ActionController::class, 'reschedule']);
        // Allow collaborators to update action progress via /actions/{id}/progress
        Route::post('actions/{id}/progress', [ActionController::class, 'updateProgress']);
        // Support param name {action} used by some clients/tests
        Route::post('actions/{action}/progress', [ActionController::class, 'updateProgress']);

        // Audits - Routes étendues
        Route::prefix('audits')->group(function () {
            Route::match(['put', 'post'], '{id}/status', [AuditController::class, 'updateStatus']);
            Route::post('{id}/start', [AuditController::class, 'start']);
            Route::post('{id}/checklist', [AuditController::class, 'addChecklist']);
            Route::post('{id}/generate-checklist', [AuditController::class, 'generateChecklist']);
            Route::post('{id}/generate-report', [AuditController::class, 'generateReport']);
            Route::post('{id}/approve', [AuditController::class, 'approveReport']);
            Route::get('{id}/findings', [AuditController::class, 'findings']);
            Route::post('{id}/finding', [AuditController::class, 'addFinding']);
            Route::post('{id}/finalize', [AuditController::class, 'finalize']);
            Route::post('{id}/complete', [AuditController::class, 'complete']);
            Route::post('{id}/upload-external-report', [AuditController::class, 'uploadExternalReport']);
            Route::post('{id}/send-invitations', [AuditController::class, 'sendInvitations']);
            Route::get('{id}/auditor-evaluation-criteria', [AuditController::class, 'auditorEvaluationCriteria']);
            Route::post('{id}/auditor-evaluations', [AuditController::class, 'createAuditorEvaluationRequest']);
            Route::get('{id}/download-report', [AuditController::class, 'downloadReport']);
            Route::get('statistics', [AuditController::class, 'statistics']);
            // Compléments et synthèse
            Route::put('{id}/complements', [AuditController::class, 'updateComplements']);
            Route::put('{id}/norm-inputs', [AuditController::class, 'updateNormInputs']);
            Route::get('{id}/synthesis', [AuditController::class, 'generateSynthesis']);
            Route::post('{id}/reminders', [AuditController::class, 'scheduleReminders']);
        });
        Route::apiResource('audits', AuditController::class);

        // Programmes d'audits
        Route::prefix('audit-programs')->group(function () {
            Route::post('{id}/validate', [AuditProgramController::class, 'validate']);
            Route::post('{id}/generate-from-risks', [AuditProgramController::class, 'generateFromRisks']);
            Route::get('{id}/export-calendar', [AuditProgramController::class, 'exportCalendar']);
            Route::get('{id}/statistics', [AuditProgramController::class, 'statistics']);
        });
        Route::apiResource('audit-programs', AuditProgramController::class);

        Route::apiResource('non-conformities', NonConformityController::class);

        Route::get('stakeholders/export-docx', [StakeholderController::class, 'exportDocx']);
        Route::post('stakeholders/generate-draft', [StakeholderController::class, 'generateDraftDocx']);
        Route::apiResource('stakeholders', StakeholderController::class);
        Route::get('application-scopes/{application_scope}/export-docx', [\App\Http\Controllers\Api\ApplicationScopeController::class, 'exportDocx']);
        Route::post('application-scopes/{application_scope}/generate-draft', [\App\Http\Controllers\Api\ApplicationScopeController::class, 'generateDraftDocx']);
        Route::apiResource('application-scopes', \App\Http\Controllers\Api\ApplicationScopeController::class);
        Route::get('contexts/export-docx', [ContextController::class, 'exportDocx']);
        Route::post('contexts/generate-draft', [ContextController::class, 'generateDraftDocx']);
        Route::apiResource('contexts', ContextController::class);
        Route::apiResource('dashboards', DashboardController::class);

        Route::get('management-reviews/{managementReview}/export-docx', [ManagementReviewController::class, 'exportDocx']);
        Route::post('management-reviews/{managementReview}/generate-draft', [ManagementReviewController::class, 'generateDraftDocx']);
        Route::get('management-reviews/sm-synthesis', [ManagementReviewController::class, 'getSmSynthesis']);
        Route::post('management-reviews/{managementReview}/close', [ManagementReviewController::class, 'close']);
        Route::post('management-reviews/{managementReview}/generate-data', [ManagementReviewController::class, 'generateData']);
        Route::post('management-reviews/{managementReview}/generate-sm-synthesis', [ManagementReviewController::class, 'generateSmSynthesis']);
        Route::post('management-reviews/{managementReview}/send-invitations', [ManagementReviewController::class, 'sendInvitations']);
        Route::get('management-reviews/templates/procedure', [ManagementReviewController::class, 'downloadProcedureTemplate']);
        Route::get('management-reviews/templates/invitation', [ManagementReviewController::class, 'downloadInvitationTemplate']);
        Route::apiResource('management-reviews', ManagementReviewController::class);

        // Satisfaction Surveys
        Route::apiResource('satisfaction-surveys', SatisfactionSurveyController::class);

        // Client Satisfaction Forms (M13-D4)
        Route::prefix('client-satisfaction-forms')->group(function () {
            Route::get('/', [ClientSatisfactionFormController::class, 'index']);
            Route::post('/', [ClientSatisfactionFormController::class, 'store']);
            Route::get('/statistics', [ClientSatisfactionFormController::class, 'statistics']);
            Route::get('/{clientSatisfactionForm}', [ClientSatisfactionFormController::class, 'show']);
            Route::put('/{clientSatisfactionForm}', [ClientSatisfactionFormController::class, 'update']);
            Route::delete('/{clientSatisfactionForm}', [ClientSatisfactionFormController::class, 'destroy']);
            Route::post('/{clientSatisfactionForm}/submit', [ClientSatisfactionFormController::class, 'submit']);
            Route::post('/{clientSatisfactionForm}/review', [ClientSatisfactionFormController::class, 'review']);
            Route::get('/{clientSatisfactionForm}/export-pdf', [ClientSatisfactionFormController::class, 'exportPdf']);
        });
        // Documents QHSE avec fonctionnalités étendues
        // Collaboration realtime routes (before apiResource)
        Route::post('documents/{document}/collaboration/join', [DocumentCollaborationController::class, 'join']);
        Route::post('documents/{document}/collaboration/leave', [DocumentCollaborationController::class, 'leave']);
        Route::get('documents/{document}/collaboration/state', [DocumentCollaborationController::class, 'getState']);
        Route::post('documents/{document}/collaboration/sync', [DocumentCollaborationController::class, 'syncContent']);
        Route::post('documents/{document}/collaboration/save', [DocumentCollaborationController::class, 'save']);
        Route::get('documents/{document}/collaboration/users', [DocumentCollaborationController::class, 'getActiveUsers']);
        Route::get('documents/preview-code', [DocumentController::class, 'previewCode']);
        Route::get('documents/active-norms', [DocumentController::class, 'getActiveNorms']);
        Route::get('documents/export-inventory', [DocumentController::class, 'exportInventory']);

        // Pyramide documentaire exports (Module 13)
        Route::get('documents/pyramide/export/{level}', [DocumentController::class, 'exportByLevel']); // N1, N2, N3, N4, N5
        Route::get('documents/pyramide/export-complete', [DocumentController::class, 'exportCompletePyramid']); // All levels
        Route::get('documents/pyramide/stats', [DocumentController::class, 'getPyramideStats']);
        Route::get('documents/stats', [DocumentController::class, 'getStats']);
        Route::get('documents/templates/politique-qualite', [DocumentController::class, 'downloadPolitiqueTemplate']);
        Route::get('documents/templates/manuel-qualite', [DocumentController::class, 'downloadManuelTemplate']);

        // Workflow Validation Documents
        Route::post('documents/{document}/confirm-code', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'confirmCode'])
            ->middleware('document.workflow.rate.limit');
        Route::post('documents/{document}/verify', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'verify'])
            ->middleware(['permission:verify_documents', 'document.workflow.rate.limit']);
        Route::post('documents/{document}/approve', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'approve'])
            ->middleware(['permission:approve_documents', 'document.workflow.rate.limit']);
        Route::post('documents/{document}/reject', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'reject'])
            ->middleware('document.workflow.rate.limit');
        Route::post('documents/{document}/confirm-rejection-decision', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'confirmRejectionDecision'])
            ->middleware('document.workflow.rate.limit');
        Route::get('documents/{document}/workflow-history', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'getHistory']);
        Route::get('documents/generated-source-state', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'generatedSourceState']);
        Route::post('documents/{document}/delegate', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'delegate'])
            ->middleware('document.workflow.rate.limit');
        Route::post('documents/{document}/send-reminder', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'sendReminder'])
            ->middleware('document.workflow.rate.limit');
        Route::get('workflow/pending-documents', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'getPendingDocuments']);
        Route::get('workflow/stats', [\App\Http\Controllers\Api\DocumentWorkflowController::class, 'getWorkflowStats']);


        Route::apiResource('documents', DocumentController::class);
        Route::post('documents/{document}/versions', [DocumentController::class, 'uploadVersion']);
        Route::get('documents/{document}/download/{versionId?}', [DocumentController::class, 'download']);
        Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
        Route::post('documents/{document}/submit-for-approval', [DocumentController::class, 'submitForApproval']);
        Route::get('documents/{document}/workflow-integrity', [DocumentController::class, 'workflowIntegrity']);
        Route::post('documents/{document}/archive', [DocumentController::class, 'archive']);
        Route::post('document-approvals/{approval}/process', [DocumentController::class, 'approve']);
        Route::apiResource('modifications', ModificationController::class);
        Route::post('modifications/{modification}/submit', [ModificationController::class, 'submit']);
        Route::apiResource('strategic-axes', StrategicAxisController::class);
        Route::get('plans/export-xlsx', [PlanController::class, 'exportXlsx']);
        Route::apiResource('plans', PlanController::class);
        Route::apiResource('roles', RoleController::class)->middleware([
            'index' => 'permission:roles.read',
            'show' => 'permission:roles.read',
            'store' => 'permission:roles.create',
            'update' => 'permission:roles.update',
            'destroy' => 'permission:roles.delete',
        ]);
        Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware(['permission:roles.update', 'validate.permission.assignment']);
        // Leadership Module
        Route::get('qhse-policies/current', [QhsePolicyController::class, 'current']);
        Route::get('qhse-policies/history', [QhsePolicyController::class, 'history']);
        Route::get('qhse-policies/{qhsePolicy}/export-pdf', [QhsePolicyController::class, 'exportPdf']);
        Route::get('qhse-policies/{qhsePolicy}/export-docx', [QhsePolicyController::class, 'exportDocx']);
        Route::post('qhse-policies/{qhsePolicy}/generate-draft', [QhsePolicyController::class, 'generateDraftDocx']);
        Route::post('qhse-policies/{qhsePolicy}/validate', [QhsePolicyController::class, 'validate']);
        Route::apiResource('qhse-policies', QhsePolicyController::class);

        Route::post('org-chart/upload', [OrgChartController::class, 'upload']);
        Route::get('org-chart/current', [OrgChartController::class, 'current']);
        Route::delete('org-chart/{id}', [OrgChartController::class, 'destroy']);

        Route::post('job-descriptions/{jobDescription}/sign-employee', [JobDescriptionController::class, 'signAsEmployee']);
        Route::post('job-descriptions/{jobDescription}/sign-ceo', [JobDescriptionController::class, 'signAsManager']);
        Route::get('job-descriptions/{jobDescription}/signature-status', [JobDescriptionController::class, 'signatureStatus']);
        Route::apiResource('job-descriptions', JobDescriptionController::class);
        Route::get('job-descriptions/{jobDescription}/pdf', [JobDescriptionController::class, 'downloadPdf']);
        Route::post('job-descriptions/{jobDescription}/generate-draft', [JobDescriptionController::class, 'generateDraftDocx']);
        
        // Job Descriptions Import Routes
        Route::prefix('job-descriptions/import')->group(function () {
            Route::post('upload', [JobDescriptionImportController::class, 'upload']);
            Route::get('preview/{importId}', [JobDescriptionImportController::class, 'preview']);
            Route::post('confirm/{importId}', [JobDescriptionImportController::class, 'confirm']);
            Route::get('template', [JobDescriptionImportController::class, 'downloadTemplate']);
            Route::get('logs', [JobDescriptionImportController::class, 'logs']);
            Route::get('logs/{logId}/errors', [JobDescriptionImportController::class, 'downloadErrorReport']);
        });
        
        Route::get('responsibilities/export-pdf', [ResponsibilityController::class, 'exportPdf']);
        Route::get('responsibilities/export-docx', [ResponsibilityController::class, 'exportDocx']);
        Route::post('responsibilities/generate-draft', [ResponsibilityController::class, 'generateDraftDocx']);
        Route::apiResource('responsibilities', ResponsibilityController::class);
        Route::apiResource('team-members', TeamMemberController::class);
        Route::apiResource('process-resources', ProcessResourceController::class);
        Route::apiResource('process-interactions', ProcessInteractionController::class);
        Route::apiResource('employee-evaluations', EmployeeEvaluationController::class);

        // Evaluation exports (Module 12 - Satisfaction PIP)
        Route::get('employee-evaluations/{employeeEvaluation}/export-pdf', [EmployeeEvaluationController::class, 'exportPdf']);
        Route::get('employee-evaluations/{employeeEvaluation}/export-anonymous-pdf', [EmployeeEvaluationController::class, 'exportAnonymousPdf']);
        Route::post('provider-evaluations/export-pdf', [EmployeeEvaluationController::class, 'exportProviderPdf']); // Generic provider eval

        // Provider management (ISO 8.4)
        Route::get('provider-partners/export-xlsx', [ProviderPartnerController::class, 'exportXlsx']);
        Route::apiResource('provider-partners', ProviderPartnerController::class)
            ->parameters(['provider-partners' => 'providerPartner']);
        Route::post('provider-partners/import', [ProviderPartnerController::class, 'importRows']);
        Route::post('provider-partners/import-file', [ProviderPartnerController::class, 'importFile']);
        Route::get('provider-contract-template', [ProviderPartnerController::class, 'showTemplate']);
        Route::put('provider-contract-template', [ProviderPartnerController::class, 'updateTemplate']);
        Route::get('provider-partners/{providerPartner}/contract', [ProviderPartnerController::class, 'showContract']);
        Route::put('provider-partners/{providerPartner}/contract', [ProviderPartnerController::class, 'upsertContract']);
        Route::post('provider-partners/{providerPartner}/contract/upload', [ProviderPartnerController::class, 'uploadSignedContract']);
        Route::post('provider-partners/{providerPartner}/contract/archive', [ProviderPartnerController::class, 'archiveContract']);
        Route::post('provider-partners/{providerPartner}/contract/preview', [ProviderPartnerController::class, 'previewContract']);
        Route::post('provider-partners/{providerPartner}/contract/generate-pdf', [ProviderPartnerController::class, 'generateContractPdf']);
        Route::get('provider-partners/{providerPartner}/files', [ProviderPartnerController::class, 'filesIndex']);
        Route::post('provider-partners/{providerPartner}/files', [ProviderPartnerController::class, 'uploadFile']);
        Route::delete('provider-partners/{providerPartner}/files/{providerFile}', [ProviderPartnerController::class, 'destroyFile']);
        Route::get('templates/procedure-evaluation-pip', [EmployeeEvaluationController::class, 'downloadProcedureTemplate']);

        Route::apiResource('applicable-requirements', ApplicableRequirementController::class);

        // Critères d'évaluation personnalisables
        Route::prefix('evaluation-criteria')->group(function () {
            Route::get('defaults', [EvaluationCriteriaController::class, 'defaults']);
            Route::post('initialize-defaults', [EvaluationCriteriaController::class, 'initializeDefaults']);
            Route::post('reorder', [EvaluationCriteriaController::class, 'reorder']);
            Route::get('categories', [EvaluationCriteriaController::class, 'categories']);
            Route::post('{id}/duplicate', [EvaluationCriteriaController::class, 'duplicate']);
            Route::post('{id}/toggle-active', [EvaluationCriteriaController::class, 'toggleActive']);
        });
        Route::apiResource('evaluation-criteria', EvaluationCriteriaController::class);

        // Demandes d'évaluation (envoi par email avec lien unique)
        Route::prefix('evaluation-requests')->group(function () {
            Route::get('statistics', [EvaluationRequestController::class, 'statistics']);
            Route::get('{id}/responses', [EvaluationRequestController::class, 'responses']);
            Route::post('{id}/send', [EvaluationRequestController::class, 'send']);
            Route::post('{id}/reminder', [EvaluationRequestController::class, 'sendReminder']);
            Route::post('{id}/cancel', [EvaluationRequestController::class, 'cancel']);
        });
        Route::apiResource('evaluation-requests', EvaluationRequestController::class);

        // Compliance Obligations (ISO §6.1.3)
        Route::get('compliance-obligation-aspects', [ComplianceObligationAspectController::class, 'index']);
        Route::post('compliance-obligation-aspects', [ComplianceObligationAspectController::class, 'store']);
        Route::put('compliance-obligation-aspects/{id}', [ComplianceObligationAspectController::class, 'update']);
        Route::get('compliance-obligations/template', [ComplianceObligationController::class, 'downloadTemplate']);
        Route::get('compliance-obligations/export-xlsx', [ComplianceObligationController::class, 'exportXlsx']);
        Route::post('compliance-obligations/import-xlsx', [ComplianceObligationController::class, 'importXlsx']);
        Route::apiResource('compliance-obligations', ComplianceObligationController::class)->except(['show']);

        // Product/Service requirements: API aliases mapped to the same compliance obligation engine
        Route::get('product-service-requirements-obligation-aspects', [ComplianceObligationAspectController::class, 'index']);
        Route::post('product-service-requirements-obligation-aspects', [ComplianceObligationAspectController::class, 'store']);
        Route::put('product-service-requirements-obligation-aspects/{id}', [ComplianceObligationAspectController::class, 'update']);
        Route::get('prod-req-obligations/template', [ComplianceObligationController::class, 'downloadTemplate']);
        Route::get('prod-req-obligations/export-xlsx', [ComplianceObligationController::class, 'exportXlsx']);
        Route::post('prod-req-obligations/import-xlsx', [ComplianceObligationController::class, 'importXlsx']);
        Route::apiResource('prod-req-obligations', ComplianceObligationController::class, [
            'parameters' => ['prod-req-obligations' => 'obligation']
        ])->except(['show']);

        // ============================================================
        // MODULE AMÉLIORATION CONTINUE QHSE (ISO 9001/14001/45001)
        // ============================================================

        // Non-conformités (NC) - §10.2
        Route::prefix('non-conformities')->group(function () {
            Route::post('{id}/status', [NonConformityController::class, 'updateStatus']);
            Route::post('{id}/assign', [NonConformityController::class, 'assignResponsible']);
            Route::post('{id}/analyze', [NonConformityController::class, 'analyze']);
            Route::post('{id}/validate', [NonConformityController::class, 'validate']);
            Route::post('{id}/verify', [NonConformityController::class, 'verify']);
            Route::post('{id}/close', [NonConformityController::class, 'close']);
            Route::post('{id}/cost', [NonConformityController::class, 'addCost']);
            Route::get('statistics', [NonConformityController::class, 'statistics']);
            Route::get('procedure-template', [NonConformityController::class, 'exportProcedureTemplate']);
            Route::post('{id}/generate-report', [NonConformityController::class, 'generateReport']);
        });

        // Indicateurs (KPI) - §9.1
        Route::prefix('indicateurs')->group(function () {
            Route::get('{id}/trend', [IndicateurController::class, 'trend']);
            Route::get('{id}/chart-data', [IndicateurController::class, 'getChartData']);
            Route::get('{id}/check-thresholds', [IndicateurController::class, 'checkThresholds']);
            Route::post('{id}/value', [IndicateurController::class, 'addValue']);
        });
        Route::apiResource('indicateurs', IndicateurController::class);

        // Objectifs QHSE - §6.2
        Route::prefix('objectives')->group(function () {
            Route::post('{id}/progress', [ObjectiveController::class, 'updateProgress']);
            Route::post('{id}/milestone', [ObjectiveController::class, 'addMilestone']);
        });

        // Risques/Opportunités - §6.1
        Route::prefix('risks')->group(function () {
            Route::post('{id}/assess', [RiskController::class, 'assess']);
            Route::post('{id}/treat', [RiskController::class, 'treat']);
            Route::get('matrix', [RiskController::class, 'getMatrix']);
            Route::get('statistics', [RiskController::class, 'statistics']);
        });

        // Réclamations - §9.1.2
        Route::apiResource('reclamations', ReclamationController::class);
        Route::prefix('reclamations')->group(function () {
            Route::post('{id}/analyze', [ReclamationController::class, 'analyze']);
            Route::post('{id}/respond', [ReclamationController::class, 'respond']);
            Route::post('{id}/close', [ReclamationController::class, 'close']);
            Route::get('statistics', [ReclamationController::class, 'statistics']);
        });

        // Actions correctives/préventives
        Route::prefix('actions')->group(function () {
            Route::post('{id}/status', [ActionController::class, 'updateStatus']);
            Route::post('{id}/progress', [ActionController::class, 'updateProgress']);
            Route::post('{id}/verify', [ActionController::class, 'verify']);
            Route::post('{id}/close', [ActionController::class, 'close']);
        });

        // Plans d'actions
        Route::apiResource('plan-actions', PlanActionController::class);
        Route::post('plan-actions/{id}/actions', [PlanActionController::class, 'addAction']);

        // Dashboards amélioration continue
        Route::prefix('improvement')->group(function () {
            Route::get('dashboard', [ImprovementDashboardController::class, 'global']);
            Route::get('trends', [ImprovementDashboardController::class, 'trends']);
            Route::get('axes-comparison', [ImprovementDashboardController::class, 'axesComparison']);
            Route::get('alerts', [ImprovementDashboardController::class, 'alerts']);
            Route::post('export-pdf', [ImprovementDashboardController::class, 'exportPdf']);
        });

        // Suggestions d'amélioration continue
        Route::apiResource('improvement-suggestions', \App\Http\Controllers\Api\ImprovementSuggestionController::class);

        // ============================================================
        // CLIENT B ROUTES (Client final - Consommateur)
        // ============================================================

        Route::prefix('clientb')->group(function () {
            // Dashboard Client B
            Route::get('dashboard/stats', [ClientBDashboardController::class, 'getStats']);
            Route::get('dashboard/recent-activity', [ClientBDashboardController::class, 'getRecentActivity']);
            Route::get('dashboard/quick-actions', [ClientBDashboardController::class, 'getQuickActions']);

            // Profile Management
            Route::get('profile/stats', [App\Http\Controllers\Api\ClientBProfileController::class, 'getStats']);
            Route::put('profile', [App\Http\Controllers\Api\ClientBProfileController::class, 'updateProfile']);
            Route::post('profile/change-password', [App\Http\Controllers\Api\ClientBProfileController::class, 'changePassword']);

            // Support Tickets
            Route::get('support/tickets', [App\Http\Controllers\Api\SupportTicketController::class, 'index']);
            Route::post('support/tickets', [App\Http\Controllers\Api\SupportTicketController::class, 'store']);
            Route::get('support/tickets/{id}', [App\Http\Controllers\Api\SupportTicketController::class, 'show']);
            Route::get('support/stats', [App\Http\Controllers\Api\SupportTicketController::class, 'stats']);
        });

        // ============================================================
        // MODULE SUPPORT (Ressources, Compétences, Communication, Documentation)
        // ============================================================

        // Codification (Catégories et Localisations)
        Route::apiResource('codifications', CodificationController::class);

        // Équipements (Inventaire)
        Route::get('equipements/prochain-indice', [EquipementController::class, 'prochainIndice']);
        Route::post('equipements/import-file', [EquipementController::class, 'import']);
        Route::post('equipements/import', [EquipementController::class, 'import']); // Legacy alias
        Route::get('equipements/{equipement}/transfers', [EquipementTransferController::class, 'equipmentTransfers'])
            ->middleware('throttle:20,1');
        Route::post('equipements/{equipement}/transfer', [EquipementTransferController::class, 'store'])
            ->middleware('throttle:10,1');
        Route::apiResource('equipements', EquipementController::class);

        // Equipment Transfers (History & Verification)
        Route::get('transfers', [EquipementTransferController::class, 'index'])
            ->middleware('throttle:20,1');
        Route::get('transfers/{transfer}', [EquipementTransferController::class, 'show'])
            ->middleware('throttle:20,1');
        Route::put('transfers/{transfer}/verify', [EquipementTransferController::class, 'verify'])
            ->middleware('throttle:10,1');
        Route::get('enterprises/{enterprise}/transfer-reasons', [EquipementTransferController::class, 'getTransferReasons'])
            ->middleware('throttle:20,1');

        // Maintenances (Plan et Suivi)
        Route::get('maintenances/alertes', [MaintenanceController::class, 'alertes']);
        Route::get('maintenances/template', [MaintenanceController::class, 'template']);
        Route::get('maintenances/export', [MaintenanceController::class, 'export']);
        Route::post('maintenances/import-file', [MaintenanceController::class, 'import']);
        Route::post('maintenances/import', [MaintenanceController::class, 'import']); // Legacy alias
        Route::post('maintenances/{id}/suivre', [MaintenanceController::class, 'suivre']);
        Route::apiResource('maintenances', MaintenanceController::class);

        // Formations (Compétences)
        Route::get('formations/stats', [\App\Http\Controllers\Api\FormationController::class, 'stats']);
        Route::post('formations/import-file', [\App\Http\Controllers\Api\FormationController::class, 'importFile']);
        Route::post('formations/{formation}/complete', [\App\Http\Controllers\Api\FormationController::class, 'complete']);
        Route::post('formations/{formation}/reschedule', [\App\Http\Controllers\Api\FormationController::class, 'reschedule']);
        Route::post('formations/{formation}/cancel', [\App\Http\Controllers\Api\FormationController::class, 'cancel']);
        Route::post('formations/{formation}/proofs', [\App\Http\Controllers\Api\FormationController::class, 'uploadProof']);
        Route::delete('formations/{formation}/proofs/{proof}', [\App\Http\Controllers\Api\FormationController::class, 'deleteProof']);
        Route::get('formations/{formation}/internal-evaluation', [\App\Http\Controllers\Api\FormationInternalEvaluationController::class, 'show']);
        Route::post('formations/{formation}/internal-evaluation', [\App\Http\Controllers\Api\FormationInternalEvaluationController::class, 'upsert']);
        Route::delete('formations/{formation}/internal-evaluation', [\App\Http\Controllers\Api\FormationInternalEvaluationController::class, 'destroy']);
        Route::apiResource('formations', \App\Http\Controllers\Api\FormationController::class);
        Route::get('training-plans/template', [\App\Http\Controllers\Api\TrainingPlanController::class, 'template']);
        Route::get('training-plans/{trainingPlan}/export-xlsx', [\App\Http\Controllers\Api\TrainingPlanController::class, 'exportXlsx']);
        Route::post('training-plans/{trainingPlan}/sync', [\App\Http\Controllers\Api\TrainingPlanController::class, 'sync']);
        Route::apiResource('training-plans', \App\Http\Controllers\Api\TrainingPlanController::class);

        // Matrice de Compétences
        Route::get('competence-matrix', [\App\Http\Controllers\Api\CompetenceMatrixController::class, 'getMatrix']);
        Route::get('competence-matrix/gap-analysis', [\App\Http\Controllers\Api\CompetenceMatrixController::class, 'getGapAnalysis']);
        Route::get('competence-matrix/training-plan', [\App\Http\Controllers\Api\CompetenceMatrixController::class, 'getTrainingPlan']);
        Route::get('competence-matrix/export', [\App\Http\Controllers\Api\CompetenceMatrixController::class, 'export']);

        // Communications (Communication et Sensibilisation)
        Route::get('communications/stats', [\App\Http\Controllers\Api\CommunicationController::class, 'stats']);
        Route::post('communications/import-file', [\App\Http\Controllers\Api\CommunicationController::class, 'importFile']);
        Route::get('communications/template', [\App\Http\Controllers\Api\CommunicationController::class, 'template']);
        Route::get('communications/export', [\App\Http\Controllers\Api\CommunicationController::class, 'export']);
        Route::post('communications/{communication}/complete', [\App\Http\Controllers\Api\CommunicationController::class, 'complete']);
        Route::post('communications/{communication}/reschedule', [\App\Http\Controllers\Api\CommunicationController::class, 'reschedule']);
        Route::post('communications/{communication}/cancel', [\App\Http\Controllers\Api\CommunicationController::class, 'cancel']);
        Route::post('communications/{communication}/proofs', [\App\Http\Controllers\Api\CommunicationController::class, 'uploadProof']);
        Route::delete('communications/{communication}/proofs/{proof}', [\App\Http\Controllers\Api\CommunicationController::class, 'deleteProof']);
        Route::apiResource('communications', \App\Http\Controllers\Api\CommunicationController::class);
        Route::post('communication-plans/{communicationPlan}/sync', [\App\Http\Controllers\Api\CommunicationPlanController::class, 'sync']);
        Route::apiResource('communication-plans', \App\Http\Controllers\Api\CommunicationPlanController::class);

        // Nomenclatures (nouveau système DocumentTypeConfiguration + NomenclatureTemplate)
        Route::post('nomenclature-templates/validate-template', [\App\Http\Controllers\Api\NomenclatureTemplateController::class, 'validateTemplate']);
        Route::post('nomenclature-templates/simulate-samples', [\App\Http\Controllers\Api\NomenclatureTemplateController::class, 'simulateSamples']);
        Route::post('nomenclature-templates/preview-code', [\App\Http\Controllers\Api\NomenclatureTemplateController::class, 'previewCode']);
        Route::apiResource('nomenclature-templates', \App\Http\Controllers\Api\NomenclatureTemplateController::class);
        Route::apiResource('document-type-catalogs', \App\Http\Controllers\Api\DocumentTypeCatalogController::class)
            ->except(['show']);
        Route::apiResource('process-catalogs', \App\Http\Controllers\Api\ProcessCatalogController::class)
            ->except(['show']);

        // Document Import Wizard
        Route::get('document-imports/nomenclature-schema', [\App\Http\Controllers\Api\DocumentImportController::class, 'nomenclatureSchema']);
        Route::get('document-imports/template', [\App\Http\Controllers\Api\DocumentImportController::class, 'downloadTemplate']);
        Route::get('document-imports', [\App\Http\Controllers\Api\DocumentImportController::class, 'index']);
        Route::post('document-imports/upload', [\App\Http\Controllers\Api\DocumentImportController::class, 'upload'])
            ->middleware('throttle:10,1');
        Route::post('document-imports/upload-files', [\App\Http\Controllers\Api\DocumentImportController::class, 'uploadFiles'])
            ->middleware('throttle:10,1'); // Max 20 imports par heure
        Route::post('document-imports/{importId}/validate', [\App\Http\Controllers\Api\DocumentImportController::class, 'validate']);
        Route::post('document-imports/{importId}/execute', [\App\Http\Controllers\Api\DocumentImportController::class, 'execute']);
        Route::post('document-imports/{importId}/rollback', [\App\Http\Controllers\Api\DocumentImportController::class, 'rollback']);
        Route::get('document-imports/{importId}', [\App\Http\Controllers\Api\DocumentImportController::class, 'show']);
        Route::delete('document-imports/{importId}', [\App\Http\Controllers\Api\DocumentImportController::class, 'destroy']);

        // ============================================================
        // MODULE ISO 45001 (Sécurité et Santé au Travail)
        // ============================================================

        // Habilitations
        Route::get('habilitations/expires-soon', [\App\Http\Controllers\Api\HabilitationController::class, 'expiresSoon']);
        Route::get('habilitations/export', [\App\Http\Controllers\Api\HabilitationController::class, 'export']);
        Route::get('habilitations/stats', [\App\Http\Controllers\Api\HabilitationController::class, 'stats']);
        Route::post('habilitations/{habilitation}/renew', [\App\Http\Controllers\Api\HabilitationController::class, 'renew']);
        Route::apiResource('habilitations', \App\Http\Controllers\Api\HabilitationController::class);

        // Habilitations (legacy routes - keep for compatibility)
        Route::get('habilitations/alertes', [\App\Http\Controllers\Api\HabilitationController::class, 'expiresSoon'])
            ->middleware('legacy.deprecated:habilitations/expires-soon,2026-12-31');

        // EPI (Équipements de Protection Individuelle)
        Route::get('epi/catalogue', [\App\Http\Controllers\Api\EpiController::class, 'catalogue']);
        Route::post('epi/catalogue', [\App\Http\Controllers\Api\EpiController::class, 'storeCatalogue']);
        Route::get('epi/stocks', [\App\Http\Controllers\Api\EpiController::class, 'stocks']);
        Route::post('epi/stocks', [\App\Http\Controllers\Api\EpiController::class, 'storeStock']);
        Route::put('epi/stocks/{id}', [\App\Http\Controllers\Api\EpiController::class, 'updateStock']);
        Route::get('epi/stocks/{stockId}/mouvements', [\App\Http\Controllers\Api\EpiController::class, 'mouvements']);
        Route::post('epi/mouvements', [\App\Http\Controllers\Api\EpiController::class, 'storeMouvement']);
        Route::get('epi/attributions', [\App\Http\Controllers\Api\EpiController::class, 'attributions']);
        Route::post('epi/attributions', [\App\Http\Controllers\Api\EpiController::class, 'storeAttribution']);
        Route::get('epi/stats', [\App\Http\Controllers\Api\EpiController::class, 'stats']);

        // VGP (Vérifications Générales Périodiques)
        Route::get('verifications-reglementaires/alertes', [\App\Http\Controllers\Api\VerificationReglementaireController::class, 'alertes']);
        Route::get('verifications-reglementaires/stats', [\App\Http\Controllers\Api\VerificationReglementaireController::class, 'stats']);
        Route::apiResource('verifications-reglementaires', \App\Http\Controllers\Api\VerificationReglementaireController::class);

        // ============================================================
        // MODULE ISO 14001 (Environnement)
        // ============================================================

        // Aspects Environnementaux
        Route::get('aspects-environnementaux/settings', [\App\Http\Controllers\Api\AspectEnvironnementalController::class, 'settings']);
        Route::put('aspects-environnementaux/settings', [\App\Http\Controllers\Api\AspectEnvironnementalController::class, 'updateSettings']);
        Route::get('aspects-environnementaux/stats', [\App\Http\Controllers\Api\AspectEnvironnementalController::class, 'stats']);
        Route::get('aspects-environnementaux/matrice', [\App\Http\Controllers\Api\AspectEnvironnementalController::class, 'matrice']);
        Route::apiResource('aspects-environnementaux', \App\Http\Controllers\Api\AspectEnvironnementalController::class);
        Route::apiResource('emergency-procedures', EmergencyProcedureController::class);
        Route::get('duerp', [DuerpController::class, 'index']);
        Route::post('duerp', [DuerpController::class, 'store']);
        Route::get('duerp/{duerp}', [DuerpController::class, 'show']);
        Route::put('duerp/{duerp}', [DuerpController::class, 'update']);
        Route::get('duerp-work-units', [DuerpController::class, 'workUnits']);
        Route::post('duerp-work-units', [DuerpController::class, 'storeWorkUnit']);
        Route::post('duerp-work-units/{workUnit}/families', [DuerpController::class, 'storeFamily']);
        Route::get('duerp-risk-types', [DuerpController::class, 'riskTypes']);
        Route::post('duerp-risk-types', [DuerpController::class, 'storeRiskType']);
        Route::post('duerp-risk-types/{riskType}/archive', [DuerpController::class, 'archiveRiskType']);
        Route::get('duerp-scales', [DuerpController::class, 'scales']);
        Route::post('duerp-scales', [DuerpController::class, 'storeScale']);
        Route::post('duerp/{duerp}/dangers', [DuerpController::class, 'storeDanger']);
        Route::post('duerp-dangers/{danger}/prevention-actions', [DuerpController::class, 'storePreventionAction']);

        // Obligations Conformité Environnementale
        Route::get('obligations-conformite-environnementales/alertes', [\App\Http\Controllers\Api\ObligationConformiteEnvironnementaleController::class, 'alertes']);
        Route::get('obligations-conformite-environnementales/stats', [\App\Http\Controllers\Api\ObligationConformiteEnvironnementaleController::class, 'stats']);
        Route::apiResource('obligations-conformite-environnementales', \App\Http\Controllers\Api\ObligationConformiteEnvironnementaleController::class, [
            'parameters' => ['obligations-conformite-environnementales' => 'obligation']
        ]);

        // ============================================================
        // MODULE ISO 50001 (Énergie)
        // ============================================================

        // Consommations Énergie
        Route::get('consommations-energie/stats', [\App\Http\Controllers\Api\ConsommationEnergieController::class, 'stats']);
        Route::post('consommations-energie/import-file', [\App\Http\Controllers\Api\ConsommationEnergieController::class, 'importCsv']);
        Route::post('consommations-energie/import-csv', [\App\Http\Controllers\Api\ConsommationEnergieController::class, 'importCsv']); // Legacy alias
        Route::apiResource('consommations-energie', \App\Http\Controllers\Api\ConsommationEnergieController::class);

        // IPE (Indicateurs Performance Énergétique)
        Route::get('ipe/stats', [\App\Http\Controllers\Api\IpeController::class, 'stats']);
        Route::post('ipe/{id}/valeurs', [\App\Http\Controllers\Api\IpeController::class, 'addValeur']);
        Route::get('ipe/{id}/valeurs', [\App\Http\Controllers\Api\IpeController::class, 'valeurs']);
        Route::get('ipe/{id}/tendance', [\App\Http\Controllers\Api\IpeController::class, 'tendance']);
        Route::apiResource('ipe', \App\Http\Controllers\Api\IpeController::class);



        Route::post('qhse-policies/{qhsePolicy}/submit', [\App\Http\Controllers\Api\QhsePolicyController::class, 'submitForReview']);

        // File Upload (Secure)
        Route::post('files/upload', [FileUploadController::class, 'upload']);
        Route::delete('files', [FileUploadController::class, 'delete']);

        // ============================================================
        // CONFIGURATION NOMENCLATURE DOCUMENTS
        // ============================================================
        Route::prefix('document-type-configurations')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'store']);
            Route::get('/visibility-stats', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'visibilityStats']);
            Route::get('/advanced-filters', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'advancedFilters']);
            Route::post('/preview-code', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'previewCode']);
            Route::post('/validate-structure', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'validateStructure']);
            Route::get('/{id}', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'show']);
            Route::put('/{id}', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'destroy']);
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'duplicate']);
            Route::post('/{id}/toggle-active', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'toggleActive']);
            Route::post('/{id}/share-with-sites', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'shareWithSites']);
            Route::post('/{id}/share-with-enterprises', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'shareWithEnterprises']);
            Route::get('/{id}/available-sites-for-sharing', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'availableSitesForSharing']);
            Route::get('/{id}/available-enterprises-for-sharing', [\App\Http\Controllers\Api\DocumentTypeConfigurationController::class, 'availableEnterprisesForSharing']);
        });

        // ============================================================
        // WORKFLOW CODES DOCUMENTS
        // ============================================================
        Route::prefix('document-code-workflow')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\DocumentCodeWorkflowController::class, 'getWorkflowStats']);
            Route::get('/pending-verification', [\App\Http\Controllers\Api\DocumentCodeWorkflowController::class, 'pendingVerification']);
            Route::get('/pending-approval', [\App\Http\Controllers\Api\DocumentCodeWorkflowController::class, 'pendingApproval']);
            Route::post('/documents/{document}/verify', [\App\Http\Controllers\Api\DocumentCodeWorkflowController::class, 'verifyCode']);
            Route::post('/documents/{document}/activate', [\App\Http\Controllers\Api\DocumentCodeWorkflowController::class, 'activateCode']);
            Route::post('/documents/{document}/release', [\App\Http\Controllers\Api\DocumentCodeWorkflowController::class, 'releaseCode']);
        });

        // ============================================================
        // IMPORT DOCUMENTS EXISTANTS
        // ============================================================
        Route::prefix('document-import')->group(function () {
            Route::post('/analyze', [DocumentImportController::class, 'analyze']);
            Route::post('/preview', [DocumentImportController::class, 'preview']);
            Route::post('/execute', [DocumentImportController::class, 'executeMigration']);
        });

        // Security Audit Logs
        Route::get('security-audit-logs', [\App\Http\Controllers\Api\SecurityAuditLogController::class, 'index']);
        Route::get('security-audit-logs/stats', [\App\Http\Controllers\Api\SecurityAuditLogController::class, 'stats']);
        Route::get('security-audit-logs/shadow-rbac-summary', [\App\Http\Controllers\Api\SecurityAuditLogController::class, 'shadowRbacSummary']);

        // Rate Limit Management
        Route::get('rate-limits/stats', [\App\Http\Controllers\Api\RateLimitController::class, 'stats']);
        Route::get('rate-limits/blocked-ips', [\App\Http\Controllers\Api\RateLimitController::class, 'blockedIps']);
        Route::post('rate-limits/unblock-ip', [\App\Http\Controllers\Api\RateLimitController::class, 'unblockIp']);
        Route::post('rate-limits/block-ip', [\App\Http\Controllers\Api\RateLimitController::class, 'blockIp']);
    });

    // ============================================================
    // MY TASKS - Task Tracking & Reporting (Chantier 4)
    // ============================================================
    Route::prefix('my-tasks')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [MyTasksController::class, 'index']); // GET /api/v1/my-tasks
        Route::post('/{type}/{id}/tracking', [MyTasksController::class, 'tracking']); // POST /api/v1/my-tasks/{type}/{id}/tracking
        Route::get('/{type}/{id}/history', [MyTasksController::class, 'history']); // GET /api/v1/my-tasks/{type}/{id}/history
        Route::get('/{type}/{id}/report', [MyTasksController::class, 'report']); // GET /api/v1/my-tasks/{type}/{id}/report
    });

    // ============================================================
    // PLAN DU SM - Global Calendar View (Chantier 6)
    // ============================================================
    Route::get('/plan-sm', [\App\Http\Controllers\Api\PlanSMController::class, 'index'])->middleware('auth:sanctum'); // GET /api/plan-sm

    Route::prefix('action-plans/tracking')->group(function () {
        Route::get('/', [ActionPlanTrackingController::class, 'index']);
        Route::post('/{type}/{id}', [ActionPlanTrackingController::class, 'store']);
        Route::patch('/{trackingId}/verify', [ActionPlanTrackingController::class, 'verifyUpdate']);
        Route::patch('/{type}/{id}/deadline', [ActionPlanTrackingController::class, 'updateDeadline']);
    });

    // RGPD Consent Logs (Public + Authenticated)
    Route::post('/consents', [ConsentController::class, 'store']); // Public

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/consents', [ConsentController::class, 'index']);
        Route::get('/consents/check/{type}', [ConsentController::class, 'check']);
        Route::delete('/consents/revoke-all', [ConsentController::class, 'revokeAll']);
    });
});

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // ============================================================
    // DOCUMENTS PERSONNALISÉS - Certifications, Configuration, Signatures, QR Codes
    // ============================================================

    // Certifications
    Route::get('/certifications/catalog', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'catalog']);
    Route::get('/enterprises/{enterprise}/certifications', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'index']);
    Route::post('/enterprises/{enterprise}/certifications', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'store']);
    Route::put('/enterprises/{enterprise}/certifications/{certification}', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'update']);
    Route::delete('/enterprises/{enterprise}/certifications/{certification}', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'destroy']);

    // Configuration Entreprise
    Route::get('/enterprises/{enterprise}/configuration', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'show']);
    Route::put('/enterprises/{enterprise}/branding', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateBranding']);
    Route::put('/enterprises/{enterprise}/legal-info', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateLegalInfo']);
    Route::put('/enterprises/{enterprise}/contact', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateContact']);
    Route::put('/enterprises/{enterprise}/document-config', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateDocumentConfig']);

    // Workflows de Signatures
    Route::post('/signature-workflows/initiate', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'initiate']);
    Route::post('/signature-workflows/{workflow}/sign', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'sign']);
    Route::post('/signature-workflows/{workflow}/reject', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'reject']);
    Route::get('/signature-workflows/pending', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'pending']);
    Route::get('/signature-workflows/{workflow}', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'show']);

    // QR Code Stats (authentifié)
    Route::get('/qr-codes/stats', [\App\Http\Controllers\QRCodeVerificationController::class, 'stats']);

    // PDF Export avec en-têtes/pieds de page personnalisés
    Route::post('/pdf/export', [\App\Http\Controllers\Api\V1\PdfExportController::class, 'exportDocument']);
    Route::post('/pdf/preview', [\App\Http\Controllers\Api\V1\PdfExportController::class, 'previewDocument']);
});

// QR Code Vérification (PUBLIC - pas d'auth)
Route::get('/verify/{hash}', [\App\Http\Controllers\QRCodeVerificationController::class, 'verify']);
