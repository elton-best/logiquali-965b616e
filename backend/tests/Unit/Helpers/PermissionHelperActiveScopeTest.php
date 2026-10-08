<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PermissionHelper;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionHelperActiveScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_enforcement_flag_applies_to_company_users(): void
    {
        $user = $this->makeCompanyUser();
        config([
            'authz.enforce_active_scope' => true,
            'authz.enforce_active_scope_cohort_user_ids' => [],
            'authz.enforce_active_scope_cohort_enterprise_ids' => [],
        ]);

        $this->assertTrue(PermissionHelper::isActiveScopeEnforcedForUser($user));
    }

    public function test_enforcement_is_disabled_when_flag_and_cohorts_are_empty(): void
    {
        $user = $this->makeCompanyUser();
        config([
            'authz.enforce_active_scope' => false,
            'authz.enforce_active_scope_cohort_user_ids' => [],
            'authz.enforce_active_scope_cohort_enterprise_ids' => [],
        ]);

        $this->assertFalse(PermissionHelper::isActiveScopeEnforcedForUser($user));
    }

    public function test_user_cohort_enables_enforcement_even_when_global_flag_is_disabled(): void
    {
        $user = $this->makeCompanyUser();
        config([
            'authz.enforce_active_scope' => false,
            'authz.enforce_active_scope_cohort_user_ids' => [$user->id],
            'authz.enforce_active_scope_cohort_enterprise_ids' => [],
        ]);

        $this->assertTrue(PermissionHelper::isActiveScopeEnforcedForUser($user));
    }

    public function test_enterprise_cohort_enables_enforcement_even_when_global_flag_is_disabled(): void
    {
        $user = $this->makeCompanyUser();
        config([
            'authz.enforce_active_scope' => false,
            'authz.enforce_active_scope_cohort_user_ids' => [],
            'authz.enforce_active_scope_cohort_enterprise_ids' => [(int) $user->enterprise_id],
        ]);

        $this->assertTrue(PermissionHelper::isActiveScopeEnforcedForUser($user));
    }

    private function makeCompanyUser(): User
    {
        $enterprise = Enterprise::factory()->approved()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        return User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
        ]);
    }
}

