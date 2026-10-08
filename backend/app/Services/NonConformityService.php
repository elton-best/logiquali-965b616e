<?php

namespace App\Services;

use App\Models\NonConformity;
use App\Models\TaskTracking;
use App\Models\User;
use App\Notifications\SiteEventNotification;
use App\Models\WorkflowState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class NonConformityService
{
    public function create(array $data): NonConformity
    {
        $attempts = 0;
        $maxAttempts = 5;

        while ($attempts < $maxAttempts) {
            try {
                return DB::transaction(function () use ($data) {
                    $detectedBy = $data['detected_by'] ?? auth()->id();
                    $responsibleId = $data['responsible_id'] ?? $detectedBy;

                    $payload = [
                        'ref' => $this->generateReference(),
                        'site_id' => $data['site_id'],
                        'process_id' => $data['process_id'] ?? null,
                        'audit_id' => $data['audit_id'] ?? null,
                        'title' => $data['title'] ?? null,
                        'finding_type' => $data['finding_type'] ?? 'non_conformity',
                        'type' => $data['type'],
                        'source' => $data['source'] ?? 'internal',
                        'detection_source' => $data['detection_source'] ?? null,
                        'severity' => $data['severity'],
                        'priority' => $data['priority'] ?? 'medium',
                        'description' => $data['description'],
                        'requirement_reference' => $data['requirement_reference'] ?? null,
                        'detected_by' => $detectedBy,
                        'detected_at' => $data['detected_at'] ?? now(),
                        'root_cause_analysis' => $data['root_cause_analysis'] ?? null,
                        'corrective_action' => $data['corrective_action'] ?? null,
                        'preventive_action' => $data['preventive_action'] ?? null,
                        'responsible_id' => $responsibleId,
                        'investigator_user_ids' => isset($data['investigator_user_ids'])
                            ? array_values(array_unique(array_map('intval', $data['investigator_user_ids'])))
                            : [],
                        'deadline' => $data['deadline'] ?? null,
                        'result_summary' => $data['result_summary'] ?? null,
                        'rq_signature_date' => $data['rq_signature_date'] ?? null,
                    ];

                    $nc = NonConformity::create($this->filterExistingColumns($payload));

                    // Attacher les axes QHSE
                    if (isset($data['axes'])) {
                        $nc->syncAxes($data['axes']);
                    }

                    // Attacher documents
                    if (isset($data['documents'])) {
                        $nc->documents()->sync($data['documents']);
                    }

                    // Créer actions si fournies
                    if (isset($data['actions'])) {
                        foreach ($data['actions'] as $actionData) {
                            $payload = array_merge($actionData, [
                                'site_id' => $data['site_id'],
                                'process_id' => $actionData['process_id'] ?? $data['process_id'] ?? null,
                                'title' => $actionData['title'] ?? ($actionData['description'] ?? 'Action corrective'),
                                'description' => $actionData['description'] ?? '',
                                'source' => 'non_conformity',
                            ]);
                            $action = app(ActionService::class)->create($payload);
                            $nc->addAction($action);
                        }
                    }

                    // Link to risk and update risk statistics
                    if (!empty($data['risk_id'])) {
                        $risk = \App\Models\Risk::find($data['risk_id']);
                        if ($risk) {
                            $risk->increment('actual_occurrences');
                            $risk->last_occurrence_at = now();
                            $risk->save();

                            // Create link
                            $nc->risks()->attach($risk->id, [
                                'relation_type' => 'realization',
                                'created_at' => now(),
                            ]);
                        }
                    }

                    activity()
                        ->performedOn($nc)
                        ->causedBy(auth()->user())
                        ->log('Non-conformité créée');

                    $this->syncNonConformityTracking($nc, 'non_demarre', 0, 'Création de la non-conformité');
                    $this->notifyNonConformityStakeholders($nc, 'non_conformity_created');

                    return $nc->load(['axes', 'workflowState', 'risks']);
                });
            } catch (\Throwable $e) {
                $attempts++;
                if ($this->isDuplicateRefException($e) && $attempts < $maxAttempts) {
                    continue;
                }
                throw $e;
            }
        }

        throw ValidationException::withMessages([
            'ref' => "Impossible de générer une référence unique après {$maxAttempts} tentatives.",
        ]);
    }

    public function update(NonConformity $nc, array $data): NonConformity
    {
        return DB::transaction(function () use ($nc, $data) {
            if (isset($data['investigator_user_ids']) && is_array($data['investigator_user_ids'])) {
                $data['investigator_user_ids'] = array_values(array_unique(array_map('intval', $data['investigator_user_ids'])));
            }

            $nc->update($this->filterExistingColumns($data));

            if (isset($data['axes'])) {
                $nc->syncAxes($data['axes']);
            }

            if (isset($data['documents'])) {
                $nc->documents()->sync($data['documents']);
            }

            if (array_key_exists('actions', $data)) {
                $nc->actions()->detach();
                foreach ($data['actions'] ?? [] as $actionData) {
                    $payload = array_merge($actionData, [
                        'site_id' => $nc->site_id,
                        'process_id' => $actionData['process_id'] ?? $nc->process_id ?? null,
                        'title' => $actionData['title'] ?? ($actionData['description'] ?? 'Action corrective'),
                        'description' => $actionData['description'] ?? '',
                        'source' => 'non_conformity',
                    ]);
                    $action = app(ActionService::class)->create($payload);
                    $nc->addAction($action);
                }
            }

            activity()
                ->performedOn($nc)
                ->causedBy(auth()->user())
                ->log('Non-conformité mise à jour');

            $this->syncNonConformityTracking($nc);
            $this->notifyNonConformityStakeholders($nc, 'non_conformity_updated');

            return $nc->fresh(['axes', 'workflowState']);
        });
    }

    public function analyze(NonConformity $nc, array $analysisData): NonConformity
    {
        $nc->update([
            'root_cause_analysis' => $analysisData['root_cause'] ?? null,
            'cause_analysis' => [
                'method' => $analysisData['method'] ?? '5why',
                'data' => $analysisData['data'] ?? [],
                'analyzed_by' => auth()->id(),
                'analyzed_at' => now()->toDateTimeString(),
            ],
            'impacts' => $analysisData['impacts'] ?? null,
            'corrective_action' => $analysisData['corrective_action'] ?? null,
            'preventive_action' => $analysisData['preventive_action'] ?? null,
        ]);

        // Transition vers état "in_analysis"
        if ($nc->canTransitionTo('in_analysis')) {
            $nc->transitionTo('in_analysis');
        }

        activity()
            ->performedOn($nc)
            ->causedBy(auth()->user())
            ->log('Analyse des causes effectuée');

        $this->syncNonConformityTracking($nc, 'en_cours', 50, 'Analyse des causes en cours');
        $this->notifyNonConformityStakeholders($nc, 'non_conformity_analyzed');

        return $nc->fresh();
    }

    public function validate(NonConformity $nc, array $validationData): NonConformity
    {
        $nc->update([
            'responsible_id' => $validationData['responsible_id'],
            'deadline' => $validationData['deadline'],
        ]);

        // Créer actions correctives si fournies
        if (isset($validationData['actions'])) {
            foreach ($validationData['actions'] as $actionData) {
                $action = app(ActionService::class)->create($actionData);
                $nc->addAction($action);
            }
        }

        // Transition vers état "validated"
        if ($nc->canTransitionTo('validated')) {
            $nc->transitionTo('validated');
        }

        activity()
            ->performedOn($nc)
            ->causedBy(auth()->user())
            ->log('Non-conformité validée');

        $this->syncNonConformityTracking($nc, 'en_cours', 60, 'Non-conformité validée');
        $this->notifyNonConformityStakeholders($nc, 'non_conformity_validated');

        return $nc->fresh(['actions', 'workflowState']);
    }

    public function verify(NonConformity $nc, array $verificationData): NonConformity
    {
        $nc->update([
            'effectiveness_verified' => $verificationData['is_effective'],
            'verification_date' => now(),
            'verified_by' => auth()->id(),
            'verification_notes' => $verificationData['notes'] ?? null,
        ]);

        // Si efficace, passer en vérification
        if ($verificationData['is_effective'] && $nc->canTransitionTo('in_verification')) {
            $nc->transitionTo('in_verification');
        }

        activity()
            ->performedOn($nc)
            ->causedBy(auth()->user())
            ->log('Efficacité vérifiée : ' . ($verificationData['is_effective'] ? 'OUI' : 'NON'));

        if (!empty($verificationData['is_effective'])) {
            $this->syncNonConformityTracking($nc, 'termine', 100, 'Efficacité vérifiée');
            $this->notifyNonConformityStakeholders($nc, 'non_conformity_verified');
        } else {
            $this->syncNonConformityTracking($nc, 'en_cours', 75, 'Efficacité non validée, reprise');
            $this->notifyNonConformityStakeholders($nc, 'non_conformity_reopened');
        }

        return $nc->fresh();
    }

    public function close(NonConformity $nc): NonConformity
    {
        $openActions = $nc->actions()
            ->whereNotIn('status', ['completed', 'closed', 'verified'])
            ->count();

        if ($openActions > 0) {
            throw ValidationException::withMessages([
                'actions' => "Clôture impossible: {$openActions} action(s) liée(s) ne sont pas terminées.",
            ]);
        }

        $nc->update([
            'resolution_date' => now(),
        ]);

        if ($nc->canTransitionTo('closed')) {
            $nc->transitionTo('closed');
        }

        activity()
            ->performedOn($nc)
            ->causedBy(auth()->user())
            ->log('Non-conformité clôturée');

        $this->syncNonConformityTracking($nc, 'termine', 100, 'Non-conformité clôturée');
        $this->notifyNonConformityStakeholders($nc, 'non_conformity_closed');

        return $nc->fresh();
    }

    public function syncTaskTrackingAndNotify(NonConformity $nc, string $eventType): void
    {
        $this->syncNonConformityTracking($nc);
        $this->notifyNonConformityStakeholders($nc, $eventType);
    }

    public function addCost(NonConformity $nc, float $cost): NonConformity
    {
        $nc->update(['cost_impact' => $cost]);

        activity()
            ->performedOn($nc)
            ->causedBy(auth()->user())
            ->log("Coût ajouté : {$cost}€");

        return $nc;
    }

    public function getStatistics(array $filters = []): array
    {
        $query = NonConformity::query();

        // Filtres
        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        if (isset($filters['date_from'])) {
            $query->where('detected_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('detected_at', '<=', $filters['date_to']);
        }

        // Statistiques
        $total = $query->count();
        $bySeverity = $query->get()->groupBy('severity')->map->count();
        $byStatus = $query->get()->groupBy(fn($nc) => $nc->workflowState?->code)->map->count();
        $byAxe = [];

        foreach ($query->with('axes')->get() as $nc) {
            foreach ($nc->axes as $axe) {
                $byAxe[$axe->code] = ($byAxe[$axe->code] ?? 0) + 1;
            }
        }

        $overdueCount = $query->get()->filter->isOverdue()->count();
        $verifiedCount = $query->where('effectiveness_verified', true)->count();
        $avgResolutionDays = $query->whereNotNull('resolution_date')
            ->get()
            ->map(fn($nc) => $nc->detected_at->diffInDays($nc->resolution_date))
            ->avg();

        return [
            'total' => $total,
            'by_severity' => $bySeverity,
            'by_status' => $byStatus,
            'by_axe' => $byAxe,
            'overdue' => $overdueCount,
            'verified' => $verifiedCount,
            'avg_resolution_days' => round($avgResolutionDays ?? 0, 1),
        ];
    }

    protected function generateReference(): string
    {
        $year = now()->year;
        $lastNc = NonConformity::withoutEnterpriseScope()
            ->withTrashed()
            ->where('ref', 'like', "NC-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastNc) {
            $lastNumber = 0;
            if (preg_match('/(\d+)$/', (string) $lastNc->ref, $matches)) {
                $lastNumber = (int) $matches[1];
            }
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $width = max(3, strlen((string) $newNumber));
        $number = str_pad((string) $newNumber, $width, '0', STR_PAD_LEFT);

        return sprintf('NC-%d-%s', $year, $number);
    }

    private function syncNonConformityTracking(
        NonConformity $nc,
        ?string $forcedStatus = null,
        ?int $forcedProgress = null,
        ?string $note = null
    ): void {
        $trackingStatus = $forcedStatus ?? $this->mapNonConformityToTrackingStatus($nc);
        $progress = $forcedProgress ?? $this->mapNonConformityToTrackingProgress($nc);
        $trackingNote = $note ?? sprintf('Sync NC %s (%s)', $nc->ref, (string) ($nc->status ?? ''));

        $userIds = collect([
            $nc->responsible_id,
            $nc->detected_by,
        ])
            ->merge(is_array($nc->investigator_user_ids) ? $nc->investigator_user_ids : [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($userIds as $userId) {
            TaskTracking::updateOrCreate(
                [
                    'user_id' => $userId,
                    'trackable_type' => NonConformity::class,
                    'trackable_id' => $nc->id,
                ],
                [
                    'status' => $trackingStatus,
                    'progress_rate' => $progress,
                    'notes' => $trackingNote,
                    'tracked_at' => now(),
                ]
            );
        }
    }

    private function mapNonConformityToTrackingStatus(NonConformity $nc): string
    {
        $status = strtolower((string) ($nc->status ?? 'open'));
        $workflow = strtolower((string) ($nc->workflowState?->code ?? ''));

        if (in_array($status, ['closed', 'verified'], true) || in_array($workflow, ['closed', 'verified'], true)) {
            return 'termine';
        }

        if (in_array($status, ['in_progress', 'analysis', 'corrective_action', 'verification'], true)
            || in_array($workflow, ['in_analysis', 'validated', 'in_verification'], true)) {
            return 'en_cours';
        }

        return 'non_demarre';
    }

    private function mapNonConformityToTrackingProgress(NonConformity $nc): int
    {
        return match ($this->mapNonConformityToTrackingStatus($nc)) {
            'termine' => 100,
            'en_cours' => 50,
            default => 0,
        };
    }

    private function notifyNonConformityStakeholders(NonConformity $nc, string $eventType): void
    {
        try {
            $actor = auth()->user();
            $actorId = $actor?->id;

            $recipientIds = collect([
                $nc->responsible_id,
                $nc->detected_by,
            ])
                ->merge(is_array($nc->investigator_user_ids) ? $nc->investigator_user_ids : [])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => $actorId && (int) $id === (int) $actorId)
                ->values();

            if ($recipientIds->isEmpty()) {
                return;
            }

            $payload = [
                'non_conformity_id' => $nc->id,
                'non_conformity_ref' => $nc->ref,
                'non_conformity_title' => $nc->title,
                'status' => $nc->status,
                'severity' => $nc->severity,
                'deadline' => optional($nc->deadline)->format('Y-m-d'),
                'actor_name' => $actor?->name ?? $actor?->full_name ?? $actor?->username,
            ];

            User::query()
                ->whereIn('id', $recipientIds->all())
                ->get()
                ->each(fn (User $user) => $user->notify(new SiteEventNotification($eventType, $payload, $actorId)));
        } catch (\Throwable $e) {
            // Ne pas bloquer le flux NC en cas d'échec notification.
        }
    }

    private function filterExistingColumns(array $payload): array
    {
        static $allowed = null;

        if ($allowed === null) {
            $allowed = array_flip(Schema::getColumnListing('non_conformities'));
        }

        return array_intersect_key($payload, $allowed);
    }

    private function isDuplicateRefException(\Throwable $exception): bool
    {
        if ($exception instanceof UniqueConstraintViolationException) {
            return true;
        }

        if ($exception instanceof QueryException) {
            $sqlState = $exception->errorInfo[0] ?? null;
            if ($sqlState === '23505') {
                $message = $exception->getMessage();
                return str_contains($message, 'non_conformities_ref_unique');
            }
        }

        return false;
    }
}
