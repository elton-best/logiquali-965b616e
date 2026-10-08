<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Processes\Controllers\ProcessController;
use App\Modules\Processes\Controllers\ProcessReviewController;
use App\Modules\Processes\Controllers\ActivityController;
use App\Modules\Processes\Controllers\ProcessResourceController;
use App\Modules\Processes\Controllers\ProcessInteractionController;

// ============================================================
// CHAPITRE 4 & 8 : PROCESSUS & CARTOGRAPHIE
// ============================================================

// Process routes
Route::get('processes/statistics', [ProcessController::class, 'statistics']);
Route::get('processes/{process}/export-docx', [ProcessController::class, 'exportDocx']);
Route::get('processes/{process}/export-pdf', [ProcessController::class, 'exportPdf']);
Route::post('processes/{process}/generate-draft', [ProcessController::class, 'generateDraftDocx']);
Route::apiResource('processes', ProcessController::class);

// Cartographie dynamique
Route::get('processes-cartography', [ProcessController::class, 'cartography']);
Route::get('processes-cartography/export-pdf', [ProcessController::class, 'exportCartographyPdf']);
Route::get('processes-cartography/export-docx', [ProcessController::class, 'exportCartographyDocx']);
Route::post('processes-cartography/generate-draft', [ProcessController::class, 'generateCartographyDraft']);

// Workflow de validation des processus
Route::post('processes/{process}/verify', [ProcessController::class, 'verify']);
Route::post('processes/{process}/validate', [ProcessController::class, 'validateProcess']);
Route::post('processes/{process}/reject', [ProcessController::class, 'reject']);

// Indicateurs & Couverture ISO
Route::post('processes/{process}/indicators', [ProcessController::class, 'addIndicator']);
Route::post('indicators/{indicator}/values', [ProcessController::class, 'addIndicatorValue']);
Route::post('processes/{process}/iso-coverage', [ProcessController::class, 'addIsoCoverage']);
Route::get('iso-coverage-matrix', [ProcessController::class, 'isoCoverageMatrix']);
Route::get('system-settings', [ProcessController::class, 'systemSettings']);

// Revues de processus (§9.2)
Route::prefix('processes/{process}/reviews')->group(function () {
    Route::get('current', [ProcessReviewController::class, 'current']);
    Route::get('current/linked-data', [ProcessReviewController::class, 'linkedData']);
    Route::post('current/linked-data/actions', [ProcessReviewController::class, 'createLinkedAction']);
    Route::put('current', [ProcessReviewController::class, 'upsertCurrent']);
    Route::post('current/close', [ProcessReviewController::class, 'closeCurrent']);
    Route::post('current/submit-suggestions', [ProcessReviewController::class, 'submitSuggestionsToRq']);
    Route::get('current/export-pdf', [ProcessReviewController::class, 'exportCurrentPdf']);
    Route::get('current/export-docx', [ProcessReviewController::class, 'exportCurrentDocx']);
});
Route::get('processes/{process}/metrics', [ProcessReviewController::class, 'getMetrics']);
Route::get('processes/{process}/reviews/dashboard-actions', [ProcessController::class, 'listReviewDashboardActions']);

// Séquences de la fiche processus
Route::post('processes/{process}/sequences', [ProcessController::class, 'addSequence']);
Route::put('processes/{process}/sequences/{sequenceId}', [ProcessController::class, 'updateSequence']);
Route::delete('processes/{process}/sequences/{sequenceId}', [ProcessController::class, 'deleteSequence']);

// Versions de processus
Route::get('processes/{process}/versions', [ProcessController::class, 'getVersions']);
Route::post('processes/{process}/versions', [ProcessController::class, 'createVersion']);
Route::post('processes/{process}/versions/{versionId}/verify', [ProcessController::class, 'verifyVersion']);
Route::post('processes/{process}/versions/{versionId}/approve', [ProcessController::class, 'approveVersion']);

// Activités, Ressources et Interactions
Route::apiResource('activities', ActivityController::class);
Route::apiResource('process-resources', ProcessResourceController::class);
Route::apiResource('process-interactions', ProcessInteractionController::class);

