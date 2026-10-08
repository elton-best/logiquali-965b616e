<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Leadership\Controllers\OrgChartController;
use App\Modules\Support\Controllers\DocumentController;
use App\Modules\Enterprise\Controllers\GeoCatalogController;
use App\Modules\Evaluation\Controllers\EvaluationRequestController;
use App\Modules\SuperAdmin\Controllers\SuperAdminController;
use App\Modules\Support\Controllers\ProviderPartnerController;
use App\Modules\Enterprise\Controllers\UserNotificationController;
use App\Modules\Enterprise\Controllers\ConsentController;
use App\Models\Offer;

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
    $offers = Offer::with('norms')
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
        Route::get('/', [UserNotificationController::class, 'index']);
        Route::post('/mark-all-as-read', [UserNotificationController::class, 'markAllAsRead']);
        Route::post('/{notificationId}/read', [UserNotificationController::class, 'markAsRead']);
        Route::post('/{notificationId}/unread', [UserNotificationController::class, 'markAsUnread']);
        Route::post('/{type}/mark-type-as-read', [UserNotificationController::class, 'markTypeAsRead']);
        Route::get('/{notificationId}', [UserNotificationController::class, 'show']);
        Route::delete('/{notificationId}', [UserNotificationController::class, 'destroy']);
    });
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

// RGPD Consent Logs (Public + Authenticated)
Route::post('/consents', [ConsentController::class, 'store']); // Public

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/consents', [ConsentController::class, 'index']);
    Route::get('/consents/check/{type}', [ConsentController::class, 'check']);
    Route::delete('/consents/revoke-all', [ConsentController::class, 'revokeAll']);
});

