<?php

use Illuminate\Support\Facades\Route;
use App\Modules\SuperAdmin\Controllers\SuperAdminController;
use App\Modules\SuperAdmin\Controllers\SuperAdminSettingsController;
use App\Modules\SuperAdmin\Controllers\SuperAdminSessionController;
use App\Modules\SuperAdmin\Controllers\SuperAdminSearchController;
use App\Modules\SuperAdmin\Controllers\SuperAdminUserController;
use App\Modules\SuperAdmin\Controllers\SuperAdminNormController;

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

