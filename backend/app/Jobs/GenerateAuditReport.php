<?php

namespace App\Jobs;

use App\Models\Audit;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditReportGenerator;
use App\Notifications\AuditReportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class GenerateAuditReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Audit $audit,
        public string $format = 'pdf',
        public ?User $user = null,
        public ?int $documentId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AuditReportGenerator $generator): void
    {
        try {
            Log::info("Génération rapport audit {$this->audit->ref} (format: {$this->format})");

            // Générer le rapport
            $filePath = match($this->format) {
                'pdf' => $generator->generatePdf($this->audit),
                'docx' => $generator->generateDocx($this->audit),
                default => $generator->generatePdf($this->audit)
            };

            // Mettre à jour le chemin
            $fieldToUpdate = $this->format === 'pdf' ? 'report_path' : 'global_report_path';
            $this->audit->update([
                $fieldToUpdate => $filePath,
                'report_source' => 'auto',
                'report_version' => ((int) ($this->audit->report_version ?? 0)) + 1,
            ]);

            if ($this->documentId) {
                Document::query()
                    ->whereKey($this->documentId)
                    ->update([
                        'file_path' => $filePath,
                        'status' => 'draft',
                        'workflow_status' => 'draft',
                        'needs_verification' => true,
                    ]);
            }

            Log::info("Rapport généré avec succès: {$filePath}");

            // Notifier l'utilisateur
            if ($this->user) {
                Notification::send($this->user, new AuditReportReady($this->audit, $this->format));
            }

            // Notifier responsable audit
            if ($this->audit->leadAuditor && $this->audit->leadAuditor->id !== $this->user?->id) {
                Notification::send($this->audit->leadAuditor, new AuditReportReady($this->audit, $this->format));
            }

            activity('audit_report')
                ->performedOn($this->audit)
                ->causedBy($this->user)
                ->withProperties(['format' => $this->format, 'file_path' => $filePath])
                ->log('Rapport d\'audit généré');

        } catch (\Exception $e) {
            Log::error("Erreur génération rapport audit {$this->audit->ref}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Échec génération rapport audit {$this->audit->ref}: " . $exception->getMessage());
        
        activity('audit_report')
            ->performedOn($this->audit)
            ->withProperties(['error' => $exception->getMessage()])
            ->log('Échec génération rapport');
    }
}
