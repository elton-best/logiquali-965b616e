<?php

namespace Tests\Unit\Services\Security;

use App\Services\Security\RoleScopeService;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class RoleScopeServiceTest extends TestCase
{
    private RoleScopeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RoleScopeService();
    }

    public function test_extract_enterprise_id_from_custom_role_name(): void
    {
        $this->assertSame(42, $this->service->extractEnterpriseIdFromCustomRoleName('custom_enterprise_42_hse'));
        $this->assertNull($this->service->extractEnterpriseIdFromCustomRoleName('admin_entreprise'));
    }

    public function test_is_any_custom_enterprise_role_name_detects_pattern(): void
    {
        $this->assertTrue($this->service->isAnyCustomEnterpriseRoleName('custom_enterprise_7_qualite'));
        $this->assertFalse($this->service->isAnyCustomEnterpriseRoleName('lecteur'));
    }

    public function test_is_role_scoped_to_enterprise_prefers_enterprise_id_column(): void
    {
        $role = new SpatieRole();
        $role->setAttribute('name', 'custom_enterprise_1_spoofed');
        $role->setAttribute('enterprise_id', 9);

        $this->assertTrue($this->service->isRoleScopedToEnterprise($role, (string) $role->name, 9));
        $this->assertFalse($this->service->isRoleScopedToEnterprise($role, (string) $role->name, 1));
    }

    public function test_is_role_scoped_to_enterprise_does_not_fallback_to_role_name_pattern(): void
    {
        $this->assertFalse($this->service->isRoleScopedToEnterprise(null, 'custom_enterprise_5_qhse', 5));
        $this->assertFalse($this->service->isRoleScopedToEnterprise(null, 'custom_enterprise_5_qhse', 3));
    }

    public function test_is_role_strictly_scoped_to_enterprise_uses_enterprise_id_column_only(): void
    {
        $role = new SpatieRole();
        $role->setAttribute('name', 'custom_enterprise_3_qhse');
        $role->setAttribute('enterprise_id', 8);

        $this->assertTrue($this->service->isRoleStrictlyScopedToEnterprise($role, 8));
        $this->assertFalse($this->service->isRoleStrictlyScopedToEnterprise($role, 3));
    }

    public function test_is_legacy_custom_role_without_enterprise_scope_detects_missing_scope(): void
    {
        $legacyCustomRole = new SpatieRole();
        $legacyCustomRole->setAttribute('name', 'custom_enterprise_11_legacy');
        $legacyCustomRole->setAttribute('enterprise_id', null);

        $scopedCustomRole = new SpatieRole();
        $scopedCustomRole->setAttribute('name', 'custom_enterprise_11_scoped');
        $scopedCustomRole->setAttribute('enterprise_id', 11);

        $this->assertTrue($this->service->isLegacyCustomRoleWithoutEnterpriseScope($legacyCustomRole));
        $this->assertFalse($this->service->isLegacyCustomRoleWithoutEnterpriseScope($scopedCustomRole));
        $this->assertFalse($this->service->isLegacyCustomRoleWithoutEnterpriseScope(null, 'lecteur'));
    }
}
