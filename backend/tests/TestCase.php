<?php

namespace Tests;

use App\Models\Axe;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Désactiver MFA pour tous les tests
        config(['mfa.step_up_enabled' => false]);

        // Prevent stale permissions/roles cache across tests (frequent source of random 403s)
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Create default axes for testing
        $this->createDefaultAxes();
        
        // Create default permissions for testing
        $this->createDefaultPermissions();
    }

    protected function createDefaultAxes(): void
    {
        $axes = ['Q', 'HS', 'E'];
        foreach ($axes as $code) {
            Axe::firstOrCreate(
                ['code' => $code],
                ['name' => $this->getAxeName($code)]
            );
        }
    }

    protected function createDefaultPermissions(): void
    {
        $permissions = [
            'dashboard.read',
            'view_nomenclature',
            'configure_nomenclature',
            'view_documents',
            'create_documents',
            'verify_documents',
            'approve_documents',
            'import_documents',
            'process_reviews.read',
            'process_reviews.update',
            'training_plans.read',
            'training_plans.create',
            'training_plans.update',
            'training_plans.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }

    private function getAxeName(string $code): string
    {
        return match($code) {
            'Q' => 'Qualité',
            'HS' => 'Hygiène Sécurité',
            'E' => 'Environnement',
            default => $code,
        };
    }
}
