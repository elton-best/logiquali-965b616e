<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SiteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $enterprise;
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer la structure de base
        $this->enterprise = Enterprise::factory()->create();
        
        // Créer un utilisateur admin
        $this->adminUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'user_type' => 'super_admin' // Super admin pour éviter les problèmes de permissions
        ]);
    }

    public function test_can_list_sites()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Créer quelques sites
        Site::factory()->count(3)->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $response = $this->getJson('/api/v1/sites');
        
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_can_create_site()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $siteData = [
            'enterprise_id' => $this->enterprise->id,
            'name' => 'Site Test',
            'location' => 'Paris, France',
            'city' => 'Paris',
            'is_active' => true
        ];
        
        $response = $this->postJson('/api/v1/sites', $siteData);
        
        $response->assertStatus(201);
        
        // Vérifier en base
        $this->assertDatabaseHas('sites', [
            'name' => 'Site Test',
            'location' => 'Paris, France',
            'enterprise_id' => $this->enterprise->id
        ]);
    }

    public function test_validates_required_fields()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $response = $this->postJson('/api/v1/sites', []);
        
        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('location', $errors);
        $this->assertArrayHasKey('city', $errors);
    }

    public function test_can_show_site()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $response = $this->getJson("/api/v1/sites/{$site->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'attributes' => [
                        'name',
                        'location'
                    ]
                ]
            ]);
    }

    public function test_can_update_site()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        $site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $updateData = [
            'name' => 'Site Updated',
            'location' => 'Lyon, France'
        ];
        
        $response = $this->putJson("/api/v1/sites/{$site->id}", $updateData);
        
        $response->assertStatus(200);
        
        $site->refresh();
        $this->assertEquals('Site Updated', $site->name);
        $this->assertEquals('Lyon, France', $site->location);
    }

    public function test_can_toggle_site_status()
    {
        $this->actingAs($this->adminUser, 'sanctum');

        $site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/sites/{$site->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $site->refresh();
        $this->assertFalse($site->is_active);
    }

    public function test_enforces_enterprise_isolation()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Créer un site dans une autre entreprise
        $otherEnterprise = Enterprise::factory()->create();
        $otherSite = Site::factory()->create([
            'enterprise_id' => $otherEnterprise->id
        ]);
        
        // Créer un utilisateur non super admin
        $normalUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'user_type' => 'company'
        ]);
        
        $this->actingAs($normalUser, 'sanctum');
        
        // Tenter d'accéder au site d'une autre entreprise
        $response = $this->getJson("/api/v1/sites/{$otherSite->id}");
        
        $response->assertStatus(403);
    }

    public function test_can_create_site_with_manager()
    {
        $this->actingAs($this->adminUser, 'sanctum');
        
        // Créer un utilisateur qui sera manager
        $manager = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'user_type' => 'company'
        ]);
        
        $siteData = [
            'enterprise_id' => $this->enterprise->id,
            'name' => 'Site avec Manager',
            'location' => 'Marseille, France',
            'city' => 'Marseille',
            'manager_id' => $manager->id,
            'is_active' => true
        ];
        
        $response = $this->postJson('/api/v1/sites', $siteData);
        
        $response->assertStatus(201);
        
        // Vérifier que le manager a été assigné
        $site = Site::where('name', 'Site avec Manager')->first();
        $this->assertEquals($manager->id, $site->manager_id);
        
        // Vérifier que le manager a été assigné au site
        $manager->refresh();
        $this->assertEquals($site->id, $manager->site_id);
    }
}
