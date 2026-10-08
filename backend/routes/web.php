<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Callback SSO Supervision : reçoit ?sup_token=, valide, connecte et redirige vers le dashboard
Route::get('/sso-callback', function () {
    return view('sso-callback');
});

/*
|--------------------------------------------------------------------------
| Routes démo sidebar LOGIQUALI (Blade + Reicon)
|--------------------------------------------------------------------------
| Ces routes nommées correspondent à config/navigation.php (sections RÉELLES
| du projet : Information documentée, Pilotage, Évaluation, Sites & Équipes).
| Les href React fictifs (factures/devis/proformas/...) ne sont PAS utilisés.
| En production, remplacez les closures par vos contrôleurs.
| Middleware 'auth' : à adapter (Breeze/Jetstream/custom) selon votre auth web.
*/
Route::middleware(['web'])->group(function () {
    // Vue de démo générique : resources/views/dashboard/demo.blade.php.
    // NOTE : ajoutez 'auth' en production (Breeze/Jetstream/custom).
    $demo = fn () => view('dashboard.demo');

    // Racine : nom exact 'dashboard' (égalité stricte pour l'état actif).
    Route::get('/dashboard', $demo)->name('dashboard');

    Route::prefix('dashboard')->name('dashboard.')->group(function () use ($demo) {
        Route::get('/tasks', $demo)->name('tasks.index');
        Route::get('/documents/create', $demo)->name('documents.create');
        Route::get('/documents', $demo)->name('documents.index');
        Route::get('/documents/verification', $demo)->name('documents.verification');
        Route::get('/documents/approbation', $demo)->name('documents.approbation');
        Route::get('/documents/nomenclature', $demo)->name('documents.nomenclature');
        Route::get('/norms', $demo)->name('norms.index');
        Route::get('/processes', $demo)->name('processes.index');
        Route::get('/risks', $demo)->name('risks.index');
        Route::get('/objectives', $demo)->name('objectives.index');
        Route::get('/action-plans', $demo)->name('action-plans.index');
        Route::get('/audits', $demo)->name('audits.index');
        Route::get('/reviews', $demo)->name('reviews.index');
        Route::get('/indicators', $demo)->name('indicators.index');
        Route::get('/nonconformities', $demo)->name('nonconformities.index');
        Route::get('/sites', $demo)->name('sites.index');
        Route::get('/collaborators', $demo)->name('collaborators.index');
        Route::get('/users', $demo)->name('users.index');
        Route::get('/stakeholders', $demo)->name('stakeholders.index');
        Route::get('/communications', $demo)->name('communications.index');
        Route::get('/trainings', $demo)->name('trainings.index');
        Route::get('/settings', $demo)->name('settings');
        Route::get('/security', $demo)->name('security.index');
        Route::get('/subscription', $demo)->name('subscription');
        Route::get('/profile', $demo)->name('profile');
    });
});
