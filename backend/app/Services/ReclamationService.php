<?php

namespace App\Services;

use App\Models\Action;
use App\Models\NonConformity;
use App\Models\Process;
use App\Models\Reclamation;
use App\Models\TaskTracking;
use App\Models\User;
use App\Notifications\SiteEventNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReclamationService
{
    public function create(array $data): Reclamation
    {
        return DB::transaction(function () use ($data) {
            $clientName = $data['client_name'] ?? $data['customer_name'] ?? null;
            $clientEmail = $data['client_email'] ?? $data['customer_email'] ?? null;
            $clientPhone = $data['client_phone'] ?? $data['customer_phone'] ?? null;
            $clientCompany = $data['client_company'] ?? $clientName ?? null;
            $normalizedCategory = $this->normalizeCategory($data['category'] ?? $data['type'] ?? null);
            $normalizedStakeholderType = $this->normalizeStakeholderType($data['stakeholder_type'] ?? null);
            $actorId = $this->actorId();

            $reclamation = Reclamation::create([
                'ref' => $this->generateReference(),
                'site_id' => $data['site_id'],
                'user_id' => $data['user_id'] ?? $actorId,
                'client_name' => $clientName,
                'client_email' => $clientEmail,
                'client_phone' => $clientPhone,
                'client_company' => $clientCompany,
                'stakeholder_type' => $normalizedStakeholderType,
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $normalizedCategory,
                'severity' => $data['severity'] ?? 'minor',
                'received_date' => $data['received_date'] ?? now(),
                'due_date' => $data['due_date'] ?? now()->addDays(15),
                'wants_mail' => $data['wants_mail'] ?? false,
            ]);

            if (isset($data['axes'])) {
                $reclamation->syncAxes($data['axes']);
            }

            activity()
                ->performedOn($reclamation)
                ->causedBy($this->actor())
                ->log('Réclamation enregistrée');

            // Créer actions si fournies
            if (isset($data['actions'])) {
                foreach ($data['actions'] as $actionData) {
                    $payload = array_merge($actionData, [
                        'site_id' => $reclamation->site_id,
                        'process_id' => $actionData['process_id'] ?? null,
                        'title' => $actionData['title'] ?? ($actionData['description'] ?? 'Action réclamation'),
                        'description' => $actionData['description'] ?? '',
                        'source' => 'complaint',
                    ]);
                    $action = app(ActionService::class)->create($payload);
                    $reclamation->addAction($action);
                }
            }

            // Déclencher l'event de soumission
            event(new \App\Events\Complaint\ComplaintSubmitted($this->loadReclamationEventPayload($reclamation)));

            $this->syncReclamationTracking($reclamation, 'non_demarre', 0, 'Réclamation créée');
            $this->notifyReclamationStakeholders($reclamation, 'reclamation_created');

            return $reclamation->load(['axes', 'workflowState']);
        });
    }

    public function assign(Reclamation $reclamation, int $userId): Reclamation
    {
        $reclamation->update(['assigned_to' => $userId]);

        activity()
            ->performedOn($reclamation)
            ->causedBy($this->actor())
            ->log("Réclamation assignée à l'utilisateur #{$userId}");

        // Déclencher l'event d'assignation
        $assignedUser = \App\Models\User::find($userId);
        if ($assignedUser) {
            event(new \App\Events\Complaint\ComplaintAssigned($reclamation, $assignedUser));
        }

        $this->syncReclamationTracking($reclamation, 'en_cours', 20, 'Réclamation assignée');
        $this->notifyReclamationStakeholders($reclamation, 'reclamation_assigned');

        return $reclamation->fresh();
    }

    public function analyze(Reclamation $reclamation, array $analysisData): Reclamation
    {
        $reclamation->update([
            'analysis' => $analysisData['analysis'],
            'assigned_to' => $analysisData['assigned_to'] ?? $reclamation->assigned_to,
        ]);

        if ($reclamation->canTransitionTo('in_analysis')) {
            $reclamation->transitionTo('in_analysis');
        }

        activity()
            ->performedOn($reclamation)
            ->causedBy($this->actor())
            ->log('Analyse de la réclamation effectuée');

        $this->syncReclamationTracking($reclamation, 'en_cours', 40, 'Analyse réclamation');
        $this->notifyReclamationStakeholders($reclamation, 'reclamation_analyzed');

        return $reclamation->fresh();
    }

    public function respond(Reclamation $reclamation, array $responseData): Reclamation
    {
        $reclamation->update([
            'immediate_response' => $responseData['response'],
            'response_date' => now(),
            'responded_by' => $this->actorId(),
        ]);

        // Créer NC si nécessaire
        if (isset($responseData['create_nc']) && $responseData['create_nc']) {
            $nc = app(NonConformityService::class)->create([
                'site_id' => $reclamation->site_id,
                'type' => 'contractual',
                'source' => 'complaint',
                'severity' => $reclamation->severity,
                'description' => "NC suite réclamation {$reclamation->ref} : {$reclamation->description}",
                'detected_by' => $this->actorId(),
            ]);

            $reclamation->addAction($nc->id);
        }

        // Créer actions si fournies
        if (isset($responseData['actions'])) {
            foreach ($responseData['actions'] as $actionData) {
                $action = app(ActionService::class)->create($actionData);
                $reclamation->addAction($action);
            }
        }

        if ($reclamation->canTransitionTo('in_treatment')) {
            $reclamation->transitionTo('in_treatment');
        }

        // Déclencher l'event de réponse
        event(new \App\Events\Complaint\ComplaintReplied(
            $this->loadReclamationEventPayload($reclamation),
            $responseData['response']
        ));

        activity()
            ->performedOn($reclamation)
            ->causedBy($this->actor())
            ->log('Réponse envoyée au client');

        $this->syncReclamationTracking($reclamation, 'en_cours', 70, 'Réponse réclamation envoyée');
        $this->notifyReclamationStakeholders($reclamation, 'reclamation_responded');

        return $reclamation->fresh(['actions']);
    }

    public function close(Reclamation $reclamation, ?array $satisfactionData = null): Reclamation
    {
        return DB::transaction(function () use ($reclamation, $satisfactionData) {
            $reclamation->update([
                'closed_date' => now(),
            ]);

            // Supporte les deux conventions de payload (legacy et controller actuel).
            $rating = $satisfactionData['rating'] ?? $satisfactionData['satisfaction_score'] ?? null;
            $comment = $satisfactionData['comment'] ?? $satisfactionData['satisfaction_comments'] ?? null;

            if ($rating !== null || $comment !== null) {
                $reclamation->update([
                    'satisfaction_rating' => $rating,
                    'satisfaction_comment' => $comment,
                    'satisfaction_date' => now(),
                ]);
            }

            if ($reclamation->canTransitionTo('closed')) {
                $reclamation->transitionTo('closed');
            }

            [$linkedNc, $linkedAction] = $this->ensureIncidentClosureLinks($reclamation);

            // Déclencher l'event de clôture avec lien satisfaction (best effort).
            $surveyUrl = '';
            try {
                $surveyUrl = route('satisfaction.survey', ['complaint' => $reclamation->id]);
            } catch (\Throwable $e) {
                Log::warning('Route satisfaction.survey indisponible lors de la clôture réclamation.', [
                    'reclamation_id' => $reclamation->id,
                    'error' => $e->getMessage(),
                ]);
            }

            event(new \App\Events\Complaint\ComplaintClosed(
                $this->loadReclamationEventPayload($reclamation),
                $surveyUrl
            ));

            activity()
                ->performedOn($reclamation)
                ->causedBy($this->actor())
                ->withProperties([
                    'linked_non_conformity_id' => $linkedNc?->id,
                    'linked_action_id' => $linkedAction?->id,
                ])
                ->log('Réclamation clôturée' . ($rating !== null ? " - Satisfaction : {$rating}/5" : ''));

            $this->syncReclamationTracking($reclamation, 'termine', 100, 'Réclamation clôturée');
            $this->notifyReclamationStakeholders($reclamation, 'reclamation_closed');

            return $reclamation->fresh(['actions']);
        });
    }

    public function syncTaskTrackingAndNotify(Reclamation $reclamation, string $eventType): void
    {
        $this->syncReclamationTracking($reclamation);
        $this->notifyReclamationStakeholders($reclamation, $eventType);
    }

    public function addCost(Reclamation $reclamation, float $cost): Reclamation
    {
        $reclamation->update(['cost_impact' => $cost]);

        activity()
            ->performedOn($reclamation)
            ->causedBy($this->actor())
            ->log("Coût impacté : {$cost}€");

        return $reclamation;
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Reclamation::query();

        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        if (isset($filters['date_from'])) {
            $query->where('received_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('received_date', '<=', $filters['date_to']);
        }

        $reclamations = $query->get();

        $avgResponseTime = $reclamations->filter(fn($r) => $r->response_date)
            ->map(fn($r) => $r->received_date->diffInDays($r->response_date))
            ->avg();

        $avgSatisfaction = $reclamations->whereNotNull('satisfaction_rating')->avg('satisfaction_rating');

        return [
            'total' => $reclamations->count(),
            'by_category' => $reclamations->groupBy('category')->map->count(),
            'by_severity' => $reclamations->groupBy('severity')->map->count(),
            'by_stakeholder_type' => $reclamations->groupBy('stakeholder_type')->map->count(),
            'by_status' => $reclamations->groupBy(fn($r) => $r->workflowState?->code)->map->count(),
            'overdue' => $reclamations->filter->isOverdue()->count(),
            'avg_response_days' => round($avgResponseTime ?? 0, 1),
            'avg_satisfaction' => round($avgSatisfaction ?? 0, 1),
            'with_warranty_claim' => $reclamations->where('warranty_claim', true)->count(),
        ];
    }

    private function normalizeCategory(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = strtolower(trim($value));
        $map = [
            'product' => 'product_quality',
            'quality' => 'product_quality',
            'service' => 'service_quality',
            'delivery' => 'delivery_delay',
            'safety' => 'other',
            'environment' => 'other',
        ];

        $allowed = [
            'product_quality',
            'product_defect',
            'service_quality',
            'delivery_delay',
            'delivery_error',
            'documentation',
            'packaging',
            'billing',
            'communication',
            'other',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        return in_array($normalized, $allowed, true) ? $normalized : 'other';
    }

    private function normalizeStakeholderType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'clientb', 'client', '' => 'client',
            'supplier', 'fournisseur' => 'supplier',
            'distributor', 'distributeur' => 'distributor',
            'end_user', 'enduser', 'utilisateur_final' => 'end_user',
            default => 'other',
        };
    }

    protected function generateReference(): string
    {
        $year = now()->year;
        $lastReclamation = Reclamation::where('ref', 'like', "REC-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastReclamation) {
            $lastNumber = (int) substr($lastReclamation->ref, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('REC-%d-%03d', $year, $newNumber);
    }

    private function ensureIncidentClosureLinks(Reclamation $reclamation): array
    {
        $refLabel = (string) ($reclamation->ref ?: ('REC#' . $reclamation->id));
        $processId = $this->resolveProcessIdForIncident($reclamation);
        $detectedBy = (int) ($reclamation->assigned_to ?: $reclamation->user_id ?: $this->actorId() ?: 0);
        if ($detectedBy <= 0) {
            $detectedBy = (int) (User::query()->where('site_id', $reclamation->site_id)->value('id') ?: 0);
        }
        if ($detectedBy <= 0) {
            $detectedBy = null;
        }

        $ncTitle = 'NC Incident - ' . $refLabel;
        $nc = NonConformity::query()
            ->where('site_id', $reclamation->site_id)
            ->where('source', 'complaint')
            ->where('title', $ncTitle)
            ->latest('id')
            ->first();

        if (!$nc) {
            $ncPayload = [
                'site_id' => $reclamation->site_id,
                'process_id' => $processId,
                'title' => $ncTitle,
                'type' => 'contractual',
                'source' => 'complaint',
                'severity' => in_array((string) $reclamation->severity, ['minor', 'major', 'critical'], true)
                    ? $reclamation->severity
                    : 'minor',
                'description' => "NC auto-générée depuis incident {$refLabel}: " . (string) ($reclamation->description ?? ''),
                'detected_by' => $detectedBy,
                'detected_at' => now()->toDateString(),
                'deadline' => $reclamation->due_date ?: now()->addDays(30)->toDateString(),
            ];

            $nc = app(NonConformityService::class)->create($ncPayload);
        }

        // Réutilise d'abord une action corrective déjà liée à la NC.
        /** @var Action|null $action */
        $action = $nc->actions()
            ->where('actions.type', 'corrective')
            ->orderByDesc('actions.id')
            ->first();

        if (!$action && $processId) {
            $action = Action::query()->create([
                'site_id' => $reclamation->site_id,
                'process_id' => $processId,
                'source' => 'non_conformity',
                'source_type' => 'reclamation',
                'source_id' => (int) $reclamation->id,
                'type' => 'corrective',
                'priority' => 'high',
                'title' => 'Action corrective incident - ' . $refLabel,
                'description' => "Action corrective auto-générée depuis la clôture incident {$refLabel}.",
                'initiator_id' => $detectedBy,
                'responsible_id' => $reclamation->assigned_to ?: $detectedBy,
                'deadline' => $reclamation->due_date ?: now()->addDays(30)->toDateString(),
                'status' => 'planned',
                'complaint_id' => $reclamation->id,
                'm7_d4_traceability' => [
                    'origin' => 'incident_closure_auto_link',
                    'auto_generated' => true,
                    'reclamation_id' => (int) $reclamation->id,
                    'non_conformity_id' => (int) $nc->id,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        }

        if ($action) {
            $traceability = is_array($action->m7_d4_traceability) ? $action->m7_d4_traceability : [];
            $traceability['origin'] = $traceability['origin'] ?? 'incident_closure_auto_link';
            $traceability['auto_generated'] = $traceability['auto_generated'] ?? true;
            $traceability['reclamation_id'] = (int) $reclamation->id;
            $traceability['non_conformity_id'] = (int) $nc->id;

            $action->fill([
                'source_type' => 'reclamation',
                'source_id' => (int) $reclamation->id,
                'source' => $action->source ?: 'non_conformity',
                'complaint_id' => $reclamation->id,
                'm7_d4_traceability' => $traceability,
            ]);
            $action->save();

            $reclamation->addAction($action);
            $nc->addAction($action);
        }

        return [$nc, $action];
    }

    private function resolveProcessIdForIncident(Reclamation $reclamation): ?int
    {
        $fromLinkedActions = $reclamation->actions()
            ->whereNotNull('actions.process_id')
            ->orderByDesc('actions.id')
            ->value('actions.process_id');

        if (!empty($fromLinkedActions)) {
            return (int) $fromLinkedActions;
        }

        $fallbackProcessId = Process::query()
            ->where('site_id', $reclamation->site_id)
            ->orderByDesc('id')
            ->value('id');

        return $fallbackProcessId ? (int) $fallbackProcessId : null;
    }

    private function loadReclamationEventPayload(Reclamation $reclamation): Reclamation
    {
        if (method_exists($reclamation, 'company')) {
            return $reclamation->load(['company']);
        }

        return $reclamation;
    }

    private function syncReclamationTracking(
        Reclamation $reclamation,
        ?string $forcedStatus = null,
        ?int $forcedProgress = null,
        ?string $note = null
    ): void {
        $trackingStatus = $forcedStatus ?? $this->mapReclamationStatusToTrackingStatus($reclamation);
        $progressRate = $forcedProgress ?? $this->mapReclamationStatusToProgress($reclamation);
        $trackingNote = $note ?? sprintf('Sync réclamation %s (%s)', $reclamation->ref, (string) ($reclamation->status ?? ''));

        $userIds = collect([
            $reclamation->assigned_to,
            $reclamation->user_id,
            $this->actorId(),
        ])->filter()->map(fn ($id) => (int) $id)->unique()->values();

        foreach ($userIds as $userId) {
            TaskTracking::updateOrCreate(
                [
                    'user_id' => $userId,
                    'trackable_type' => Reclamation::class,
                    'trackable_id' => $reclamation->id,
                ],
                [
                    'status' => $trackingStatus,
                    'progress_rate' => $progressRate,
                    'notes' => $trackingNote,
                    'tracked_at' => now(),
                ]
            );
        }
    }

    private function mapReclamationStatusToTrackingStatus(Reclamation $reclamation): string
    {
        $status = strtolower((string) ($reclamation->status ?? 'open'));
        $workflow = strtolower((string) ($reclamation->workflowState?->code ?? ''));

        if (in_array($status, ['closed', 'resolved'], true) || in_array($workflow, ['closed', 'resolved'], true)) {
            return 'termine';
        }

        if (in_array($status, ['in_analysis', 'in_treatment', 'in_progress'], true)
            || in_array($workflow, ['in_analysis', 'in_treatment', 'in_progress'], true)) {
            return 'en_cours';
        }

        return 'non_demarre';
    }

    private function mapReclamationStatusToProgress(Reclamation $reclamation): int
    {
        return match ($this->mapReclamationStatusToTrackingStatus($reclamation)) {
            'termine' => 100,
            'en_cours' => 50,
            default => 0,
        };
    }

    private function notifyReclamationStakeholders(Reclamation $reclamation, string $eventType): void
    {
        try {
            $actor = $this->actor();
            $actorId = $actor?->id;

            $recipientIds = collect([
                $reclamation->assigned_to,
                $reclamation->user_id,
            ])
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => $actorId && (int) $id === (int) $actorId)
                ->values();

            if ($recipientIds->isEmpty()) {
                return;
            }

            $payload = [
                'reclamation_id' => $reclamation->id,
                'reclamation_ref' => $reclamation->ref,
                'reclamation_title' => $reclamation->title,
                'status' => $reclamation->status,
                'severity' => $reclamation->severity,
                'due_date' => optional($reclamation->due_date)->format('Y-m-d'),
                'actor_name' => $actor?->name ?? $actor?->full_name ?? $actor?->username,
            ];

            User::query()
                ->whereIn('id', $recipientIds->all())
                ->get()
                ->each(fn (User $user) => $user->notify(new SiteEventNotification($eventType, $payload, $actorId)));
        } catch (\Throwable $e) {
            // Ne pas interrompre le flux métier.
        }
    }

    private function actor(): ?User
    {
        $actor = Auth::user();
        return $actor instanceof User ? $actor : null;
    }

    private function actorId(): ?int
    {
        return $this->actor()?->id;
    }
}
