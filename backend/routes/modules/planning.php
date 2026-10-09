<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Processes\Controllers\ProcessController;
use App\Modules\Planning\Controllers\ObjectiveController;
use App\Modules\Planning\Controllers\RiskController;
use App\Modules\Planning\Controllers\OpportunityController;
use App\Modules\Planning\Controllers\SmPlanHierarchicalController;
use App\Modules\Planning\Controllers\OperationalPlanningController;
use App\Modules\Planning\Controllers\PlanSMController;
use App\Modules\Planning\Controllers\ModificationController;
use App\Modules\Planning\Controllers\MethodologyGuideController;
use App\Modules\Improvement\Controllers\PlanController;

// ============================================================
// CHAPITRE 6 : PLANIFICATION & GESTION DES RISQUES
// ============================================================

// --- Guides d'utilisation & Matrices méthodologiques (ISO 9001 / ISO 14001 / ISO 45001) ---
Route::get('methodology-guides', [MethodologyGuideController::class, 'index']);
Route::get('methodology-guides/{section}', [MethodologyGuideController::class, 'show']);
Route::get('methodology/matrices', [MethodologyGuideController::class, 'index']);
Route::get('methodology/matrices/{section}', [MethodologyGuideController::class, 'show']);

// --- 6.1 Risques & Opportunités ---
Route::post('processes/{process}/risks-opportunities', [ProcessController::class, 'addRiskOpportunity']);

// Endpoints canoniques R&O
Route::get('risks-opportunities', [ProcessController::class, 'listRisksOpportunities']);
Route::get('risks-opportunities/export-xlsx', [ProcessController::class, 'exportRisksOpportunitiesXlsx']);
Route::get('risks-opportunities/{riskOpportunity}', [ProcessController::class, 'showRiskOpportunity']);
Route::put('risks-opportunities/{riskOpportunity}', [ProcessController::class, 'updateRiskOpportunity']);
Route::delete('risks-opportunities/{riskOpportunity}', [ProcessController::class, 'deleteRiskOpportunity']);
Route::patch('risks-opportunities/{riskOpportunity}/evaluation', [ProcessController::class, 'updateRiskEvaluation']);

// Alias dépréciés
Route::get('process-risks-opportunities', [ProcessController::class, 'listRisksOpportunities'])
    ->middleware('legacy.deprecated:risks-opportunities,2026-12-31');
Route::get('process-risks-opportunities/{riskOpportunity}', [ProcessController::class, 'showRiskOpportunity'])
    ->middleware('legacy.deprecated:risks-opportunities/{riskOpportunity},2026-12-31');
Route::put('process-risks-opportunities/{riskOpportunity}', [ProcessController::class, 'updateRiskOpportunity'])
    ->middleware('legacy.deprecated:risks-opportunities/{riskOpportunity},2026-12-31');
Route::delete('process-risks-opportunities/{riskOpportunity}', [ProcessController::class, 'deleteRiskOpportunity'])
    ->middleware('legacy.deprecated:risks-opportunities/{riskOpportunity},2026-12-31');

// Risques
Route::get('risks/export-docx', [RiskController::class, 'exportDocx']);
Route::get('risks/heatmap', [RiskController::class, 'heatmap']);
Route::get('risks/matrix', [RiskController::class, 'getMatrix']);
Route::get('risks/statistics', [RiskController::class, 'statistics']);
Route::post('risks/{id}/assess', [RiskController::class, 'assess']);
Route::post('risks/{id}/treat', [RiskController::class, 'treat']);
Route::post('risks/{id}/mitigate', [RiskController::class, 'mitigate']);
Route::apiResource('risks', RiskController::class);
Route::apiResource('opportunities', OpportunityController::class);

// --- 6.2 Objectifs QHSE & Moteur de calcul ---
Route::get('processes/{process}/objectives', [ProcessController::class, 'getObjectives']);
Route::get('processus/{process}/objectifs', [ProcessController::class, 'getObjectives']);
Route::post('processes/{process}/objectives', [ProcessController::class, 'addObjective']);
Route::put('processes/{process}/objectives/{objectiveId}', [ProcessController::class, 'updateObjective']);
Route::delete('processes/{process}/objectives/{objectiveId}', [ProcessController::class, 'deleteObjective']);
Route::get('objectives/system-summary', [ProcessController::class, 'systemObjectivesSummary']);

Route::prefix('objectives')->group(function () {
    Route::post('{id}/progress', [ObjectiveController::class, 'updateProgress']);
    Route::post('{id}/milestone', [ObjectiveController::class, 'addMilestone']);
});
Route::get('objectives/export-xlsx', [ObjectiveController::class, 'exportXlsx']);
Route::apiResource('objectives', ObjectiveController::class);

// --- 6.3 Plan du Système de Management & Modifications ---
// Plan du SM Hiérarchique (Canevas de Ben : Activité -> Sous-activité -> Action)
Route::prefix('sm-plan-hierarchy')->group(function () {
    Route::get('/', [SmPlanHierarchicalController::class, 'index']);
    Route::get('/export-xlsx', [SmPlanHierarchicalController::class, 'exportXlsx']);
    Route::post('/activities', [SmPlanHierarchicalController::class, 'storeActivity']);
    Route::put('/activities/{activity}', [SmPlanHierarchicalController::class, 'updateActivity']);
    Route::delete('/activities/{activity}', [SmPlanHierarchicalController::class, 'destroyActivity']);
    Route::post('/sub-activities', [SmPlanHierarchicalController::class, 'storeSubActivity']);
    Route::put('/sub-activities/{subActivity}', [SmPlanHierarchicalController::class, 'updateSubActivity']);
    Route::delete('/sub-activities/{subActivity}', [SmPlanHierarchicalController::class, 'destroySubActivity']);
    Route::post('/actions', [SmPlanHierarchicalController::class, 'storeAction']);
    Route::put('/actions/{action}', [SmPlanHierarchicalController::class, 'updateAction']);
    Route::delete('/actions/{action}', [SmPlanHierarchicalController::class, 'destroyAction']);
    Route::post('/actions/{action}/reschedule', [SmPlanHierarchicalController::class, 'rescheduleAction']);
});

Route::get('sm-plan/export-xlsx', [SmPlanHierarchicalController::class, 'exportXlsx']);

// Vue calendrier global du plan SM
Route::get('/plan-sm', [PlanSMController::class, 'index']);

// Demandes de modifications du SM (Brouillon -> RQ -> CEO - REQ-6.3-01..05)
Route::post('modifications/{id}/submit-verification', [ModificationController::class, 'submitForVerification']);
Route::post('modifications/{id}/verify-rq', [ModificationController::class, 'verifyByRq']);
Route::post('modifications/{id}/approve-ceo', [ModificationController::class, 'approveByCeo']);
Route::post('modifications/{id}/record-results', [ModificationController::class, 'recordResults']);
Route::apiResource('modifications', ModificationController::class);
Route::get('plans/export-xlsx', [PlanController::class, 'exportXlsx']);
Route::apiResource('plans', PlanController::class);

// Planification Opérationnelle (§8.1)
Route::get('operational-planning/overview', [OperationalPlanningController::class, 'overview']);
Route::post('operational-projects', [OperationalPlanningController::class, 'storeProject']);
Route::put('operational-projects/{project}', [OperationalPlanningController::class, 'updateProject']);
Route::delete('operational-projects/{project}', [OperationalPlanningController::class, 'destroyProject']);
Route::post('operational-projects/{project}/evidences', [OperationalPlanningController::class, 'uploadProjectEvidence']);
Route::delete('operational-projects/{project}/evidences/{index}', [OperationalPlanningController::class, 'deleteProjectEvidence']);
Route::post('operational-projects/{project}/activities', [OperationalPlanningController::class, 'storeActivity']);
Route::put('operational-project-activities/{activity}', [OperationalPlanningController::class, 'updateActivity']);
Route::delete('operational-project-activities/{activity}', [OperationalPlanningController::class, 'destroyActivity']);
Route::post('operational-project-activities/{activity}/tasks', [OperationalPlanningController::class, 'storeTask']);
Route::put('operational-project-tasks/{task}', [OperationalPlanningController::class, 'updateTask']);
Route::delete('operational-project-tasks/{task}', [OperationalPlanningController::class, 'destroyTask']);

