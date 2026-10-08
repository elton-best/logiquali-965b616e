<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\User;
use App\Models\AuditFinding;

class AuditFindingPolicy
{
    /**
     * Determine if the user can view any findings.
     */
    public function viewAny(User $user): bool
    {
        return PermissionHelper::can($user, 'audits.read');
    }

    /**
     * Determine if the user can view the finding.
     */
    public function view(User $user, AuditFinding $finding): bool
    {
        return PermissionHelper::can($user, 'audits.read');
    }

    /**
     * Determine if the user can create findings.
     */
    public function create(User $user): bool
    {
        return PermissionHelper::can($user, 'audits.update')
            || PermissionHelper::can($user, 'audits.manage');
    }

    /**
     * Determine if the user can update the finding.
     */
    public function update(User $user, AuditFinding $finding): bool
    {
        $audit = $finding->audit;

        if ($finding->resolved_at) {
            return $user->isSuperAdmin() || PermissionHelper::can($user, 'audits.manage');
        }

        return PermissionHelper::can($user, 'audits.update', function ($candidate) use ($audit) {
            return $audit->lead_auditor_id === $candidate->id
                || $audit->auditors->contains($candidate->id);
        }) || PermissionHelper::can($user, 'audits.manage');
    }

    /**
     * Determine if the user can delete the finding.
     */
    public function delete(User $user, AuditFinding $finding): bool
    {
        return $user->isSuperAdmin()
            && !$finding->nc_created;
    }

    /**
     * Determine if the user can resolve the finding.
     */
    public function resolve(User $user, AuditFinding $finding): bool
    {
        $audit = $finding->audit;

        return PermissionHelper::can($user, 'audits.update', function ($candidate) use ($audit, $finding) {
            return $audit->lead_auditor_id === $candidate->id
                || ($finding->process && $finding->process->owner_id === $candidate->id);
        }) || PermissionHelper::can($user, 'audits.manage');
    }
}
