<?php

use Illuminate\Support\Facades\Route;
use App\Modules\ClientB\Controllers\ClientBDashboardController;
use App\Modules\ClientB\Controllers\ClientBProfileController;
use App\Modules\Support\Controllers\SupportTicketController;

// ============================================================
// CLIENT B ROUTES (Client final / Consommateur)
// ============================================================

Route::prefix('clientb')->group(function () {
    // Dashboard Client B
    Route::get('dashboard/stats', [ClientBDashboardController::class, 'getStats']);
    Route::get('dashboard/recent-activity', [ClientBDashboardController::class, 'getRecentActivity']);
    Route::get('dashboard/quick-actions', [ClientBDashboardController::class, 'getQuickActions']);

    // Gestion du profil
    Route::get('profile/stats', [ClientBProfileController::class, 'getStats']);
    Route::put('profile', [ClientBProfileController::class, 'updateProfile']);
    Route::post('profile/change-password', [ClientBProfileController::class, 'changePassword']);

    // Tickets de support
    Route::get('support/tickets', [SupportTicketController::class, 'index']);
    Route::post('support/tickets', [SupportTicketController::class, 'store']);
    Route::get('support/tickets/{id}', [SupportTicketController::class, 'show']);
    Route::get('support/stats', [SupportTicketController::class, 'stats']);
});

