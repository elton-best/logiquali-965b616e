<?php

namespace App\Jobs;

use App\Models\AuditFinding;
use App\Models\NonConformity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateNCFromFinding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public AuditFinding $finding
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Vérifier si NC déjà créée
        if ($this->finding->nc_created || $this->finding->non_conformity_id) {
            Log::info("NC déjà créée pour finding {$this->finding->id}");
            return;
        }

        // Créer NC uniquement pour écarts majeurs/mineurs
        if (!in_array($this->finding->type, ['nc_major', 'nc_minor'])) {
            Log::info("Finding {$this->finding->id} n'est pas un écart, skip");
            return;
        }

        try {
            $nc = NonConformity::create([
                'site_id' => $this->finding->audit->site_id,
                'process_id' => $this->finding->process_id,
                'audit_id' => $this->finding->audit_id,
                'title' => $this->finding->title,
                'type' => $this->finding->type === 'nc_major' ? 'major' : 'minor',
                'source' => 'audit',
                'severity' => $this->finding->severity,
                'priority' => $this->finding->priority,
                'description' => $this->finding->description,
                'detected_by' => $this->finding->detected_by,
                'detected_at' => $this->finding->detected_at,
                'root_cause_analysis' => $this->finding->root_cause,
                'deadline' => $this->finding->resolution_deadline,
                'status' => 'open',
            ]);

            // Mettre à jour finding
            $this->finding->update([
                'non_conformity_id' => $nc->id,
                'nc_created' => true,
                'status' => 'nc_created'
            ]);

            Log::info("NC {$nc->ref} créée depuis finding {$this->finding->ref}");

            activity('audit_finding')
                ->performedOn($this->finding)
                ->withProperties(['nc_id' => $nc->id, 'nc_ref' => $nc->ref])
                ->log('NC créée automatiquement depuis constat');

        } catch (\Exception $e) {
            Log::error("Erreur création NC depuis finding {$this->finding->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
