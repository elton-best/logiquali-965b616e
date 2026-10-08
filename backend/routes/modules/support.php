<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Support\Controllers\DocumentController;
use App\Modules\Support\Controllers\DocumentCollaborationController;
use App\Modules\Support\Controllers\DocumentWorkflowController;
use App\Modules\Support\Controllers\DocumentTypeConfigurationController;
use App\Modules\Support\Controllers\DocumentCodeWorkflowController;
use App\Modules\Support\Controllers\DocumentImportController;
use App\Modules\Support\Controllers\NomenclatureTemplateController;
use App\Modules\Support\Controllers\DocumentTypeCatalogController;
use App\Modules\Processes\Controllers\ProcessCatalogController;
use App\Modules\Support\Controllers\DocumentCodeRecyclingController;
use App\Modules\Support\Controllers\CodificationController;
use App\Modules\Support\Controllers\EquipementController;
use App\Modules\Support\Controllers\EquipementTransferController;
use App\Modules\Support\Controllers\MaintenanceController;
use App\Modules\Support\Controllers\FormationController;
use App\Modules\Support\Controllers\FormationInternalEvaluationController;
use App\Modules\Support\Controllers\TrainingPlanController;
use App\Modules\Support\Controllers\CompetenceMatrixController;
use App\Modules\Support\Controllers\CommunicationController;
use App\Modules\Support\Controllers\CommunicationPlanController;

// ============================================================
// CHAPITRE 7 : SUPPORT (DOCUMENTATION, RESSOURCES, COMPÉTENCES, COMMUNICATION)
// ============================================================

// --- 7.5 Maîtrise des Informations Documentées ---

// Collaboration temps réel sur documents
Route::post('documents/{document}/collaboration/join', [DocumentCollaborationController::class, 'join']);
Route::post('documents/{document}/collaboration/leave', [DocumentCollaborationController::class, 'leave']);
Route::get('documents/{document}/collaboration/state', [DocumentCollaborationController::class, 'getState']);
Route::post('documents/{document}/collaboration/sync', [DocumentCollaborationController::class, 'syncContent']);
Route::post('documents/{document}/collaboration/save', [DocumentCollaborationController::class, 'save']);
Route::get('documents/{document}/collaboration/users', [DocumentCollaborationController::class, 'getActiveUsers']);

Route::get('documents/preview-code', [DocumentController::class, 'previewCode']);
Route::get('documents/active-norms', [DocumentController::class, 'getActiveNorms']);
Route::get('documents/export-inventory', [DocumentController::class, 'exportInventory']);

// Pyramide documentaire
Route::get('documents/pyramide/export/{level}', [DocumentController::class, 'exportByLevel']);
Route::get('documents/pyramide/export-complete', [DocumentController::class, 'exportCompletePyramid']);
Route::get('documents/pyramide/stats', [DocumentController::class, 'getPyramideStats']);
Route::get('documents/stats', [DocumentController::class, 'getStats']);
Route::get('documents/templates/politique-qualite', [DocumentController::class, 'downloadPolitiqueTemplate']);
Route::get('documents/templates/manuel-qualite', [DocumentController::class, 'downloadManuelTemplate']);

// Workflow de validation des documents
Route::post('documents/{document}/confirm-code', [DocumentWorkflowController::class, 'confirmCode'])
    ->middleware('document.workflow.rate.limit');
Route::post('documents/{document}/verify', [DocumentWorkflowController::class, 'verify'])
    ->middleware(['permission:verify_documents', 'document.workflow.rate.limit']);
Route::post('documents/{document}/approve', [DocumentWorkflowController::class, 'approve'])
    ->middleware(['permission:approve_documents', 'document.workflow.rate.limit']);
Route::post('documents/{document}/reject', [DocumentWorkflowController::class, 'reject'])
    ->middleware('document.workflow.rate.limit');
Route::post('documents/{document}/confirm-rejection-decision', [DocumentWorkflowController::class, 'confirmRejectionDecision'])
    ->middleware('document.workflow.rate.limit');
Route::get('documents/{document}/workflow-history', [DocumentWorkflowController::class, 'getHistory']);
Route::get('documents/generated-source-state', [DocumentWorkflowController::class, 'generatedSourceState']);
Route::post('documents/{document}/delegate', [DocumentWorkflowController::class, 'delegate'])
    ->middleware('document.workflow.rate.limit');
Route::post('documents/{document}/send-reminder', [DocumentWorkflowController::class, 'sendReminder'])
    ->middleware('document.workflow.rate.limit');
Route::get('workflow/pending-documents', [DocumentWorkflowController::class, 'getPendingDocuments']);
Route::get('workflow/stats', [DocumentWorkflowController::class, 'getWorkflowStats']);

// CRUD Documents
Route::apiResource('documents', DocumentController::class);
Route::post('documents/{document}/versions', [DocumentController::class, 'uploadVersion']);
Route::get('documents/{document}/download/{versionId?}', [DocumentController::class, 'download']);
Route::get('documents/{document}/preview', [DocumentController::class, 'preview']);
Route::post('documents/{document}/submit-for-approval', [DocumentController::class, 'submitForApproval']);
Route::get('documents/{document}/workflow-integrity', [DocumentController::class, 'workflowIntegrity']);
Route::post('documents/{document}/archive', [DocumentController::class, 'archive']);
Route::post('document-approvals/{approval}/process', [DocumentController::class, 'approve']);

// Signatures et recyclage de codes
Route::post('document-signatures/sign', [\App\Http\Controllers\Api\V1\DocumentSignatureController::class, 'sign']);
Route::get('document-signatures', [\App\Http\Controllers\Api\V1\DocumentSignatureController::class, 'index']);
Route::get('document-signatures/check-signed', [\App\Http\Controllers\Api\V1\DocumentSignatureController::class, 'checkSigned']);

Route::get('document-codes/check-availability', [DocumentCodeRecyclingController::class, 'checkAvailability']);
Route::post('document-codes/release', [DocumentCodeRecyclingController::class, 'releaseCode']);
Route::get('document-codes/available', [DocumentCodeRecyclingController::class, 'availableCodes']);
Route::get('document-codes/{code}/history', [DocumentCodeRecyclingController::class, 'codeHistory']);

// Nomenclatures et configurations types
Route::prefix('document-type-configurations')->group(function () {
    Route::get('/', [DocumentTypeConfigurationController::class, 'index']);
    Route::post('/', [DocumentTypeConfigurationController::class, 'store']);
    Route::get('/visibility-stats', [DocumentTypeConfigurationController::class, 'visibilityStats']);
    Route::get('/advanced-filters', [DocumentTypeConfigurationController::class, 'advancedFilters']);
    Route::post('/preview-code', [DocumentTypeConfigurationController::class, 'previewCode']);
    Route::post('/validate-structure', [DocumentTypeConfigurationController::class, 'validateStructure']);
    Route::get('/{id}', [DocumentTypeConfigurationController::class, 'show']);
    Route::put('/{id}', [DocumentTypeConfigurationController::class, 'update']);
    Route::delete('/{id}', [DocumentTypeConfigurationController::class, 'destroy']);
    Route::post('/{id}/duplicate', [DocumentTypeConfigurationController::class, 'duplicate']);
    Route::post('/{id}/toggle-active', [DocumentTypeConfigurationController::class, 'toggleActive']);
    Route::post('/{id}/share-with-sites', [DocumentTypeConfigurationController::class, 'shareWithSites']);
    Route::post('/{id}/share-with-enterprises', [DocumentTypeConfigurationController::class, 'shareWithEnterprises']);
    Route::get('/{id}/available-sites-for-sharing', [DocumentTypeConfigurationController::class, 'availableSitesForSharing']);
    Route::get('/{id}/available-enterprises-for-sharing', [DocumentTypeConfigurationController::class, 'availableEnterprisesForSharing']);
});

Route::prefix('document-code-workflow')->group(function () {
    Route::get('/stats', [DocumentCodeWorkflowController::class, 'getWorkflowStats']);
    Route::get('/pending-verification', [DocumentCodeWorkflowController::class, 'pendingVerification']);
    Route::get('/pending-approval', [DocumentCodeWorkflowController::class, 'pendingApproval']);
    Route::post('/documents/{document}/verify', [DocumentCodeWorkflowController::class, 'verifyCode']);
    Route::post('/documents/{document}/activate', [DocumentCodeWorkflowController::class, 'activateCode']);
    Route::post('/documents/{document}/release', [DocumentCodeWorkflowController::class, 'releaseCode']);
});

Route::prefix('document-import')->group(function () {
    Route::post('/analyze', [DocumentImportController::class, 'analyze']);
    Route::post('/preview', [DocumentImportController::class, 'preview']);
    Route::post('/execute', [DocumentImportController::class, 'executeMigration']);
});

Route::get('document-imports/nomenclature-schema', [DocumentImportController::class, 'nomenclatureSchema']);
Route::get('document-imports/template', [DocumentImportController::class, 'downloadTemplate']);
Route::get('document-imports', [DocumentImportController::class, 'index']);
Route::post('document-imports/upload', [DocumentImportController::class, 'upload'])->middleware('throttle:10,1');
Route::post('document-imports/upload-files', [DocumentImportController::class, 'uploadFiles'])->middleware('throttle:10,1');
Route::post('document-imports/{importId}/validate', [DocumentImportController::class, 'validate']);
Route::post('document-imports/{importId}/execute', [DocumentImportController::class, 'execute']);
Route::post('document-imports/{importId}/rollback', [DocumentImportController::class, 'rollback']);
Route::get('document-imports/{importId}', [DocumentImportController::class, 'show']);
Route::delete('document-imports/{importId}', [DocumentImportController::class, 'destroy']);

Route::post('nomenclature-templates/validate-template', [NomenclatureTemplateController::class, 'validateTemplate']);
Route::post('nomenclature-templates/simulate-samples', [NomenclatureTemplateController::class, 'simulateSamples']);
Route::post('nomenclature-templates/preview-code', [NomenclatureTemplateController::class, 'previewCode']);
Route::apiResource('nomenclature-templates', NomenclatureTemplateController::class);
Route::apiResource('document-type-catalogs', DocumentTypeCatalogController::class)->except(['show']);
Route::apiResource('process-catalogs', ProcessCatalogController::class)->except(['show']);

// --- 7.1 Ressources : Équipements & Codification ---
Route::apiResource('codifications', CodificationController::class);

Route::get('equipements/prochain-indice', [EquipementController::class, 'prochainIndice']);
Route::post('equipements/import-file', [EquipementController::class, 'import']);
Route::post('equipements/import', [EquipementController::class, 'import']);
Route::get('equipements/{equipement}/transfers', [EquipementTransferController::class, 'equipmentTransfers'])->middleware('throttle:20,1');
Route::post('equipements/{equipement}/transfer', [EquipementTransferController::class, 'store'])->middleware('throttle:10,1');
Route::apiResource('equipements', EquipementController::class);

Route::get('transfers', [EquipementTransferController::class, 'index'])->middleware('throttle:20,1');
Route::get('transfers/{transfer}', [EquipementTransferController::class, 'show'])->middleware('throttle:20,1');
Route::put('transfers/{transfer}/verify', [EquipementTransferController::class, 'verify'])->middleware('throttle:10,1');
Route::get('enterprises/{enterprise}/transfer-reasons', [EquipementTransferController::class, 'getTransferReasons'])->middleware('throttle:20,1');

Route::get('maintenances/alertes', [MaintenanceController::class, 'alertes']);
Route::get('maintenances/template', [MaintenanceController::class, 'template']);
Route::get('maintenances/export', [MaintenanceController::class, 'export']);
Route::post('maintenances/import-file', [MaintenanceController::class, 'import']);
Route::post('maintenances/import', [MaintenanceController::class, 'import']);
Route::post('maintenances/{id}/suivre', [MaintenanceController::class, 'suivre']);
Route::apiResource('maintenances', MaintenanceController::class);

// --- 7.2 Compétences & Formations ---
Route::get('formations/stats', [FormationController::class, 'stats']);
Route::post('formations/import-file', [FormationController::class, 'importFile']);
Route::post('formations/{formation}/complete', [FormationController::class, 'complete']);
Route::post('formations/{formation}/reschedule', [FormationController::class, 'reschedule']);
Route::post('formations/{formation}/cancel', [FormationController::class, 'cancel']);
Route::post('formations/{formation}/proofs', [FormationController::class, 'uploadProof']);
Route::delete('formations/{formation}/proofs/{proof}', [FormationController::class, 'deleteProof']);
Route::get('formations/{formation}/internal-evaluation', [FormationInternalEvaluationController::class, 'show']);
Route::post('formations/{formation}/internal-evaluation', [FormationInternalEvaluationController::class, 'upsert']);
Route::delete('formations/{formation}/internal-evaluation', [FormationInternalEvaluationController::class, 'destroy']);
Route::apiResource('formations', FormationController::class);

Route::get('training-plans/template', [TrainingPlanController::class, 'template']);
Route::get('training-plans/{trainingPlan}/export-xlsx', [TrainingPlanController::class, 'exportXlsx']);
Route::post('training-plans/{trainingPlan}/sync', [TrainingPlanController::class, 'sync']);
Route::apiResource('training-plans', TrainingPlanController::class);

Route::get('competence-matrix', [CompetenceMatrixController::class, 'getMatrix']);
Route::get('competence-matrix/gap-analysis', [CompetenceMatrixController::class, 'getGapAnalysis']);
Route::get('competence-matrix/training-plan', [CompetenceMatrixController::class, 'getTrainingPlan']);
Route::get('competence-matrix/export', [CompetenceMatrixController::class, 'export']);

// --- 7.4 Sensibilisation & Communication ---
Route::get('communications/stats', [CommunicationController::class, 'stats']);
Route::post('communications/import-file', [CommunicationController::class, 'importFile']);
Route::get('communications/template', [CommunicationController::class, 'template']);
Route::get('communications/export', [CommunicationController::class, 'export']);
Route::post('communications/{communication}/complete', [CommunicationController::class, 'complete']);
Route::post('communications/{communication}/reschedule', [CommunicationController::class, 'reschedule']);
Route::post('communications/{communication}/cancel', [CommunicationController::class, 'cancel']);
Route::post('communications/{communication}/proofs', [CommunicationController::class, 'uploadProof']);
Route::delete('communications/{communication}/proofs/{proof}', [CommunicationController::class, 'deleteProof']);
Route::apiResource('communications', CommunicationController::class);

Route::post('communication-plans/{communicationPlan}/sync', [CommunicationPlanController::class, 'sync']);
Route::apiResource('communication-plans', CommunicationPlanController::class);

