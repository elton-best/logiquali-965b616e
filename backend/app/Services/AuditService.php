<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\NonConformity;
use App\Models\TaskTracking;
use App\Models\User;
use App\Notifications\SiteEventNotification;
use App\Services\Core\DynamicFieldService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditService
{
    public function __construct(
        private readonly DynamicFieldService $dynamicFieldService,
    ) {}

    public function create(array $data): Audit
    {
        if (($data['type'] ?? null) === 'internal') {
            foreach ($this->dynamicFieldService->getInternalAuditRequiredFields() as $requiredField) {
                if (!filled($data[$requiredField] ?? null)) {
                    throw ValidationException::withMessages([
                        $requiredField => "Le champ {$requiredField} est obligatoire pour un audit interne.",
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($data) {
            $audit = Audit::create([
                'ref' => $this->generateReference(),
                'audit_program_id' => $data['audit_program_id'] ?? null,
                'site_id' => $data['site_id'],
                'type' => $data['type'],
                'title' => $data['title'],
                'planned_date' => $data['planned_date'],
                'lead_auditor_id' => $data['lead_auditor_id'],
                'assigned_to' => $data['assigned_to'] ?? $data['lead_auditor_id'],
                'team_members' => $data['team_members'] ?? [],
                'scope' => $data['scope'] ?? null,
                'objectives' => $data['objectives'] ?? null,
                'reference_documents' => $data['reference_documents'] ?? null,
                'risk_based_criteria' => $data['risk_based_criteria'] ?? null,
                'quarter' => $data['quarter'] ?? null,
                'frequency' => $data['frequency'] ?? null,
            ]);

            // Gestion des auditeurs (pivot table)
            if (isset($data['auditor_ids'])) {
                $audit->auditors()->sync($data['auditor_ids']);
            }

            // Gestion des audités (pivot table)
            if (isset($data['auditee_ids'])) {
                $audit->auditees()->sync($data['auditee_ids']);
            }

            // Gestion des axes QHSE
            if (isset($data['axes'])) {
                $audit->syncAxes($data['axes']);
            }

            // Gestion des processus (pivot table)
            if (isset($data['processes']) || isset($data['process_ids'])) {
                $processIds = $data['process_ids'] ?? $data['processes'];
                $audit->processes()->sync($processIds);
            }

            activity()
                ->performedOn($audit)
                ->causedBy(auth()->user())
                ->log('Audit planifié');

            $this->syncAuditTracking($audit);
            $this->notifyAuditStakeholders($audit, 'audit_created');

            return $audit->refresh()->load(['axes', 'workflowState', 'leadAuditor', 'auditors', 'auditees', 'processes', 'site']);
        });
    }

    public function start(Audit $audit, array $startData): Audit
    {
        $audit->update([
            'actual_date' => $startData['actual_date'] ?? now(),
            'status' => 'in_progress',
        ]);

        activity()
            ->performedOn($audit)
            ->causedBy(auth()->user())
            ->log('Audit démarré');

        $this->syncAuditTracking($audit);
        $this->notifyAuditStakeholders($audit, 'audit_started');

        return $audit->fresh();
    }

    public function addChecklist(Audit $audit, array $checklistItems): Audit
    {
        $audit->update([
            'checklist' => $checklistItems,
        ]);

        // Calculer taux de conformité
        $audit->calculateConformityRate();

        activity()
            ->performedOn($audit)
            ->causedBy(auth()->user())
            ->log('Checklist d\'audit ajoutée (' . count($checklistItems) . ' items)');

        return $audit->fresh();
    }

    public function addFinding(Audit $audit, array $finding): AuditFinding
    {
        // Créer un AuditFinding dans la base de données
        $auditFinding = $audit->findings()->create(array_merge($finding, [
            'audit_id' => $audit->id,
            'detected_by' => auth()->id(),
            'detected_at' => now(),
            'status' => 'open',
        ]));

        activity()
            ->performedOn($audit)
            ->causedBy(auth()->user())
            ->log("Constatation ajoutée : {$finding['type']}");

        return $auditFinding;
    }

    public function finalize(Audit $audit, array $reportData): Audit
    {
        // Calculer le taux de conformité depuis la checklist
        $checklist = $audit->checklist ?? [];
        if (count($checklist) > 0) {
            $conformCount = count(array_filter($checklist, fn($item) => ($item['conformity'] ?? null) === 'conform'));
            $conformityRate = round(($conformCount / count($checklist)) * 100);
            $audit->update(['conformity_rate' => $conformityRate]);
        }

        $audit->update([
            'conclusion' => $reportData['conclusion'] ?? null,
            'report_path' => $reportData['report_path'] ?? null,
            'attachments' => $reportData['attachments'] ?? [],
            'report_source' => 'auto',
            'report_version' => ((int) ($audit->report_version ?? 0)) + 1,
            'status' => 'completed',
        ]);

        // Queue le job de génération de rapport si format spécifié
        if (isset($reportData['format'])) {
            dispatch(new \App\Jobs\GenerateAuditReport($audit, $reportData['format']));
        }

        activity()
            ->performedOn($audit)
            ->causedBy(auth()->user())
            ->log('Rapport d\'audit finalisé');

        $this->syncAuditTracking($audit);
        $this->notifyAuditStakeholders($audit, 'audit_finalized');

        return $audit->fresh();
    }

    public function complete(Audit $audit): Audit
    {
        if ($audit->canTransitionTo('completed')) {
            $audit->transitionTo('completed');
        }

        // Génération automatique du rapport à la clôture.
        dispatch(new \App\Jobs\GenerateAuditReport($audit, 'pdf', auth()->user()));

        $audit->update([
            'report_source' => 'auto',
            'report_version' => ((int) ($audit->report_version ?? 0)) + 1,
        ]);

        activity()
            ->performedOn($audit)
            ->causedBy(auth()->user())
            ->log('Audit complété');

        $this->syncAuditTracking($audit);
        $this->notifyAuditStakeholders($audit, 'audit_completed');

        return $audit->fresh();
    }

    public function cancel(Audit $audit, string $reason): Audit
    {
        if ($audit->canTransitionTo('cancelled')) {
            $audit->transitionTo('cancelled');
        }

        activity()
            ->performedOn($audit)
            ->causedBy(auth()->user())
            ->log("Audit annulé : {$reason}");

        $this->syncAuditTracking($audit);
        $this->notifyAuditStakeholders($audit, 'audit_cancelled', [
            'reason' => $reason,
        ]);

        return $audit->fresh();
    }

    private function syncAuditTracking(Audit $audit): void
    {
        $status = $this->mapAuditStatusToTrackingStatus((string) $audit->status);
        $progress = $this->mapAuditStatusToProgress((string) $audit->status);
        $notes = sprintf('Sync auto audit %s (%s)', $audit->ref, $audit->status);

        $userIds = collect([
            $audit->assigned_to,
            $audit->lead_auditor_id,
        ])->filter()->unique()->values();

        foreach ($userIds as $userId) {
            TaskTracking::updateOrCreate(
                [
                    'user_id' => (int) $userId,
                    'trackable_type' => Audit::class,
                    'trackable_id' => $audit->id,
                ],
                [
                    'status' => $status,
                    'progress_rate' => $progress,
                    'notes' => $notes,
                    'tracked_at' => now(),
                ]
            );
        }
    }

    private function mapAuditStatusToTrackingStatus(string $status): string
    {
        return match ($status) {
            'in_progress' => 'en_cours',
            'completed', 'closed', 'report_approved' => 'termine',
            default => 'non_demarre',
        };
    }

    private function mapAuditStatusToProgress(string $status): int
    {
        return match ($status) {
            'in_progress' => 50,
            'completed', 'closed', 'report_approved' => 100,
            default => 0,
        };
    }

    private function notifyAuditStakeholders(Audit $audit, string $eventType, array $extra = []): void
    {
        try {
            $audit->loadMissing(['site', 'leadAuditor', 'auditors', 'auditees']);

            $actor = auth()->user();
            $actorId = $actor?->id;

            $recipientIds = collect([
                $audit->assigned_to,
                $audit->lead_auditor_id,
            ])
                ->merge($audit->auditors->pluck('id'))
                ->merge($audit->auditees->pluck('id'))
                ->filter()
                ->unique()
                ->reject(fn ($id) => $actorId && (int) $id === (int) $actorId)
                ->values();

            if ($recipientIds->isEmpty()) {
                return;
            }

            $payload = array_merge([
                'audit_id' => $audit->id,
                'audit_ref' => $audit->ref,
                'audit_title' => $audit->title,
                'audit_status' => $audit->status,
                'planned_date' => optional($audit->planned_date)->format('Y-m-d'),
                'site_name' => $audit->site?->name,
                'actor_name' => $actor?->name ?? $actor?->full_name ?? $actor?->username,
            ], $extra);

            User::query()
                ->whereIn('id', $recipientIds->all())
                ->get()
                ->each(fn (User $user) => $user->notify(new SiteEventNotification($eventType, $payload, $actorId)));
        } catch (\Throwable $e) {
            // Notifications should not block business flow.
        }
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Audit::query();

        if (array_key_exists('enterprise_id', $filters)) {
            if (empty($filters['enterprise_id'])) {
                $query->whereRaw('1 = 0');
            } else {
                if (Schema::hasColumn('audits', 'enterprise_id')) {
                    $query->where('enterprise_id', $filters['enterprise_id']);
                } else {
                    $enterpriseId = (int) $filters['enterprise_id'];
                    $query->whereHas('site', function ($siteQuery) use ($enterpriseId) {
                        $siteQuery->where('enterprise_id', $enterpriseId);
                    });
                }
            }
        }

        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        if (isset($filters['year'])) {
            $query->whereYear('planned_date', $filters['year']);
        }

        $total = (clone $query)->count();
        $byType = (clone $query)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
        $byStatus = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $avgConformityRate = (clone $query)
            ->whereNotNull('conformity_rate')
            ->avg('conformity_rate');

        $auditIds = (clone $query)->pluck('id');

        // Compter les findings depuis la table audit_findings uniquement sur le scope locataire.
        $totalFindings = \App\Models\AuditFinding::whereIn('audit_id', $auditIds)->count();
        $ncMajorCount = \App\Models\AuditFinding::whereIn('audit_id', $auditIds)
            ->where('type', 'nc_major')->count();
        $ncMinorCount = \App\Models\AuditFinding::whereIn('audit_id', $auditIds)
            ->where('type', 'nc_minor')->count();
        $totalNonConformities = NonConformity::whereIn('audit_id', $auditIds)->count();

        return [
            'total' => $total,
            'completed' => $byStatus->get('completed', 0),
            'in_progress' => $byStatus->get('in_progress', 0),
            'planned' => $byStatus->get('planned', 0),
            'by_type' => $byType->toArray(),
            'avg_conformity_rate' => round($avgConformityRate ?? 0, 1),
            'total_findings' => $totalFindings,
            'nc_major_count' => $ncMajorCount,
            'nc_minor_count' => $ncMinorCount,
            'total_nc_generated' => $totalNonConformities,
        ];
    }

    protected function generateReference(): string
    {
        $year = now()->year;
        $lastAudit = Audit::where('ref', 'like', "AUD-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if (!$lastAudit) {
            return "AUD-{$year}-001";
        }

        $lastNumber = (int) substr($lastAudit->ref, -3);
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return "AUD-{$year}-{$newNumber}";
    }

    /**
     * Génère checklist automatique basée sur processus et clauses ISO
     */
    public function generateChecklist(Audit $audit, array $isoClauses = [], bool $includeProcessRisks = true): array
    {
        $checklist = [];

        // 1. Questions standards par clause ISO
        $standardQuestions = $this->getStandardIsoQuestions($isoClauses);
        foreach ($standardQuestions as $question) {
            $checklist[] = $question;
        }

        // 2. Questions spécifiques aux processus audités
        foreach ($audit->auditedProcesses as $process) {
            $checklist[] = [
                'process_id' => $process->id,
                'question' => "Le processus '{$process->title}' est-il maîtrisé et efficace ?",
                'clause_iso' => '4.4',
                'conformity' => null,
                'evidence' => '',
                'notes' => ''
            ];

            // Intégrer risques du processus
            if ($includeProcessRisks) {
                foreach ($process->risks()->where('criticality', '>=', 10)->get() as $risk) {
                    $checklist[] = [
                        'process_id' => $process->id,
                        'risk_id' => $risk->id,
                        'question' => "Le risque '{$risk->title}' est-il maîtrisé ?",
                        'clause_iso' => '6.1',
                        'conformity' => null,
                        'evidence' => '',
                        'notes' => ''
                    ];
                }
            }
        }

        // Sauvegarder la checklist dans l'audit
        $audit->update(['checklist' => $checklist]);

        return $checklist;
    }

    /**
     * Questions ISO standards
     */
    protected function getStandardIsoQuestions(array $clauses): array
    {
        $questions = [
            '4.4' => [
                'La cartographie des processus est-elle à jour ?',
                'Les interactions entre processus sont-elles identifiées ?',
                'Les ressources nécessaires sont-elles disponibles ?'
            ],
            '6.1' => [
                'Les risques et opportunités ont-ils été identifiés ?',
                'Des actions ont-elles été planifiées pour traiter les risques ?'
            ],
            '7.1.5' => [
                'Les ressources de surveillance et de mesure sont-elles adéquates ?',
                'Les équipements sont-ils étalonnés/vérifiés ?'
            ],
            '8.1' => [
                'Les exigences pour les produits/services sont-elles déterminées ?',
                'Les processus opérationnels sont-ils maîtrisés ?'
            ],
            '9.1' => [
                'La surveillance et la mesure sont-elles efficaces ?',
                'Les indicateurs de performance sont-ils suivis ?'
            ],
            '9.2' => [
                'Le programme d\'audits internes est-il établi ?',
                'Les audits sont-ils conduits de manière planifiée ?'
            ],
            '10.2' => [
                'Les non-conformités sont-elles traitées ?',
                'Les actions correctives sont-elles efficaces ?'
            ]
        ];

        $result = [];
        foreach ($clauses as $clause) {
            if (isset($questions[$clause])) {
                foreach ($questions[$clause] as $q) {
                    $result[] = [
                        'question' => $q,
                        'clause_iso' => $clause,
                        'conformity' => null,
                        'evidence' => '',
                        'notes' => ''
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Statistiques globales
     */
    public function getGlobalStatistics($query): array
    {
        $audits = $query->get();

        $stats = [
            'total' => $audits->count(),
            'completed' => $audits->where('status', 'completed')->count(),
            'in_progress' => $audits->where('status', 'in_progress')->count(),
            'planned' => $audits->where('status', 'planned')->count(),
            'avg_conformity_rate' => round($audits->whereNotNull('conformity_rate')->avg('conformity_rate') ?? 0, 1),
            'by_type' => $audits->groupBy('type')->map->count(),
            'total_findings' => $audits->sum(fn($a) => count($a->findings ?? [])),
            'nc_major_count' => 0,
            'nc_minor_count' => 0,
            'observations_count' => 0,
        ];

        // Compter findings par type
        foreach ($audits as $audit) {
            foreach ($audit->findings ?? [] as $finding) {
                if ($finding['type'] === 'major') $stats['nc_major_count']++;
                if ($finding['type'] === 'minor') $stats['nc_minor_count']++;
                if ($finding['type'] === 'observation') $stats['observations_count']++;
            }
        }

        return $stats;
    }
}
