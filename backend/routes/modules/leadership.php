<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Leadership\Controllers\QhsePolicyController;
use App\Modules\Leadership\Controllers\StrategicAxisController;
use App\Modules\Leadership\Controllers\OrgChartController;
use App\Modules\Leadership\Controllers\ResponsibilityController;
use App\Modules\Leadership\Controllers\RoleController;
use App\Modules\Leadership\Controllers\JobDescriptionController;
use App\Modules\Leadership\Controllers\JobDescriptionImportController;
use App\Modules\Leadership\Controllers\TeamMemberController;

// ============================================================
// CHAPITRE 5 : LEADERSHIP & ENGAGEMENT
// ============================================================

// Politique QHSE (§5.2)
Route::get('qhse-policies/current', [QhsePolicyController::class, 'current']);
Route::get('qhse-policies/history', [QhsePolicyController::class, 'history']);
Route::get('qhse-policies/{qhsePolicy}/export-pdf', [QhsePolicyController::class, 'exportPdf']);
Route::get('qhse-policies/{qhsePolicy}/export-docx', [QhsePolicyController::class, 'exportDocx']);
Route::post('qhse-policies/{qhsePolicy}/generate-draft', [QhsePolicyController::class, 'generateDraftDocx']);
Route::post('qhse-policies/{qhsePolicy}/validate', [QhsePolicyController::class, 'validate']);
Route::post('qhse-policies/{qhsePolicy}/submit', [QhsePolicyController::class, 'submitForReview']);
Route::apiResource('qhse-policies', QhsePolicyController::class);

// Axes Stratégiques (§5.1)
Route::apiResource('strategic-axes', StrategicAxisController::class);

// Organigramme (§5.3)
Route::post('org-chart/upload', [OrgChartController::class, 'upload']);
Route::get('org-chart/current', [OrgChartController::class, 'current']);
Route::delete('org-chart/{id}', [OrgChartController::class, 'destroy']);

// Fiches de poste (§5.3 / §7.2)
Route::apiResource('job-descriptions', JobDescriptionController::class);
Route::get('job-descriptions/{jobDescription}/pdf', [JobDescriptionController::class, 'downloadPdf']);
Route::post('job-descriptions/{jobDescription}/generate-draft', [JobDescriptionController::class, 'generateDraftDocx']);

// Import Fiches de poste
Route::prefix('job-descriptions/import')->group(function () {
    Route::post('upload', [JobDescriptionImportController::class, 'upload']);
    Route::get('preview/{importId}', [JobDescriptionImportController::class, 'preview']);
    Route::post('confirm/{importId}', [JobDescriptionImportController::class, 'confirm']);
    Route::get('template', [JobDescriptionImportController::class, 'downloadTemplate']);
    Route::get('logs', [JobDescriptionImportController::class, 'logs']);
    Route::get('logs/{logId}/errors', [JobDescriptionImportController::class, 'downloadErrorReport']);
});

// Responsabilités & Autorités (§5.3)
Route::get('responsibilities/export-pdf', [ResponsibilityController::class, 'exportPdf']);
Route::get('responsibilities/export-docx', [ResponsibilityController::class, 'exportDocx']);
Route::post('responsibilities/generate-draft', [ResponsibilityController::class, 'generateDraftDocx']);
Route::apiResource('responsibilities', ResponsibilityController::class);

// Équipe et Rôles
Route::apiResource('team-members', TeamMemberController::class);
Route::apiResource('roles', RoleController::class)->middleware([
    'index' => 'permission:roles.read',
    'show' => 'permission:roles.read',
    'store' => 'permission:roles.create',
    'update' => 'permission:roles.update',
    'destroy' => 'permission:roles.delete',
]);
Route::post('roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
    ->middleware(['permission:roles.update', 'validate.permission.assignment']);

