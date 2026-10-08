<?php

namespace App\Policies;

use App\Helpers\PermissionHelper;
use App\Models\User;
use App\Models\AuditProgram;

class AuditProgramPolicy
{
    /**
     * Determine if the user can view any audit programs.
     */
    public function viewAny(User $user): bool
    {
        return PermissionHelper::can($user, 'audits.read');
    }

    /**
     * Determine if the user can view the audit program.
     */
    public function view(User $user, AuditProgram $program): bool
    {
        return PermissionHelper::can($user, 'audits.read');
    }

    /**
     * Determine if the user can create audit programs.
     */
    public function create(User $user): bool
    {
        return PermissionHelper::can($user, 'audit_programs.create')
            || PermissionHelper::can($user, 'audits.manage');
    }

    /**
     * Determine if the user can update the audit program.
     */
    public function update(User $user, AuditProgram $program): bool
    {
        return PermissionHelper::can($user, 'audit_programs.update')
            || PermissionHelper::can($user, 'audits.manage');
    }

    /**
     * Determine if the user can delete the audit program.
     */
    public function delete(User $user, AuditProgram $program): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine if the user can validate the audit program.
     */
    public function validate(User $user, AuditProgram $program): bool
    {
        return PermissionHelper::can($user, 'audit_programs.manage')
            || PermissionHelper::can($user, 'audits.manage');
    }
}
