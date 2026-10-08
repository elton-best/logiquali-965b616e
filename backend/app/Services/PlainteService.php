<?php

namespace App\Services;

use App\Models\Plainte;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PlainteCreatedNotification;
use App\Notifications\PlainteAssignedNotification;
use App\Notifications\PlainteResolvedNotification;

class PlainteService
{
    public function create(array $data): Plainte
    {
        return DB::transaction(function () use ($data) {
            // Gérer l'anonymat
            if ($data['anonymous'] ?? false) {
                $data['plaignant_name'] = 'Anonyme';
                $data['plaignant_email'] = null;
                $data['plaignant_phone'] = null;
            }

            // Définir la date de réception si non fournie
            if (empty($data['received_date'])) {
                $data['received_date'] = now();
            }

            $plainte = Plainte::create($data);

            // Attacher les axes si fournis
            if (!empty($data['axes'])) {
                $plainte->attachAxes($data['axes']);
            }

            // Définir le workflow initial
            $plainte->setInitialState();

            // Notifier les responsables si pas anonyme
            if (!$plainte->anonymous) {
                $this->notifyStakeholders($plainte, 'created');
            }

            return $plainte->load(['site', 'workflowState']);
        });
    }

    public function update(Plainte $plainte, array $data): Plainte
    {
        return DB::transaction(function () use ($plainte, $data) {
            $plainte->update($data);

            if (isset($data['axes'])) {
                $plainte->syncAxes($data['axes']);
            }

            return $plainte->fresh(['site', 'assignedUser', 'workflowState']);
        });
    }

    public function assign(Plainte $plainte, int $userId, ?string $priority = null): Plainte
    {
        return DB::transaction(function () use ($plainte, $userId, $priority) {
            $updateData = ['assigned_to' => $userId];
            
            if ($priority) {
                $updateData['priority'] = $priority;
            }

            $plainte->update($updateData);

            // Transition vers état "en cours de traitement"
            $plainte->transitionTo('in_progress');

            // Notifier l'assigné
            $assignedUser = User::find($userId);
            if ($assignedUser && !$plainte->confidential) {
                $assignedUser->notify(new PlainteAssignedNotification($plainte));
            }

            return $plainte->fresh(['assignedUser', 'workflowState']);
        });
    }

    public function investigate(Plainte $plainte, array $data): Plainte
    {
        return DB::transaction(function () use ($plainte, $data) {
            $plainte->update([
                'analysis' => $data['analysis'],
                'corrective_actions' => $data['corrective_actions'] ?? null,
                'preventive_actions' => $data['preventive_actions'] ?? null,
            ]);

            // Transition vers état "investigation"
            $plainte->transitionTo('investigating');

            // Joindre documents si fournis
            if (!empty($data['evidence'])) {
                foreach ($data['evidence'] as $file) {
                    $plainte->attachDocument($file, 'evidence');
                }
            }

            return $plainte->fresh();
        });
    }

    public function respond(Plainte $plainte, string $response, bool $sendNotification = false): Plainte
    {
        return DB::transaction(function () use ($plainte, $response, $sendNotification) {
            $plainte->update([
                'immediate_response' => $response,
                'response_date' => now(),
                'responded_by' => auth()->id(),
            ]);

            // Si notification demandée et plainte non-anonyme
            if ($sendNotification && !$plainte->anonymous && $plainte->plaignant_email) {
                // Envoyer email au plaignant
                $this->sendResponseEmail($plainte);
            }

            return $plainte->fresh(['respondedByUser']);
        });
    }

    public function resolve(Plainte $plainte, array $data): Plainte
    {
        return DB::transaction(function () use ($plainte, $data) {
            $plainte->update([
                'corrective_actions' => $data['corrective_actions'],
                'preventive_actions' => $data['preventive_actions'] ?? null,
                'cost_impact' => $data['cost_impact'] ?? null,
            ]);

            // Transition vers état "résolu"
            $plainte->transitionTo('resolved');

            // Notifier si demandé
            if ($data['notification_sent'] ?? false) {
                $this->notifyStakeholders($plainte, 'resolved');
            }

            return $plainte->fresh();
        });
    }

    public function close(Plainte $plainte, array $data): Plainte
    {
        return DB::transaction(function () use ($plainte, $data) {
            $plainte->update([
                'satisfaction_rating' => $data['satisfaction_rating'] ?? null,
                'satisfaction_comment' => $data['satisfaction_comment'] ?? null,
                'satisfaction_date' => now(),
                'closed_date' => now(),
            ]);

            // Transition vers état "fermé"
            $plainte->transitionTo('closed');

            return $plainte->fresh();
        });
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Plainte::query();

        if (!empty($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (!empty($filters['year'])) {
            $query->whereYear('created_at', $filters['year']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return [
            'total' => $query->count(),
            'by_severity' => $this->countBySeverity($query),
            'by_category' => $this->countByCategory($query),
            'by_stakeholder_type' => $this->countByStakeholderType($query),
            'by_status' => $this->countByStatus($query),
            'confidential_count' => (clone $query)->where('confidential', true)->count(),
            'anonymous_count' => (clone $query)->where('anonymous', true)->count(),
            'overdue' => (clone $query)->where('due_date', '<', now())->whereNull('closed_date')->count(),
            'avg_resolution_time' => $this->calculateAvgResolutionTime($query),
            'satisfaction_avg' => (clone $query)->whereNotNull('satisfaction_rating')->avg('satisfaction_rating'),
        ];
    }

    private function countBySeverity($query): array
    {
        return (clone $query)->select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();
    }

    private function countByCategory($query): array
    {
        return (clone $query)->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    private function countByStakeholderType($query): array
    {
        return (clone $query)->select('stakeholder_type', DB::raw('count(*) as count'))
            ->groupBy('stakeholder_type')
            ->pluck('count', 'stakeholder_type')
            ->toArray();
    }

    private function countByStatus($query): array
    {
        return (clone $query)
            ->join('workflow_states', 'plaintes.workflow_state_id', '=', 'workflow_states.id')
            ->select('workflow_states.name', DB::raw('count(*) as count'))
            ->groupBy('workflow_states.name')
            ->pluck('count', 'name')
            ->toArray();
    }

    private function calculateAvgResolutionTime($query): ?float
    {
        return (clone $query)
            ->whereNotNull('closed_date')
            ->selectRaw('AVG(DATEDIFF(closed_date, received_date)) as avg_days')
            ->value('avg_days');
    }

    private function notifyStakeholders(Plainte $plainte, string $event): void
    {
        // Logique de notification selon l'événement
        // À implémenter selon les besoins
    }

    private function sendResponseEmail(Plainte $plainte): void
    {
        // Logique d'envoi d'email
        // À implémenter avec le système de mail
    }

    public function export(string $format, array $filters = []): string
    {
        // Logique d'export
        // À implémenter selon les besoins
        return '/exports/plaintes_' . now()->format('Ymd_His') . '.' . $format;
    }
}
