<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SsoLoginController;
use App\Http\Controllers\QRCodeVerificationController;

/*
|--------------------------------------------------------------------------
| API Routes — SMI LOGIQUALI / BESTQHSE
|--------------------------------------------------------------------------
|
| Les routes de l'API sont découpées de manière modulaire par domaine métier :
| - modules/auth.php        : Authentification publique, MFA, notifications, liens publics
| - modules/superadmin.php  : Administration globale de la plateforme SaaS
| - modules/enterprise.php  : Entreprises, sites, abonnements, utilisateurs, sécurité
| - modules/leadership.php  : Chapitre 5 (Politique, axes stratégiques, organigramme, fiches de poste)
| - modules/processes.php   : Chapitre 4 & 8 (Processus, cartographie, séquences, revues processus)
| - modules/planning.php    : Chapitre 6 (Objectifs, R&O, Plan SM canevas de Ben, modifications)
| - modules/hse.php         : ISO 14001 (AES), ISO 45001 (DUERP, EPI, VGP), ISO 50001 (Énergie)
| - modules/support.php     : Chapitre 7 (Gestion documentaire, équipements, formations, communication)
| - modules/evaluation.php  : Chapitre 9 (Audits ISO 19011, KPIs, enquêtes PIP, revues de direction)
| - modules/improvement.php : Chapitre 10 (Non-conformités, réclamations, plans d'actions, dashboards)
| - modules/clientb.php     : Espace Client B (portail consommateur final)
|
*/

// SSO Supervision : la plateforme cible valide le token et connecte l'utilisateur
Route::get('/sso/login', [SsoLoginController::class, 'login']);

// Vérification publique par QR Code (sans authentification)
Route::get('/verify/{hash}', [QRCodeVerificationController::class, 'verify']);

// ============================================================
// API V1
// ============================================================
Route::prefix('/v1/')->group(function () {

    // 1. Authentification, formulaires publics, notifications & RGPD
    require __DIR__ . '/modules/auth.php';

    // 2. Espace SuperAdmin
    require __DIR__ . '/modules/superadmin.php';

    // 3. Utilisateur connecté courant
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('auth:sanctum');

    // 4. Modules métier sécurisés (Entreprise / Filiale / Site)
    Route::middleware([
        'auth:sanctum',
        'force.password',
        'force.signature',
        'force.company',
        'check.subscription',
        'ensure.ownership',
    ])->group(function () {
        require __DIR__ . '/modules/enterprise.php';
        require __DIR__ . '/modules/leadership.php';
        require __DIR__ . '/modules/processes.php';
        require __DIR__ . '/modules/planning.php';
        require __DIR__ . '/modules/hse.php';
        require __DIR__ . '/modules/support.php';
        require __DIR__ . '/modules/evaluation.php';
        require __DIR__ . '/modules/improvement.php';
        require __DIR__ . '/modules/clientb.php';
    });
});
