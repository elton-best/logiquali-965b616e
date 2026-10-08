<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Evaluation\Controllers\IndicateurController;
use App\Modules\Evaluation\Controllers\AuditController;
use App\Modules\Evaluation\Controllers\AuditProgramController;
use App\Modules\Evaluation\Controllers\StakeholderController;
use App\Modules\Leadership\Controllers\ApplicationScopeController;
use App\Modules\Leadership\Controllers\ContextController;
use App\Modules\Evaluation\Controllers\SatisfactionSurveyController;
use App\Modules\Evaluation\Controllers\ClientSatisfactionFormController;
use App\Modules\Support\Controllers\EmployeeEvaluationController;
use App\Modules\Support\Controllers\ProviderPartnerController;
use App\Modules\Evaluation\Controllers\EvaluationCriteriaController;
use App\Modules\Evaluation\Controllers\EvaluationRequestController;
use App\Modules\Evaluation\Controllers\ManagementReviewController;

// ============================================================
// CHAPITRE 9 : ÉVALUATION DES PERFORMANCES (AUDITS, KPIS, REVUES, SATISFACTION)
// ============================================================

// --- Indicateurs de performance (KPI - §9.1) ---
Route::prefix('indicateurs')->group(function () {
    Route::get('{id}/trend', [IndicateurController::class, 'trend']);
    Route::get('{id}/chart-data', [IndicateurController::class, 'getChartData']);
    Route::get('{id}/check-thresholds', [IndicateurController::class, 'checkThresholds']);
    Route::post('{id}/value', [IndicateurController::class, 'addValue']);
});
Route::apiResource('indicateurs', IndicateurController::class);

// --- Audits internes (§9.2 / ISO 19011) ---
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
    Route::put('{id}/complements', [AuditController::class, 'updateComplements']);
    Route::put('{id}/norm-inputs', [AuditController::class, 'updateNormInputs']);
    Route::get('{id}/synthesis', [AuditController::class, 'generateSynthesis']);
    Route::post('{id}/reminders', [AuditController::class, 'scheduleReminders']);
});
Route::apiResource('audits', AuditController::class);

// Programmes d'audit
Route::prefix('audit-programs')->group(function () {
    Route::post('{id}/validate', [AuditProgramController::class, 'validate']);
    Route::post('{id}/generate-from-risks', [AuditProgramController::class, 'generateFromRisks']);
    Route::get('{id}/export-calendar', [AuditProgramController::class, 'exportCalendar']);
    Route::get('{id}/statistics', [AuditProgramController::class, 'statistics']);
});
Route::apiResource('audit-programs', AuditProgramController::class);

// --- Contexte, PIP et Domaine d'application (§4.1, §4.2, §4.3) ---
Route::get('stakeholders/export-docx', [StakeholderController::class, 'exportDocx']);
Route::post('stakeholders/generate-draft', [StakeholderController::class, 'generateDraftDocx']);
Route::apiResource('stakeholders', StakeholderController::class);

Route::get('application-scopes/{application_scope}/export-docx', [ApplicationScopeController::class, 'exportDocx']);
Route::post('application-scopes/{application_scope}/generate-draft', [ApplicationScopeController::class, 'generateDraftDocx']);
Route::apiResource('application-scopes', ApplicationScopeController::class);

Route::get('contexts/export-docx', [ContextController::class, 'exportDocx']);
Route::post('contexts/generate-draft', [ContextController::class, 'generateDraftDocx']);
Route::apiResource('contexts', ContextController::class);

// --- Satisfaction & Évaluations PIP (§9.1.2) ---
Route::apiResource('satisfaction-surveys', SatisfactionSurveyController::class);

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

Route::apiResource('employee-evaluations', EmployeeEvaluationController::class);
Route::get('employee-evaluations/{employeeEvaluation}/export-pdf', [EmployeeEvaluationController::class, 'exportPdf']);
Route::get('employee-evaluations/{employeeEvaluation}/export-anonymous-pdf', [EmployeeEvaluationController::class, 'exportAnonymousPdf']);
Route::post('provider-evaluations/export-pdf', [EmployeeEvaluationController::class, 'exportProviderPdf']);
Route::get('templates/procedure-evaluation-pip', [EmployeeEvaluationController::class, 'downloadProcedureTemplate']);

// Prestataires externes (ISO §8.4)
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

// Critères et demandes d'évaluation
Route::prefix('evaluation-criteria')->group(function () {
    Route::get('defaults', [EvaluationCriteriaController::class, 'defaults']);
    Route::post('initialize-defaults', [EvaluationCriteriaController::class, 'initializeDefaults']);
    Route::post('reorder', [EvaluationCriteriaController::class, 'reorder']);
    Route::get('categories', [EvaluationCriteriaController::class, 'categories']);
    Route::post('{id}/duplicate', [EvaluationCriteriaController::class, 'duplicate']);
    Route::post('{id}/toggle-active', [EvaluationCriteriaController::class, 'toggleActive']);
});
Route::apiResource('evaluation-criteria', EvaluationCriteriaController::class);

Route::prefix('evaluation-requests')->group(function () {
    Route::get('statistics', [EvaluationRequestController::class, 'statistics']);
    Route::get('{id}/responses', [EvaluationRequestController::class, 'responses']);
    Route::post('{id}/send', [EvaluationRequestController::class, 'send']);
    Route::post('{id}/reminder', [EvaluationRequestController::class, 'sendReminder']);
    Route::post('{id}/cancel', [EvaluationRequestController::class, 'cancel']);
});
Route::apiResource('evaluation-requests', EvaluationRequestController::class);

// --- Revue de direction (§9.3) ---
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

