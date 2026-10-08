<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DocumentTypeConfigurationVisibilityService;
use App\Models\DocumentTypeConfiguration;
use App\Models\User;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DocumentTypeConfigurationVisibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentTypeConfigurationVisibilityService $service;
    private Enterprise $enterprise;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentTypeConfigurationVisibilityService::class);
        
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
    }

    /** @test */
    public function it_gets_visible_configurations_for_user()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        
        DocumentTypeConfiguration::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $otherEnterprise = Enterprise::factory()->create();
        DocumentTypeConfiguration::factory()->count(2)->create([
            'enterprise_id' => $otherEnterprise->id
        ]);

        $visible = $this->service->getVisibleConfigurations($user);

        $this->assertGreaterThanOrEqual(3, $visible->count());
    }

    /** @test */
    public function it_filters_configurations_by_site()
    {
        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id
        ]);
        
        DocumentTypeConfiguration::factory()->count(2)->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id
        ]);
        
        DocumentTypeConfiguration::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => null
        ]);

        $visible = $this->service->getVisibleConfigurations($user, ['site_id' => $this->site->id]);

        $this->assertGreaterThanOrEqual(2, $visible->count());
    }

    /** @test */
    public function it_checks_if_user_can_view_configuration()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $canView = $this->service->canView($user, $config);

        $this->assertTrue($canView);
    }

    /** @test */
    public function it_prevents_viewing_other_enterprise_configuration()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $otherEnterprise = Enterprise::factory()->create();
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $otherEnterprise->id
        ]);

        $canView = $this->service->canView($user, $config);

        $this->assertFalse($canView);
    }

    /** @test */
    public function it_checks_if_user_can_edit_configuration()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $user->givePermissionTo('configure_nomenclature');
        
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $canEdit = $this->service->canEdit($user, $config);

        $this->assertTrue($canEdit);
    }

    /** @test */
    public function it_prevents_editing_without_permission()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $canEdit = $this->service->canEdit($user, $config);

        $this->assertFalse($canEdit);
    }

    /** @test */
    public function it_shares_configuration_with_sites()
    {
        $enterprise1 = Enterprise::factory()->create();
        $enterprise2 = Enterprise::factory()->create();
        $enterprise3 = Enterprise::factory()->create();

        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $enterprise1->id,
            'abbreviation' => 'SHARE',
            'name' => 'Shared Config',
        ]);

        $site1 = Site::factory()->create(['enterprise_id' => $enterprise2->id]);
        $site2 = Site::factory()->create(['enterprise_id' => $enterprise3->id]);

        // Modifier l'enterprise_id de la config pour chaque site lors du partage
        $results = $this->service->shareWithSites($config, [$site1->id, $site2->id]);

        $this->assertCount(2, $results['success']);
        $this->assertEmpty($results['errors']);
    }

    /** @test */
    public function it_gets_visibility_statistics()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        
        DocumentTypeConfiguration::factory()->count(5)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true
        ]);
        
        DocumentTypeConfiguration::factory()->count(2)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => false
        ]);

        $stats = $this->service->getVisibilityStats($user);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('by_scope', $stats);
        $this->assertArrayHasKey('by_status', $stats);
        $this->assertGreaterThanOrEqual(7, $stats['total']);
        $this->assertGreaterThanOrEqual(5, $stats['by_status']['active']);
        $this->assertGreaterThanOrEqual(2, $stats['by_status']['inactive']);
    }

    /** @test */
    public function it_applies_advanced_filters()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        
        DocumentTypeConfiguration::factory()->count(10)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true
        ]);
        
        DocumentTypeConfiguration::factory()->count(5)->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => false
        ]);

        $filters = [
            'is_active' => true,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc'
        ];

        $filtered = $this->service->applyAdvancedFilters($user, $filters);

        $this->assertNotEmpty($filtered);
        $this->assertTrue($filtered->every(fn($config) => $config->is_active === true));
    }

    /** @test */
    public function it_gets_available_sites_for_sharing()
    {
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'abbreviation' => 'TEST'
        ]);

        $site1 = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $site2 = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        $availableSites = $this->service->getAvailableSitesForSharing($user, $config);

        $this->assertGreaterThanOrEqual(2, $availableSites->count());
    }

    /** @test */
    public function super_admin_can_view_all_configurations()
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'sanctum']);
        $user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $user->assignRole('super-admin');
        
        DocumentTypeConfiguration::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $otherEnterprise = Enterprise::factory()->create();
        DocumentTypeConfiguration::factory()->count(2)->create([
            'enterprise_id' => $otherEnterprise->id
        ]);

        $visible = $this->service->getVisibleConfigurations($user);

        $this->assertGreaterThanOrEqual(5, $visible->count());
    }
}
