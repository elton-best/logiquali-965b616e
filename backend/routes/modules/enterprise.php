<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Enterprise\Controllers\SearchController;
use App\Modules\Enterprise\Controllers\DashboardController;
use App\Modules\Evaluation\Controllers\ReportController;
use App\Modules\Enterprise\Controllers\UserController;
use App\Modules\Enterprise\Controllers\UserImportController;
use App\Modules\SuperAdmin\Controllers\AiController;
use App\Modules\Leadership\Controllers\NormController;
use App\Modules\Enterprise\Controllers\SiteController;
use App\Modules\Enterprise\Controllers\EnterpriseController;
use App\Modules\Support\Controllers\ArticleController;
use App\Modules\Enterprise\Controllers\OfferController;
use App\Modules\Enterprise\Controllers\EnterpriseSubscriptionController;
use App\Modules\Enterprise\Controllers\SubscriptionDashboardController;
use App\Modules\Enterprise\Controllers\SubscriptionGovernanceController;
use App\Modules\Enterprise\Controllers\SubscriptionOnboardingController;
use App\Modules\Enterprise\Controllers\SubscribedModuleController;
use App\Modules\Enterprise\Controllers\AccessCatalogController;
use App\Modules\Enterprise\Controllers\ModuleController;
use App\Modules\Enterprise\Controllers\SubModuleController;
use App\Modules\Enterprise\Controllers\SubModuleSectionController;
use App\Modules\Enterprise\Controllers\PaymentController;
use App\Modules\Support\Controllers\FileUploadController;
use App\Modules\Enterprise\Controllers\SecurityAuditLogController;
use App\Modules\Enterprise\Controllers\RateLimitController;
use App\Modules\Improvement\Controllers\ComplaintController;

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
Route::apiResource('dashboards', DashboardController::class);

Route::apiResource('offers', OfferController::class);
Route::apiResource('enterprise-subscriptions', EnterpriseSubscriptionController::class);

// Subscription routes
Route::get('subscription/dashboard', [SubscriptionDashboardController::class, 'index']);
Route::get('subscription/quick-actions', [SubscriptionDashboardController::class, 'quickActions']);
Route::get('subscription/current', [EnterpriseSubscriptionController::class, 'current']);
Route::get('subscription/accessible-modules', [EnterpriseSubscriptionController::class, 'accessibleModules']);
Route::get('subscription/status', [SubscriptionGovernanceController::class, 'status']);
Route::post('subscription/add-norm', [SubscriptionGovernanceController::class, 'addNorm']);
Route::post('subscription/renew-norm', [SubscriptionGovernanceController::class, 'renewNorm']);
Route::post('subscription/subscribe', [EnterpriseSubscriptionController::class, 'subscribe']);
Route::get('subscription/alerts', [EnterpriseSubscriptionController::class, 'alerts']);

// Onboarding routes
Route::post('subscription/onboarding', [SubscriptionOnboardingController::class, 'store']);
Route::get('subscription/onboarding/status', [SubscriptionOnboardingController::class, 'status']);
Route::get('sites/{id}/subscription', [SiteController::class, 'subscription']);
Route::get('sites/{site}/subscription/check', [EnterpriseSubscriptionController::class, 'checkSiteSubscription']);
Route::get('sites/{site}/subscriptions', [EnterpriseSubscriptionController::class, 'getSiteSubscriptions']);
Route::post('enterprise-subscriptions/{enterpriseSubscription}/renew', [EnterpriseSubscriptionController::class, 'renew']);

// Modules routes
Route::get('access/catalog', AccessCatalogController::class);
Route::get('modules', [ModuleController::class, 'index']);
Route::get('modules/accessible', [ModuleController::class, 'accessible']);
Route::get('modules/subscribed', [SubscribedModuleController::class, 'modules']);

// Sub-modules routes
Route::get('sub-modules', [SubModuleController::class, 'index']);
Route::get('sub-modules/accessible', [SubModuleController::class, 'accessible']);
Route::get('sub-modules/subscribed', [SubscribedModuleController::class, 'subModules']);

// Sub-module sections routes
Route::get('sub-module-sections', [SubModuleSectionController::class, 'index']);
Route::get('sub-module-sections/accessible', [SubModuleSectionController::class, 'accessible']);
Route::get('sub-module-sections/subscribed', [SubscribedModuleController::class, 'sections']);

// Payment routes
Route::post('payments/request', [PaymentController::class, 'requestPayment']);
Route::post('payments/check-subscription', [PaymentController::class, 'checkSubscriptionPayment']);
Route::post('payments/send-otp', [PaymentController::class, 'sendOtp']);
Route::post('payments/simulate', [PaymentController::class, 'simulate']);
Route::get('payments/history', [PaymentController::class, 'history']);

// File Upload (Secure)
Route::post('files/upload', [FileUploadController::class, 'upload']);
Route::delete('files', [FileUploadController::class, 'delete']);

// Security Audit Logs
Route::get('security-audit-logs', [SecurityAuditLogController::class, 'index']);
Route::get('security-audit-logs/stats', [SecurityAuditLogController::class, 'stats']);
Route::get('security-audit-logs/shadow-rbac-summary', [SecurityAuditLogController::class, 'shadowRbacSummary']);

// Rate Limit Management
Route::get('rate-limits/stats', [RateLimitController::class, 'stats']);
Route::get('rate-limits/blocked-ips', [RateLimitController::class, 'blockedIps']);
Route::post('rate-limits/unblock-ip', [RateLimitController::class, 'unblockIp']);
Route::post('rate-limits/block-ip', [RateLimitController::class, 'blockIp']);

// Certifications, Configurations Entreprise, Signatures & PDF
Route::get('/certifications/catalog', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'catalog']);
Route::get('/enterprises/{enterprise}/certifications', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'index']);
Route::post('/enterprises/{enterprise}/certifications', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'store']);
Route::put('/enterprises/{enterprise}/certifications/{certification}', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'update']);
Route::delete('/enterprises/{enterprise}/certifications/{certification}', [\App\Http\Controllers\Api\V1\EnterpriseCertificationController::class, 'destroy']);

Route::get('/enterprises/{enterprise}/configuration', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'show']);
Route::put('/enterprises/{enterprise}/branding', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateBranding']);
Route::put('/enterprises/{enterprise}/legal-info', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateLegalInfo']);
Route::put('/enterprises/{enterprise}/contact', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateContact']);
Route::put('/enterprises/{enterprise}/document-config', [\App\Http\Controllers\Api\V1\EnterpriseConfigurationController::class, 'updateDocumentConfig']);

Route::post('/signature-workflows/initiate', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'initiate']);
Route::post('/signature-workflows/{workflow}/sign', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'sign']);
Route::post('/signature-workflows/{workflow}/reject', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'reject']);
Route::get('/signature-workflows/pending', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'pending']);
Route::get('/signature-workflows/{workflow}', [\App\Http\Controllers\Api\V1\SignatureWorkflowController::class, 'show']);

Route::get('/qr-codes/stats', [\App\Http\Controllers\QRCodeVerificationController::class, 'stats']);
Route::post('/pdf/export', [\App\Http\Controllers\Api\V1\PdfExportController::class, 'exportDocument']);
Route::post('/pdf/preview', [\App\Http\Controllers\Api\V1\PdfExportController::class, 'previewDocument']);

