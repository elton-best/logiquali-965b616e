<?php

namespace App\Jobs;

use App\Imports\UserImport;
use App\Models\User;
use App\Notifications\User\PendingImportApprovalNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $path,
        private readonly int $enterpriseId,
        private readonly int $siteId,
        private readonly array $permissions,
        private readonly bool $sendWelcomeEmail,
        private readonly string $defaultRole = 'lecteur',
        private readonly bool $restrictPrivilegedRoles = false,
        private readonly ?int $createdByUserId = null,
        private readonly bool $requiresManualApproval = false
    ) {
    }

    public function handle(): void
    {
        $fullPath = Storage::path($this->path);

        $import = new UserImport(
            $this->enterpriseId,
            $this->siteId,
            $this->permissions,
            $this->sendWelcomeEmail,
            $this->defaultRole,
            $this->restrictPrivilegedRoles,
            $this->createdByUserId,
            $this->requiresManualApproval
        );

        try {
            Log::info('Starting user import', [
                'file' => $this->path,
                'enterprise_id' => $this->enterpriseId,
            ]);

            Excel::import($import, $fullPath);
            
            // Récupérer les erreurs et failures
            $failures = method_exists($import, 'failures')
                ? collect((array) call_user_func([$import, 'failures']))
                : collect();
            $errors = method_exists($import, 'errors')
                ? collect((array) call_user_func([$import, 'errors']))
                : collect();
            
            $failureCount = count($failures);
            $errorCount = count($errors);
            $pendingApprovalUsersCount = $import->getPendingApprovalUsersCount();
            $createdUsersCount = $import->getCreatedUsersCount();

            // Supprimer le fichier après traitement
            Storage::delete($this->path);
            
            Log::info('User import completed', [
                'enterprise_id' => $this->enterpriseId,
                'site_id' => $this->siteId,
                'default_role' => $this->defaultRole,
                'restrict_privileged_roles' => $this->restrictPrivilegedRoles,
                'requires_manual_approval' => $this->requiresManualApproval,
                'created_users' => $createdUsersCount,
                'pending_approval_users' => $pendingApprovalUsersCount,
                'failures' => $failureCount,
                'errors' => $errorCount,
            ]);

            if ($this->requiresManualApproval && $pendingApprovalUsersCount > 0) {
                $this->notifyEnterpriseAdminsAboutPendingImport($pendingApprovalUsersCount);
            }

            // Logger les détails des erreurs si nécessaire
            if ($failureCount > 0 || $errorCount > 0) {
                Log::warning('Import completed with issues', [
                        'total_failures' => $failureCount,
                        'total_errors' => $errorCount,
                        'sample_failures' => array_slice($failures->all(), 0, 5),
                    ]);
            }

        } catch (\Exception $e) {
            // Supprimer le fichier même en cas d'erreur
            if (Storage::exists($this->path)) {
                Storage::delete($this->path);
            }
            
            Log::error('User import failed critically', [
                'path' => $this->path,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('User import failed', [
            'path' => $this->path,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    private function notifyEnterpriseAdminsAboutPendingImport(int $pendingApprovalUsersCount): void
    {
        $admins = User::query()
            ->role('admin_entreprise')
            ->where('user_type', User::TYPE_COMPANY)
            ->where('enterprise_id', $this->enterpriseId)
            ->get();

        if ($admins->isEmpty()) {
            Log::warning('No enterprise admin found for pending import notification', [
                'enterprise_id' => $this->enterpriseId,
                'site_id' => $this->siteId,
                'pending_approval_users' => $pendingApprovalUsersCount,
            ]);

            return;
        }

        Notification::send(
            $admins,
            new PendingImportApprovalNotification(
                pendingApprovalUsersCount: $pendingApprovalUsersCount,
                enterpriseId: $this->enterpriseId,
                siteId: $this->siteId,
                requestedByUserId: $this->createdByUserId
            )
        );
    }
}
