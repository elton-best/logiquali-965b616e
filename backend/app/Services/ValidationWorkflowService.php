<?php

namespace App\Services;

use App\Models\Action;
use App\Models\AspectEnvironnemental;
use App\Models\Communication;
use App\Models\Duerp;
use App\Models\Document;
use App\Models\EmergencyProcedure;
use App\Models\JobDescription;
use App\Models\Modification;
use App\Models\NonConformity;
use App\Models\OperationalProject;
use App\Models\Opportunity;
use App\Models\Plan;
use App\Models\Reclamation;
use App\Models\Risk;
use App\Models\User;
use App\Models\ValidationWorkflow;
use App\Models\ValidationWorkflowHistory;
use App\Notifications\WorkflowNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ValidationWorkflowService
{
    public const DRAFT = 'brouillon';
    public const VERIFIED = 'verifie_rq';
    public const APPROVED = 'approuve_ceo';
    public const REFUSED = 'refuse';

    /** @var array<string, class-string<Model>> */
    private const OBJECT_TYPES = [
        'action' => Action::class,
        'aes' => AspectEnvironnemental::class,
        'communication' => Communication::class,
        'document' => Document::class,
        'duerp' => Duerp::class,
        'emergency' => EmergencyProcedure::class,
        'fiche_poste' => JobDescription::class,
        'modification' => Modification::class,
        'non_conformite' => NonConformity::class,
        'projet' => OperationalProject::class,
        'opportunite' => Opportunity::class,
        'plan_sm' => Plan::class,
        'reclamation' => Reclamation::class,
        'risque' => Risk::class,
    ];

    public function objectType(string $type): string
    {
        $normalized = Str::of($type)->lower()->replace(['\\', '/'], '_')->toString();
        $class = self::OBJECT_TYPES[$normalized] ?? null;
        if (!$class) {
            throw new NotFoundHttpException('Type d’objet non validable.');
        }

        return $class;
    }

    public function resolveObject(User $user, string $type, int $id): Model
    {
        $object = $this->objectType($type)::query()->find($id);
        if (!$object || !$this->isInUserScope($user, $object)) {
            throw new NotFoundHttpException('Objet introuvable ou hors périmètre.');
        }

        return $object;
    }

    public function create(User $user, string $type, int $id, ?string $comment = null, array $metadata = []): ValidationWorkflow
    {
        $object = $this->resolveObject($user, $type, $id);
        $normalizedType = Str::of($type)->lower()->toString();
        $open = ValidationWorkflow::query()
            ->where('objet_type', $normalizedType)
            ->where('objet_id', $object->getKey())
            ->whereIn('status', [self::DRAFT, self::VERIFIED])
            ->latest('id')
            ->first();

        if ($open) {
            return $open->load('histories');
        }

        return DB::transaction(function () use ($user, $normalizedType, $object, $comment, $metadata): ValidationWorkflow {
            $workflow = ValidationWorkflow::create([
                'enterprise_id' => $this->enterpriseId($object, $user),
                'objet_type' => $normalizedType,
                'objet_id' => $object->getKey(),
                'status' => self::DRAFT,
                'commentaire' => $comment,
                'metadata' => $metadata,
                'created_by' => $user->id,
            ]);

            $this->record($workflow, null, self::DRAFT, $user, $comment);
            return $workflow->load('histories');
        });
    }

    public function transition(ValidationWorkflow $workflow, string $target, User $actor, ?string $comment = null): ValidationWorkflow
    {
        $allowed = [
            self::DRAFT => [self::VERIFIED, self::REFUSED],
            self::VERIFIED => [self::APPROVED, self::REFUSED],
            self::APPROVED => [],
            self::REFUSED => [self::DRAFT],
        ];

        if (!in_array($target, $allowed[$workflow->status] ?? [], true)) {
            abort(422, "Transition de validation invalide: {$workflow->status} -> {$target}.");
        }

        return DB::transaction(function () use ($workflow, $target, $actor, $comment): ValidationWorkflow {
            $from = $workflow->status;
            $workflow->status = $target;
            $workflow->commentaire = $comment ?? $workflow->commentaire;

            $now = now();
            if ($target === self::VERIFIED) {
                $workflow->verified_by = $actor->id;
                $workflow->verified_at = $now;
            } elseif ($target === self::APPROVED) {
                $workflow->approved_by = $actor->id;
                $workflow->approved_at = $now;
            } elseif ($target === self::REFUSED) {
                $workflow->refused_by = $actor->id;
                $workflow->refused_at = $now;
            }

            $workflow->save();
            $this->syncObjectWorkflowState($workflow, $target, $actor);
            $this->record($workflow, $from, $target, $actor, $comment);
            $this->notifyNextActors($workflow, $target);

            return $workflow->fresh(['histories', 'creator', 'verifier', 'approver']);
        });
    }

    private function record(ValidationWorkflow $workflow, ?string $from, string $to, User $actor, ?string $comment): void
    {
        ValidationWorkflowHistory::create([
            'validation_workflow_id' => $workflow->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->id,
            'commentaire' => $comment,
        ]);
    }

    private function notifyNextActors(ValidationWorkflow $workflow, string $status): void
    {
        $event = match ($status) {
            self::VERIFIED => ['validation.approval_requested', $this->usersWithRoles($workflow->enterprise_id, ['ceo', 'directeur_general', 'admin_entreprise']), 'Validation CEO attendue.'],
            self::APPROVED => ['validation.approved', $workflow->creator ? collect([$workflow->creator]) : collect(), 'Validation approuvée par le CEO.'],
            self::REFUSED => ['validation.refused', $workflow->creator ? collect([$workflow->creator]) : collect(), 'Validation refusée.'],
            default => [null, collect(), null],
        };

        if (!$event[0]) {
            return;
        }

        foreach ($event[1] as $recipient) {
            $recipient->notify(new WorkflowNotification($event[0], $workflow, $event[2]));
        }
    }

    private function syncObjectWorkflowState(ValidationWorkflow $workflow, string $target, User $actor): void
    {
        if ($workflow->objet_type !== 'modification') {
            return;
        }

        $modification = Modification::query()->find($workflow->objet_id);
        if (!$modification) {
            return;
        }

        $payload = ['workflow_status' => $target];
        if ($target === self::APPROVED) {
            $payload += [
                'status' => 'approved',
                'validated_by' => $actor->id,
                'validated_at' => now(),
                'approved_at' => now(),
            ];
        } elseif ($target === self::REFUSED) {
            $payload['status'] = 'rejected';
        }

        $modification->update($payload);
    }

    private function usersWithRoles(?int $enterpriseId, array $roles)
    {
        if (!$enterpriseId) {
            return collect();
        }

        $accepted = array_map('mb_strtolower', $roles);
        return User::query()
            ->where('enterprise_id', $enterpriseId)
            ->where('is_active', true)
            ->with('roles')
            ->get()
            ->filter(function (User $user) use ($accepted): bool {
                $names = $user->roles->pluck('name')->map(fn ($name) => mb_strtolower((string) $name))->all();
                $names[] = mb_strtolower((string) $user->role);
                return collect($names)->contains(fn (string $name): bool => in_array($name, $accepted, true));
            })
            ->values();
    }

    private function isInUserScope(User $user, Model $object): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $enterpriseId = $this->enterpriseId($object, $user);
        if (!$user->enterprise_id || $enterpriseId !== (int) $user->enterprise_id) {
            return false;
        }

        $siteId = (int) ($object->getAttribute('site_id') ?? 0);
        return !$user->site_id || !$siteId || $siteId === (int) $user->site_id || $user->isEnterpriseAdmin();
    }

    private function enterpriseId(Model $object, User $user): ?int
    {
        $direct = $object->getAttribute('enterprise_id');
        if ($direct) {
            return (int) $direct;
        }

        $siteId = $object->getAttribute('site_id');
        if ($siteId) {
            return (int) DB::table('sites')->where('id', $siteId)->value('enterprise_id');
        }

        return $user->enterprise_id ? (int) $user->enterprise_id : null;
    }
}
