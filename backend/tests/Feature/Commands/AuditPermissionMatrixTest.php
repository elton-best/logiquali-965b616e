<?php

namespace Tests\Feature\Commands;

use App\Models\Module;
use App\Models\Norm;
use App\Models\PermissionNormMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuditPermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_matrix_command_succeeds_when_only_mapped_or_system_permissions_exist(): void
    {
        $norm = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $module = Module::create([
            'code' => 'contexte',
            'name' => 'Contexte',
            'iso_point' => 4,
        ]);

        $mappedPermission = Permission::create([
            'name' => 'contexte.manage',
            'guard_name' => 'web',
        ]);

        PermissionNormMapping::create([
            'permission_id' => $mappedPermission->id,
            'norm_id' => $norm->id,
            'module_id' => $module->id,
            'is_shared' => true,
        ]);

        // Permission système volontairement non mappée.
        Permission::create([
            'name' => 'users.read',
            'guard_name' => 'web',
        ]);

        $code = Artisan::call('permissions:audit-matrix', [
            '--fail-on-critical' => true,
            '--only' => 'contexte.manage,users.read',
        ]);

        $this->assertSame(0, $code);
    }

    public function test_audit_matrix_command_fails_when_critical_permission_is_unmapped(): void
    {
        Permission::create([
            'name' => 'contexte.edit',
            'guard_name' => 'web',
        ]);

        $code = Artisan::call('permissions:audit-matrix', [
            '--fail-on-critical' => true,
            '--only' => 'contexte.edit',
        ]);

        $this->assertSame(1, $code);
    }
}
