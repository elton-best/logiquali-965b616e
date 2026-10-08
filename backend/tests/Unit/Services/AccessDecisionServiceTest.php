<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Access\AccessDecisionService;
use Mockery;
use Tests\TestCase;

class AccessDecisionServiceTest extends TestCase
{
    public function test_it_returns_full_permissions_for_enterprise_admin(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isSuperAdmin')->once()->andReturn(false);
        $user->shouldReceive('isEnterpriseAdmin')->once()->andReturn(true);
        $user->shouldNotReceive('canAccessModule');

        $service = app(AccessDecisionService::class);

        $permissions = $service->modulePermissions($user, 'planning');

        $this->assertSame([
            'read' => true,
            'create' => true,
            'update' => true,
            'delete' => true,
            'manage' => true,
        ], $permissions);
    }

    public function test_it_delegates_module_permissions_to_user_access_checks(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isSuperAdmin')->once()->andReturn(false);
        $user->shouldReceive('isEnterpriseAdmin')->once()->andReturn(false);
        $user->shouldReceive('isSiteManager')->once()->andReturn(false);

        $user->shouldReceive('canAccessModule')->once()->with('planning', 'read')->andReturn(true);
        $user->shouldReceive('canAccessModule')->once()->with('planning', 'create')->andReturn(false);
        $user->shouldReceive('canAccessModule')->once()->with('planning', 'update')->andReturn(true);
        $user->shouldReceive('canAccessModule')->once()->with('planning', 'delete')->andReturn(false);
        $user->shouldReceive('canAccessModule')->once()->with('planning', 'manage')->andReturn(false);

        $service = app(AccessDecisionService::class);

        $permissions = $service->modulePermissions($user, 'planning');

        $this->assertSame([
            'read' => true,
            'create' => false,
            'update' => true,
            'delete' => false,
            'manage' => false,
        ], $permissions);
    }

    public function test_it_falls_back_to_false_when_access_check_throws(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('isSuperAdmin')->once()->andReturn(false);
        $user->shouldReceive('isEnterpriseAdmin')->once()->andReturn(false);
        $user->shouldReceive('isSiteManager')->once()->andReturn(false);

        $user->shouldReceive('canAccessSection')->once()->with('duerp', 'read')->andThrow(new \RuntimeException('boom'));
        $user->shouldReceive('canAccessSection')->once()->with('duerp', 'create')->andReturn(false);
        $user->shouldReceive('canAccessSection')->once()->with('duerp', 'update')->andReturn(false);
        $user->shouldReceive('canAccessSection')->once()->with('duerp', 'delete')->andReturn(false);
        $user->shouldReceive('canAccessSection')->once()->with('duerp', 'manage')->andReturn(false);

        $service = app(AccessDecisionService::class);

        $permissions = $service->sectionPermissions($user, 'duerp');

        $this->assertFalse($permissions['read']);
        $this->assertFalse($permissions['create']);
        $this->assertFalse($permissions['update']);
        $this->assertFalse($permissions['delete']);
        $this->assertFalse($permissions['manage']);
    }
}

