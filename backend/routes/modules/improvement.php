<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Improvement\Controllers\NonConformityController;
use App\Modules\Improvement\Controllers\ReclamationController;
use App\Modules\Improvement\Controllers\ComplaintController;
use App\Modules\Improvement\Controllers\ActionController;
use App\Modules\Improvement\Controllers\PlanActionController;
use App\Modules\Improvement\Controllers\ImprovementDashboardController;
use App\Modules\Improvement\Controllers\ImprovementSuggestionController;
use App\Modules\Improvement\Controllers\MyTasksController;
use App\Modules\Improvement\Controllers\ActionPlanTrackingController;

// ============================================================
// CHAPITRE 10 : AMÉLIORATION CONTINUE (NC, RÉCLAMATIONS, ACTIONS, PLANS)
// ============================================================

// --- Non-conformités (NC - §10.2) ---
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
Route::apiResource('non-conformities', NonConformityController::class);

// --- Réclamations et Plaintes ---
Route::apiResource('complaints', ComplaintController::class);
Route::post('/complaints/{complaint}/response', [ComplaintController::class, 'addResponse']);
Route::get('/complaints-categories', [ComplaintController::class, 'getCategories']);

Route::apiResource('reclamations', ReclamationController::class);
Route::prefix('reclamations')->group(function () {
    Route::post('{id}/analyze', [ReclamationController::class, 'analyze']);
    Route::post('{id}/respond', [ReclamationController::class, 'respond']);
    Route::post('{id}/close', [ReclamationController::class, 'close']);
    Route::get('statistics', [ReclamationController::class, 'statistics']);
});

// --- Actions correctives & d'amélioration ---
Route::get('my-actions', [ActionController::class, 'myActions']);
Route::get('actions/my-actions', [ActionController::class, 'myActions']);
Route::get('actions/templates/plan', [ActionController::class, 'downloadPlanTemplate']);
Route::post('actions/import-plan', [ActionController::class, 'importPlan']);
Route::prefix('actions')->group(function () {
    Route::post('{id}/status', [ActionController::class, 'updateStatus']);
    Route::post('{id}/progress', [ActionController::class, 'updateProgress']);
    Route::post('{id}/verify', [ActionController::class, 'verify']);
    Route::post('{id}/close', [ActionController::class, 'close']);
});
// Support param name {action} used by some clients/tests
Route::post('actions/{action}/progress', [ActionController::class, 'updateProgress']);
Route::apiResource('actions', ActionController::class);

// Plans d'actions
Route::apiResource('plan-actions', PlanActionController::class);
Route::post('plan-actions/{id}/actions', [PlanActionController::class, 'addAction']);

// Suivi individuel des actions (My Tasks & Tracking)
Route::prefix('my-tasks')->group(function () {
    Route::get('/', [MyTasksController::class, 'index']);
    Route::post('/{type}/{id}/tracking', [MyTasksController::class, 'tracking']);
    Route::get('/{type}/{id}/history', [MyTasksController::class, 'history']);
    Route::get('/{type}/{id}/report', [MyTasksController::class, 'report']);
});

Route::prefix('action-plans/tracking')->group(function () {
    Route::get('/', [ActionPlanTrackingController::class, 'index']);
    Route::post('/{type}/{id}', [ActionPlanTrackingController::class, 'store']);
    Route::patch('/{trackingId}/verify', [ActionPlanTrackingController::class, 'verifyUpdate']);
    Route::patch('/{type}/{id}/deadline', [ActionPlanTrackingController::class, 'updateDeadline']);
});

// --- Pilotage de l'amélioration continue ---
Route::prefix('improvement')->group(function () {
    Route::get('dashboard', [ImprovementDashboardController::class, 'global']);
    Route::get('trends', [ImprovementDashboardController::class, 'trends']);
    Route::get('axes-comparison', [ImprovementDashboardController::class, 'axesComparison']);
    Route::get('alerts', [ImprovementDashboardController::class, 'alerts']);
    Route::post('export-pdf', [ImprovementDashboardController::class, 'exportPdf']);
});

Route::post('improvement-suggestions/{id}/validate', [ImprovementSuggestionController::class, 'validateSuggestion']);
Route::apiResource('improvement-suggestions', ImprovementSuggestionController::class);

