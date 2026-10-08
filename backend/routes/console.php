<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\NotificationReminderService;
use App\Jobs\CheckExpiredCertifications;
use App\Jobs\CheckExpiredSignatureWorkflows;
use App\Jobs\SendAuditReminders;
use App\Jobs\CleanupExpiredCodeReservations;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:send-deadline-reminders', function () {
    app(NotificationReminderService::class)->handle();
})->purpose('Send audit and subscription deadline reminders');

// Scheduler - Tâches planifiées
Schedule::command('notifications:send-deadline-reminders')->dailyAt('08:30');
Schedule::command('habilitations:check-expiry')->dailyAt('08:30');
Schedule::command('documents:check-revisions')->dailyAt('09:30');
Schedule::command('actions:check-deadlines')->dailyAt('09:00');
Schedule::command('incidents:check-alerts')->dailyAt('09:05');
Schedule::command('subscriptions:check-expiry')->dailyAt('07:00');
Schedule::command('trial:check')->daily();
Schedule::command('communications:check-reminders')->dailyAt('08:00');
Schedule::command('maintenances:check-reminders')->dailyAt('08:15');
Schedule::command('formations:check-reminders')->dailyAt('08:30');
Schedule::command('risks:check-reassessments')->monthlyOn(1, '09:00');

// Nouveaux jobs - Documents personnalisés
Schedule::job(new CheckExpiredCertifications)->daily();
Schedule::job(new CheckExpiredSignatureWorkflows)->hourly();

// Rappels d'audit - 1 mois, 1 semaine, 1 jour avant
Schedule::job(new SendAuditReminders)->dailyAt('08:00');

// Nettoyage codes expirés - toutes les heures
Schedule::job(new CleanupExpiredCodeReservations)->hourly();

// Archivage annuel du plan SMQ
Schedule::command('plans:archive-sm')->yearlyOn(12, 31, '23:55');
