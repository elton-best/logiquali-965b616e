<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Hse\Controllers\EnvironmentalAspectController;
use App\Modules\Hse\Controllers\DuerpController;
use App\Modules\Support\Controllers\HabilitationController;
use App\Modules\Hse\Controllers\EpiController;
use App\Modules\Hse\Controllers\VerificationReglementaireController;
use App\Modules\Hse\Controllers\AspectEnvironnementalController;
use App\Modules\Hse\Controllers\ObligationConformiteEnvironnementaleController;
use App\Modules\Hse\Controllers\ConsommationEnergieController;
use App\Modules\Hse\Controllers\IpeController;
use App\Modules\Hse\Controllers\ComplianceObligationAspectController;
use App\Modules\Hse\Controllers\ComplianceObligationController;
use App\Modules\Hse\Controllers\ApplicableRequirementController;

// ============================================================
// MODULE HSE & NORMES ASSOCIÉES (ISO 14001, ISO 45001, ISO 50001)
// ============================================================

// --- 1. ISO 14001 : Environnement & AES ---
Route::get('environmental-aspects/statistics', [EnvironmentalAspectController::class, 'statistics']);
Route::get('environmental-aspects/export-xlsx', [EnvironmentalAspectController::class, 'exportXlsx']);
Route::apiResource('environmental-aspects', EnvironmentalAspectController::class);

Route::get('aspects-environnementaux/stats', [AspectEnvironnementalController::class, 'stats']);
Route::get('aspects-environnementaux/matrice', [AspectEnvironnementalController::class, 'matrice']);
Route::apiResource('aspects-environnementaux', AspectEnvironnementalController::class);

Route::get('obligations-conformite-environnementales/alertes', [ObligationConformiteEnvironnementaleController::class, 'alertes']);
Route::get('obligations-conformite-environnementales/stats', [ObligationConformiteEnvironnementaleController::class, 'stats']);
Route::apiResource('obligations-conformite-environnementales', ObligationConformiteEnvironnementaleController::class, [
    'parameters' => ['obligations-conformite-environnementales' => 'obligation']
]);

// --- 2. ISO 45001 : Santé & Sécurité au Travail (DUERP, Habilitations, EPI, VGP) ---
// DUERP & Dangers (Canevas INRS)
Route::get('duerp/inrs-families', [DuerpController::class, 'inrsFamilies']);
Route::get('duerp/export-xlsx', [DuerpController::class, 'exportXlsx']);
Route::post('duerp/{id}/submit-verification', [DuerpController::class, 'submitForVerification']);
Route::post('duerp/{id}/approve', [DuerpController::class, 'approveByCeo']);
Route::post('duerp/{id}/dangers', [DuerpController::class, 'storeDanger']);
Route::put('duerp/{id}/dangers/{dangerId}', [DuerpController::class, 'updateDanger']);
Route::delete('duerp/{id}/dangers/{dangerId}', [DuerpController::class, 'destroyDanger']);
Route::apiResource('duerp', DuerpController::class);

// Habilitations
Route::get('habilitations/expires-soon', [HabilitationController::class, 'expiresSoon']);
Route::get('habilitations/export', [HabilitationController::class, 'export']);
Route::get('habilitations/stats', [HabilitationController::class, 'stats']);
Route::post('habilitations/{habilitation}/renew', [HabilitationController::class, 'renew']);
Route::apiResource('habilitations', HabilitationController::class);
Route::get('habilitations/alertes', [HabilitationController::class, 'expiresSoon'])
    ->middleware('legacy.deprecated:habilitations/expires-soon,2026-12-31');

// EPI (Équipements de Protection Individuelle)
Route::get('epi/catalogue', [EpiController::class, 'catalogue']);
Route::post('epi/catalogue', [EpiController::class, 'storeCatalogue']);
Route::get('epi/stocks', [EpiController::class, 'stocks']);
Route::post('epi/stocks', [EpiController::class, 'storeStock']);
Route::put('epi/stocks/{id}', [EpiController::class, 'updateStock']);
Route::get('epi/stocks/{stockId}/mouvements', [EpiController::class, 'mouvements']);
Route::post('epi/mouvements', [EpiController::class, 'storeMouvement']);
Route::get('epi/attributions', [EpiController::class, 'attributions']);
Route::post('epi/attributions', [EpiController::class, 'storeAttribution']);
Route::get('epi/stats', [EpiController::class, 'stats']);

// VGP (Vérifications Générales Périodiques)
Route::get('verifications-reglementaires/alertes', [VerificationReglementaireController::class, 'alertes']);
Route::get('verifications-reglementaires/stats', [VerificationReglementaireController::class, 'stats']);
Route::apiResource('verifications-reglementaires', VerificationReglementaireController::class);

// --- 3. ISO 50001 : Énergie ---
Route::get('consommations-energie/stats', [ConsommationEnergieController::class, 'stats']);
Route::post('consommations-energie/import-file', [ConsommationEnergieController::class, 'importCsv']);
Route::post('consommations-energie/import-csv', [ConsommationEnergieController::class, 'importCsv']);
Route::apiResource('consommations-energie', ConsommationEnergieController::class);

Route::get('ipe/stats', [IpeController::class, 'stats']);
Route::post('ipe/{id}/valeurs', [IpeController::class, 'addValeur']);
Route::get('ipe/{id}/valeurs', [IpeController::class, 'valeurs']);
Route::get('ipe/{id}/tendance', [IpeController::class, 'tendance']);
Route::apiResource('ipe', IpeController::class);

// --- 4. Exigences et Obligations de Conformité Légale (§6.1.3) ---
Route::get('compliance-obligation-aspects', [ComplianceObligationAspectController::class, 'index']);
Route::post('compliance-obligation-aspects', [ComplianceObligationAspectController::class, 'store']);
Route::put('compliance-obligation-aspects/{id}', [ComplianceObligationAspectController::class, 'update']);
Route::get('compliance-obligations/template', [ComplianceObligationController::class, 'downloadTemplate']);
Route::get('compliance-obligations/export-xlsx', [ComplianceObligationController::class, 'exportXlsx']);
Route::post('compliance-obligations/import-xlsx', [ComplianceObligationController::class, 'importXlsx']);
Route::apiResource('compliance-obligations', ComplianceObligationController::class)->except(['show']);

Route::get('product-service-requirements-obligation-aspects', [ComplianceObligationAspectController::class, 'index']);
Route::post('product-service-requirements-obligation-aspects', [ComplianceObligationAspectController::class, 'store']);
Route::put('product-service-requirements-obligation-aspects/{id}', [ComplianceObligationAspectController::class, 'update']);
Route::get('prod-req-obligations/template', [ComplianceObligationController::class, 'downloadTemplate']);
Route::get('prod-req-obligations/export-xlsx', [ComplianceObligationController::class, 'exportXlsx']);
Route::post('prod-req-obligations/import-xlsx', [ComplianceObligationController::class, 'importXlsx']);
Route::apiResource('prod-req-obligations', ComplianceObligationController::class, [
    'parameters' => ['prod-req-obligations' => 'obligation']
])->except(['show']);

Route::apiResource('applicable-requirements', ApplicableRequirementController::class);

