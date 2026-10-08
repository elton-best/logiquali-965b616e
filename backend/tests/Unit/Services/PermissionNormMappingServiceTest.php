<?php

namespace Tests\Unit\Services;

use App\Models\Module;
use App\Models\Norm;
use App\Models\PermissionNormMapping;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Offer;
use App\Models\Site;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use App\Models\User;
use App\Services\PermissionNormMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionNormMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PermissionNormMappingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PermissionNormMappingService::class);

        // Seed permissions, norms, modules
        $this->seedTestData();
    }

    protected function seedTestData(): void
    {
        // Create norms
        $this->iso9001 = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $this->iso14001 = Norm::create([
            'code' => 'ISO 14001:2015',
            'name' => 'Management environnemental',
            'domain' => 'environment',
            'status' => 'published',
        ]);

        // Create modules
        $this->contexte = Module::create([
            'code' => 'contexte',
            'name' => 'Contexte',
            'iso_point' => 4,
        ]);

        $this->realisation = Module::create([
            'code' => 'realisation',
            'name' => 'Réalisation',
            'iso_point' => 8,
        ]);

        $this->subModuleContexte = SubModule::create([
            'module_id' => $this->contexte->id,
            'name' => 'Compréhension organisme',
            'code' => 'comprehension_organisme',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $this->sectionContexte = SubModuleSection::create([
            'sub_module_id' => $this->subModuleContexte->id,
            'name' => 'Parties intéressées',
            'code' => 'parties_interessees',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        // Create permissions
        $this->permContexteManage = Permission::create([
            'name' => 'contexte.manage',
            'guard_name' => 'web',
        ]);

        $this->permRealisationManage = Permission::create([
            'name' => 'realisation.manage',
            'guard_name' => 'web',
        ]);

        // Create mappings: contexte.manage is SHARED (both norms)
        PermissionNormMapping::create([
            'permission_id' => $this->permContexteManage->id,
            'norm_id' => $this->iso9001->id,
            'module_id' => $this->contexte->id,
            'sub_module_id' => $this->subModuleContexte->id,
            'section_id' => $this->sectionContexte->id,
            'is_shared' => true,
        ]);

        PermissionNormMapping::create([
            'permission_id' => $this->permContexteManage->id,
            'norm_id' => $this->iso14001->id,
            'module_id' => $this->contexte->id,
            'sub_module_id' => $this->subModuleContexte->id,
            'section_id' => $this->sectionContexte->id,
            'is_shared' => true,
        ]);

        // realisation.manage is SPECIFIC (ISO-9001 only)
        PermissionNormMapping::create([
            'permission_id' => $this->permRealisationManage->id,
            'norm_id' => $this->iso9001->id,
            'module_id' => $this->realisation->id,
            'is_shared' => false,
        ]);
    }

    /**
     * Test: Get permissions for a specific norm
     */
    public function test_get_permissions_for_norm()
    {
        $perms = $this->service->getPermissionsForNorm($this->iso9001->id);

        // Should have both contexte.manage and realisation.manage
        $this->assertEquals(2, $perms->count());
        $this->assertTrue($perms->pluck('name')->contains('contexte.manage'));
        $this->assertTrue($perms->pluck('name')->contains('realisation.manage'));
    }

    /**
     * Test: Get permissions for a module
     */
    public function test_get_permissions_for_module()
    {
        $perms = $this->service->getPermissionsForModule($this->contexte->id);

        // Should have only contexte.manage
        $this->assertEquals(1, $perms->count());
        $this->assertTrue($perms->pluck('name')->contains('contexte.manage'));
    }

    /**
     * Test: Get permissions for norm + module combo
     */
    public function test_get_permissions_for_norm_module()
    {
        $perms = $this->service->getPermissionsForNormModule($this->iso9001->id, $this->contexte->id);

        $this->assertEquals(1, $perms->count());
        $this->assertTrue($perms->pluck('name')->contains('contexte.manage'));
    }

    public function test_get_permissions_for_norm_sub_module()
    {
        $perms = $this->service->getPermissionsForNormSubModule($this->iso9001->id, $this->subModuleContexte->id);

        $this->assertEquals(1, $perms->count());
        $this->assertTrue($perms->pluck('name')->contains('contexte.manage'));
    }

    public function test_get_permissions_for_norm_section()
    {
        $perms = $this->service->getPermissionsForNormSection($this->iso9001->id, $this->sectionContexte->id);

        $this->assertEquals(1, $perms->count());
        $this->assertTrue($perms->pluck('name')->contains('contexte.manage'));
    }

    /**
     * Test: Check if permission is shared
     */
    public function test_is_permission_shared()
    {
        $this->assertTrue($this->service->isPermissionShared($this->permContexteManage->id));
        $this->assertFalse($this->service->isPermissionShared($this->permRealisationManage->id));
    }

    /**
     * Test: Get norms for a permission
     */
    public function test_get_norms_for_permission()
    {
        // contexte.manage should be in BOTH norms
        $norms = $this->service->getNormsForPermission($this->permContexteManage->id);
        $this->assertEquals(2, $norms->count());
        $this->assertTrue($norms->pluck('id')->contains($this->iso9001->id));
        $this->assertTrue($norms->pluck('id')->contains($this->iso14001->id));

        // realisation.manage should be in ISO-9001 only
        $norms = $this->service->getNormsForPermission($this->permRealisationManage->id);
        $this->assertEquals(1, $norms->count());
        $this->assertTrue($norms->pluck('id')->contains($this->iso9001->id));
    }

    /**
     * Test: Validate permission assignment - should pass for valid assignment
     */
    public function test_validate_permission_assignment_valid()
    {
        $user = $this->makeUserWithSubscribedNorm($this->iso9001);

        $result = $this->service->validatePermissionAssignment($user, 'contexte.manage', $this->iso9001->id);

        $this->assertTrue($result['valid']);
        $this->assertNotNull($result['permission']);
    }

    /**
     * Test: Validate permission assignment - should fail for non-existent permission
     */
    public function test_validate_permission_assignment_invalid_permission()
    {
        $user = $this->makeUserWithSubscribedNorm($this->iso9001);

        $result = $this->service->validatePermissionAssignment($user, 'nonexistent.permission', $this->iso9001->id);

        $this->assertFalse($result['valid']);
    }

    /**
     * Test: Validate permission assignment - should fail for unsubscribed norm
     */
    public function test_validate_permission_assignment_unsubscribed_norm()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $result = $this->service->validatePermissionAssignment($user, 'contexte.manage', $this->iso9001->id);

        $this->assertFalse($result['valid']);
    }

    /**
     * Test: Get shared permissions
     */
    public function test_get_shared_permissions()
    {
        $shared = $this->service->getSharedPermissions();

        $this->assertEquals(1, $shared->count());
        $this->assertTrue($shared->pluck('name')->contains('contexte.manage'));
    }

    /**
     * Test: Get specific permissions
     */
    public function test_get_specific_permissions()
    {
        $specific = $this->service->getSpecificPermissions();

        $this->assertEquals(1, $specific->count());
        $this->assertTrue($specific->pluck('name')->contains('realisation.manage'));
    }

    private function makeUserWithSubscribedNorm(Norm $norm): User
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $offer = Offer::factory()->create(['is_active' => true]);
        $offer->norms()->syncWithoutDetaching([$norm->id]);

        EnterpriseSubscription::factory()->create([
            'site_id' => $site->id,
            'offer_id' => $offer->id,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
        ]);

        return User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
    }
}
