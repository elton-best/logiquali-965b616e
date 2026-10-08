<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enterprise;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnterpriseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un super admin pour éviter les problèmes de permissions
        $this->superAdmin = User::factory()->create([
            'user_type' => 'super_admin'
        ]);
    }

    public function test_can_list_enterprises()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        // Créer quelques entreprises
        Enterprise::factory()->count(3)->create();
        
        $response = $this->getJson('/api/v1/enterprises');
        
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_can_create_enterprise()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        $enterpriseData = [
            'name' => 'Test Enterprise',
            'email' => 'test@enterprise.com',
            'status' => 'pending',
            'field' => 'Technology'
        ];
        
        $response = $this->postJson('/api/v1/enterprises', $enterpriseData);
        
        $response->assertStatus(201);
        
        // Vérifier en base
        $this->assertDatabaseHas('enterprises', [
            'name' => 'Test Enterprise',
            'email' => 'test@enterprise.com',
            'status' => 'pending'
        ]);
    }

    public function test_validates_required_fields()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        $response = $this->postJson('/api/v1/enterprises', []);
        
        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('status', $errors);
    }

    public function test_validates_unique_email()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        // Créer une entreprise existante
        Enterprise::factory()->create(['email' => 'existing@test.com']);
        
        $response = $this->postJson('/api/v1/enterprises', [
            'name' => 'New Enterprise',
            'email' => 'existing@test.com',
            'status' => 'pending'
        ]);
        
        $response->assertStatus(422);
        $errors = $response->json('errors');
        
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_can_show_enterprise()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        $enterprise = Enterprise::factory()->create();
        
        $response = $this->getJson("/api/v1/enterprises/{$enterprise->id}");
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals($enterprise->id, $data['id']);
        $this->assertEquals($enterprise->name, $data['attributes']['name']);
    }

    public function test_can_update_enterprise()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        $enterprise = Enterprise::factory()->create();
        
        $updateData = [
            'name' => 'Updated Enterprise',
            'field' => 'Updated Field'
        ];
        
        $response = $this->putJson("/api/v1/enterprises/{$enterprise->id}", $updateData);
        
        $response->assertStatus(200);
        
        $enterprise->refresh();
        $this->assertEquals('Updated Enterprise', $enterprise->name);
        $this->assertEquals('Updated Field', $enterprise->field);
    }

    public function test_can_delete_enterprise()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        $enterprise = Enterprise::factory()->create();
        
        $response = $this->deleteJson("/api/v1/enterprises/{$enterprise->id}");
        
        $response->assertStatus(204);
        
        // Vérifier que l'entreprise a été supprimée (soft delete)
        $this->assertSoftDeleted('enterprises', [
            'id' => $enterprise->id
        ]);
    }

    public function test_domaine_activite_set_logic()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        // Test avec field rempli
        $response = $this->postJson('/api/v1/enterprises', [
            'name' => 'Test Enterprise',
            'email' => 'test@example.com',
            'status' => 'pending',
            'field' => 'Technology'
        ]);
        
        $response->assertStatus(201);
        
        $enterprise = Enterprise::where('email', 'test@example.com')->first();
        $this->assertTrue($enterprise->domaine_activite_set);
        
        // Test avec field vide
        $response2 = $this->postJson('/api/v1/enterprises', [
            'name' => 'Test Enterprise 2',
            'email' => 'test2@example.com',
            'status' => 'pending',
            'field' => null
        ]);
        
        $response2->assertStatus(201);
        
        $enterprise2 = Enterprise::where('email', 'test2@example.com')->first();
        $this->assertFalse($enterprise2->domaine_activite_set);
    }

    public function test_status_approval_consistency()
    {
        $this->actingAs($this->superAdmin, 'sanctum');
        
        $enterprise = Enterprise::factory()->create([
            'status' => 'pending',
            'approval_status' => 'pending'
        ]);
        
        // Mettre à jour le status vers active
        $response = $this->putJson("/api/v1/enterprises/{$enterprise->id}", [
            'status' => 'active'
        ]);
        
        $response->assertStatus(200);
        
        $enterprise->refresh();
        $this->assertEquals('active', $enterprise->status);
        $this->assertEquals('approved', $enterprise->approval_status);
    }
}