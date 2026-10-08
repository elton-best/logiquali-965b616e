<?php

namespace App\Policies;

use App\Models\Process;
use App\Models\User;
use App\Models\Action;
use App\Helpers\PermissionHelper;
use Illuminate\Auth\Access\Response;

class ProcessPolicy
{
    private function isInScope(User $user, Process $process): bool
    {
        return ($user->site_id && $user->site_id === $process->site_id)
            || ($user->enterprise_id === $process->enterprise_id);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Process $process): bool
    {
        return PermissionHelper::canWithinScope($user, 'processes.read', function($user) use ($process) {
            // Utilisateur peut voir les processus de son site ou entreprise
            return $this->isInScope($user, $process);
        });
    }

    /**
     * Access dédié au dashboard actions de revue processus.
     * Autorise: lecteurs process dans le scope, pilote/copilote, ou responsable d'au moins une action liée.
     */
    public function viewReviewDashboardActions(User $user, Process $process): bool
    {
        if (!$this->isInScope($user, $process)) {
            return false;
        }

        if (PermissionHelper::canWithinScope($user, 'processes.read')) {
            return true;
        }

        if ((int) $process->pilot_id === (int) $user->id || (int) $process->copilot_id === (int) $user->id) {
            return true;
        }

        return Action::query()
            ->where('process_id', (int) $process->id)
            ->where('responsible_id', (int) $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'processes');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Process $process): bool
    {
        return PermissionHelper::canWithinScope($user, 'processes.update', function($user) use ($process) {
            $sameScope = $this->isInScope($user, $process);
            if (!$sameScope) {
                return false;
            }

            // Pilot/copilot peut modifier si draft
            return $process->status === 'draft'
                && ($process->pilot_id === $user->id || $process->copilot_id === $user->id);
        }) || PermissionHelper::canWithinScope($user, 'processes.manage', function($user) use ($process) {
            return $this->isInScope($user, $process);
        });
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Process $process): bool
    {
        // Seulement si draft
        if ($process->status !== 'draft') {
            return false;
        }

        return PermissionHelper::canWithinScope($user, 'processes.delete', function($user) use ($process) {
            $sameScope = $this->isInScope($user, $process);
            if (!$sameScope) {
                return false;
            }

            // Pilote peut supprimer son processus
            return $process->pilot_id === $user->id;
        }) || PermissionHelper::canWithinScope($user, 'processes.manage', function($user) use ($process) {
            return $this->isInScope($user, $process);
        });
    }

    /**
     * Determine whether the user can verify the process (draft → in_review).
     */
    public function verify(User $user, Process $process): bool
    {
        if ($process->status !== 'draft') {
            return false;
        }

        return PermissionHelper::can($user, 'processes.manage');
    }

    /**
     * Determine whether the user can validate the process (in_review → active).
     */
    public function validate(User $user, Process $process): bool
    {
        if ($process->status !== 'in_review') {
            return false;
        }

        return PermissionHelper::can($user, 'processes.manage');
    }

    /**
     * Determine whether the user can reject the process.
     */
    public function reject(User $user, Process $process): bool
    {
        if (!in_array($process->status, ['in_review', 'validated'])) {
            return false;
        }

        return PermissionHelper::canWithinScope($user, 'processes.manage', function($user) use ($process) {
            return $this->isInScope($user, $process);
        }) || PermissionHelper::canWithinScope($user, 'processes.update', function($user) use ($process) {
            return $this->isInScope($user, $process);
        });
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Process $process): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Process $process): bool
    {
        return $user->isSuperAdmin();
    }
}
