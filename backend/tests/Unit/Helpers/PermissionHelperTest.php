<?php

namespace Tests\Unit\Helpers;

use App\Helpers\PermissionHelper;
use App\Models\User;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PermissionHelperTest extends TestCase
{
    public function test_can_returns_true_when_permission_is_granted_and_scope_is_active(): void
    {
        config()->set('authz.enforce_active_scope', true);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(12344);
        $user->shouldReceive('isSuperAdmin')->twice()->andReturn(false);
        $user->shouldReceive('hasPermissionTo')->once()->with('actions.read')->andReturn(true);
        $user->shouldReceive('isCompanyUser')->twice()->andReturn(true);
        $user->shouldReceive('getActiveScopedPermissionNames')
            ->once()
            ->andReturn(new Collection(['actions.read']));

        $this->assertTrue(PermissionHelper::can($user, 'actions.read'));
    }

    public function test_can_returns_false_when_permission_is_granted_but_out_of_scope(): void
    {
        config()->set('authz.enforce_active_scope', true);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(12345);
        $user->shouldReceive('isSuperAdmin')->twice()->andReturn(false);
        $user->shouldReceive('hasPermissionTo')->once()->with('actions.read')->andReturn(true);
        $user->shouldReceive('isCompanyUser')->twice()->andReturn(true);
        $user->shouldReceive('getActiveScopedPermissionNames')
            ->once()
            ->andReturn(new Collection(['dashboard.read']));

        $this->assertFalse(PermissionHelper::can($user, 'actions.read'));
    }

    public function test_can_keeps_legacy_behavior_when_scope_enforcement_is_disabled(): void
    {
        config()->set('authz.enforce_active_scope', false);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isSuperAdmin')->once()->andReturn(false);
        $user->shouldReceive('hasPermissionTo')->once()->with('actions.read')->andReturn(true);

        $this->assertTrue(PermissionHelper::can($user, 'actions.read'));
    }

    public function test_can_within_scope_requires_scope_callback_to_pass(): void
    {
        config()->set('authz.enforce_active_scope', false);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('isSuperAdmin')->twice()->andReturn(false);
        $user->shouldReceive('hasPermissionTo')->twice()->with('actions.update')->andReturn(true);

        $this->assertFalse(PermissionHelper::canWithinScope($user, 'actions.update', fn () => false));
        $this->assertTrue(PermissionHelper::canWithinScope($user, 'actions.update', fn () => true));
    }
}
