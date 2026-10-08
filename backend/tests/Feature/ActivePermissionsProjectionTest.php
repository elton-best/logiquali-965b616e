<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Module;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\PermissionNormMapping;
use App\Models\Site;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivePermissionsProjectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_permissions_projection_includes_submodule_and_section_mapping(): void
    {
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

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

        $subModule = SubModule::create([
            'module_id' => $module->id,
            'name' => 'Compréhension organisme',
            'code' => 'comprehension_organisme',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $section = SubModuleSection::create([
            'sub_module_id' => $subModule->id,
            'name' => 'Parties intéressées',
            'code' => 'parties_interessees',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $permission = Permission::create([
            'name' => 'contexte.manage',
            'guard_name' => 'web',
        ]);

        PermissionNormMapping::create([
            'permission_id' => $permission->id,
            'norm_id' => $norm->id,
            'module_id' => $module->id,
            'sub_module_id' => $subModule->id,
            'section_id' => $section->id,
            'is_shared' => true,
        ]);

        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'is_headquarter' => true,
        ]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);
        $user->assignRole($adminRole);

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

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/available-permissions/active');

        $response->assertStatus(200);

        $permissions = collect($response->json('permissions', []))
            ->flatMap(fn ($moduleGroup) => collect($moduleGroup['permissions'] ?? []));

        $target = $permissions->firstWhere('name', 'contexte.manage');
        $this->assertNotNull($target, 'La permission contexte.manage doit être présente dans le catalogue actif.');

        $mapping = collect($target['mappings'] ?? [])->first();
        $this->assertNotNull($mapping, 'La permission contexte.manage doit exposer au moins un mapping.');
        $this->assertSame($norm->id, $mapping['norm_id'] ?? null);
        $this->assertSame($module->id, $mapping['module_id'] ?? null);
        $this->assertSame($subModule->id, $mapping['sub_module_id'] ?? null);
        $this->assertSame($section->id, $mapping['section_id'] ?? null);
        $this->assertSame('contexte', $mapping['module_code'] ?? null);
        $this->assertSame('comprehension_organisme', $mapping['sub_module_code'] ?? null);
        $this->assertSame('parties_interessees', $mapping['section_code'] ?? null);
    }

    public function test_active_permissions_projection_returns_multiple_granular_mappings_in_same_payload(): void
    {
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        $norm = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $moduleContexte = Module::create([
            'code' => 'contexte',
            'name' => 'Contexte',
            'iso_point' => 4,
        ]);

        $subContexte = SubModule::create([
            'module_id' => $moduleContexte->id,
            'name' => 'Compréhension organisme',
            'code' => 'comprehension_organisme',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $sectionContexte = SubModuleSection::create([
            'sub_module_id' => $subContexte->id,
            'name' => 'Parties intéressées',
            'code' => 'parties_interessees',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $moduleLeadership = Module::create([
            'code' => 'leadership',
            'name' => 'Leadership',
            'iso_point' => 5,
        ]);

        $subLeadership = SubModule::create([
            'module_id' => $moduleLeadership->id,
            'name' => 'Rôles et responsabilités',
            'code' => 'roles_responsabilites',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $sectionLeadership = SubModuleSection::create([
            'sub_module_id' => $subLeadership->id,
            'name' => 'Organigramme',
            'code' => 'organigramme',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $permissionContexte = Permission::create([
            'name' => 'contexte.manage',
            'guard_name' => 'web',
        ]);
        $permissionLeadership = Permission::create([
            'name' => 'leadership.manage',
            'guard_name' => 'web',
        ]);

        PermissionNormMapping::create([
            'permission_id' => $permissionContexte->id,
            'norm_id' => $norm->id,
            'module_id' => $moduleContexte->id,
            'sub_module_id' => $subContexte->id,
            'section_id' => $sectionContexte->id,
            'is_shared' => true,
        ]);
        PermissionNormMapping::create([
            'permission_id' => $permissionLeadership->id,
            'norm_id' => $norm->id,
            'module_id' => $moduleLeadership->id,
            'sub_module_id' => $subLeadership->id,
            'section_id' => $sectionLeadership->id,
            'is_shared' => true,
        ]);

        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'is_headquarter' => true,
        ]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);
        $user->assignRole($adminRole);

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

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/available-permissions/active');

        $response->assertStatus(200);

        $permissions = collect($response->json('permissions', []))
            ->flatMap(fn ($moduleGroup) => collect($moduleGroup['permissions'] ?? []));

        $contexte = $permissions->firstWhere('name', 'contexte.manage');
        $leadership = $permissions->firstWhere('name', 'leadership.manage');

        $this->assertNotNull($contexte);
        $this->assertNotNull($leadership);

        $contexteMapping = collect($contexte['mappings'] ?? [])->first();
        $leadershipMapping = collect($leadership['mappings'] ?? [])->first();

        $this->assertSame('comprehension_organisme', $contexteMapping['sub_module_code'] ?? null);
        $this->assertSame('parties_interessees', $contexteMapping['section_code'] ?? null);
        $this->assertSame('roles_responsabilites', $leadershipMapping['sub_module_code'] ?? null);
        $this->assertSame('organigramme', $leadershipMapping['section_code'] ?? null);
    }

    public function test_active_permissions_projection_keeps_permission_when_section_is_null(): void
    {
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        $norm = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $module = Module::create([
            'code' => 'support',
            'name' => 'Support',
            'iso_point' => 7,
        ]);

        $subModule = SubModule::create([
            'module_id' => $module->id,
            'name' => 'Documents',
            'code' => 'documents',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $permission = Permission::create([
            'name' => 'support.manage',
            'guard_name' => 'web',
        ]);

        PermissionNormMapping::create([
            'permission_id' => $permission->id,
            'norm_id' => $norm->id,
            'module_id' => $module->id,
            'sub_module_id' => $subModule->id,
            'section_id' => null,
            'is_shared' => true,
        ]);

        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'is_headquarter' => true,
        ]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);
        $user->assignRole($adminRole);

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

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/available-permissions/active');

        $response->assertStatus(200);

        $permissions = collect($response->json('permissions', []))
            ->flatMap(fn ($moduleGroup) => collect($moduleGroup['permissions'] ?? []));

        $target = $permissions->firstWhere('name', 'support.manage');
        $this->assertNotNull($target);
        $mapping = collect($target['mappings'] ?? [])->first();
        $this->assertSame('documents', $mapping['sub_module_code'] ?? null);
        $this->assertNull($mapping['section_id'] ?? null);
        $this->assertNull($mapping['section_code'] ?? null);
    }

    public function test_active_permissions_projection_excludes_permissions_of_unsubscribed_norm(): void
    {
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        $normSubscribed = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);
        $normUnsubscribed = Norm::create([
            'code' => 'ISO 14001:2015',
            'name' => 'Management environnemental',
            'domain' => 'environment',
            'status' => 'published',
        ]);

        $moduleContexte = Module::create([
            'code' => 'contexte',
            'name' => 'Contexte',
            'iso_point' => 4,
        ]);

        $permissionSubscribed = Permission::create([
            'name' => 'contexte.manage',
            'guard_name' => 'web',
        ]);
        $permissionUnsubscribed = Permission::create([
            'name' => 'contexte.edit',
            'guard_name' => 'web',
        ]);

        PermissionNormMapping::create([
            'permission_id' => $permissionSubscribed->id,
            'norm_id' => $normSubscribed->id,
            'module_id' => $moduleContexte->id,
            'is_shared' => false,
        ]);
        PermissionNormMapping::create([
            'permission_id' => $permissionUnsubscribed->id,
            'norm_id' => $normUnsubscribed->id,
            'module_id' => $moduleContexte->id,
            'is_shared' => false,
        ]);

        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'is_headquarter' => true,
        ]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin_entreprise',
            'guard_name' => 'web',
        ]);
        $user->assignRole($adminRole);

        $offer = Offer::factory()->create(['is_active' => true]);
        $offer->norms()->syncWithoutDetaching([$normSubscribed->id]);
        EnterpriseSubscription::factory()->create([
            'site_id' => $site->id,
            'offer_id' => $offer->id,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/available-permissions/active');

        $response->assertStatus(200);

        $permissions = collect($response->json('permissions', []))
            ->flatMap(fn ($moduleGroup) => collect($moduleGroup['permissions'] ?? []))
            ->pluck('name')
            ->values()
            ->all();

        $this->assertContains('contexte.manage', $permissions);
        $this->assertNotContains('contexte.edit', $permissions);
    }
}
