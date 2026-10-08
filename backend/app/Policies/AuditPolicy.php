<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Audit;
use App\Helpers\PermissionHelper;

class AuditPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::can($user, 'audits.read');
    }

    public function view(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.read', function($user) use ($audit) {
            return ($user->site_id && $audit->site_id && $user->site_id === $audit->site_id) ||
                   ($user->enterprise_id && $audit->enterprise_id && $user->enterprise_id === $audit->enterprise_id);
        });
    }

    public function create(User $user): bool
    {
        return PermissionHelper::canCreate($user, 'audits');
    }

    public function update(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.update', function($user) use ($audit) {
            return $audit->lead_auditor_id === $user->id;
        }) || PermissionHelper::canWithinScope($user, 'audits.manage');
    }

    public function delete(User $user, Audit $audit): bool
    {
        return PermissionHelper::canDelete($user, 'audits');
    }

    public function start(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.update', function($user) use ($audit) {
            return $audit->lead_auditor_id === $user->id;
        }) || PermissionHelper::canWithinScope($user, 'audits.manage');
    }

    public function addChecklist(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.update', function($user) use ($audit) {
            return $audit->lead_auditor_id === $user->id;
        }) || PermissionHelper::canWithinScope($user, 'audits.manage');
    }

    public function addFinding(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.update', function($user) use ($audit) {
            return $audit->auditors->contains($user->id) ||
                   $audit->lead_auditor_id === $user->id;
        }) || PermissionHelper::canWithinScope($user, 'audits.manage');
    }

    public function finalize(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.manage', function($user) use ($audit) {
            return $audit->lead_auditor_id === $user->id;
        });
    }

    public function sendInvitations(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.update', function($user) use ($audit) {
            return $audit->lead_auditor_id === $user->id;
        }) || PermissionHelper::canWithinScope($user, 'audits.manage');
    }

    public function downloadReport(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.read', function($user) use ($audit) {
            return $audit->auditors->contains($user->id) ||
                   $audit->auditees->contains($user->id) ||
                   $audit->lead_auditor_id === $user->id;
        });
    }

    public function complete(User $user, Audit $audit): bool
    {
        return PermissionHelper::canWithinScope($user, 'audits.manage', function($user) use ($audit) {
            return $audit->lead_auditor_id === $user->id;
        });
    }

    public function viewStatistics(User $user): bool
    {
        return PermissionHelper::can($user, 'audits.read');
    }
}
