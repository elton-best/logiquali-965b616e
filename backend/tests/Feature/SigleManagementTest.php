<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\EnterpriseSigleHistory;
use App\Models\Equipement;
use App\Models\CodificationElement;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigleManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Enterprise $enterprise;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\ForceSignatureUpload::class,
            \App\Http\Middleware\ForceCompanySetup::class,
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            \App\Http\Middleware\EnsureEnterpriseOwnership::class,
            \App\Http\Middleware\SecurityAuditMiddleware::class,
            \App\Http\Middleware\ForceSessionTimeout::class,
        ]);

        // Create user
        $this->user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        // Create enterprise
        $this->enterprise = Enterprise::factory()->create([
            'sigle' => 'ABC',
            'codification_mode' => 'standard',
        ]);

        // Associate user with enterprise
        $this->user->forceFill([
            'enterprise_id' => $this->enterprise->id,
            'user_type' => 'company',
        ])->save();
    }

    public function test_preview_sigle_change_returns_current_and_new_sigle()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle/preview",
            ['new_sigle' => 'XYZ']
        );

        $response->assertStatus(200)
            ->assertJson([
                'current_sigle' => 'ABC',
                'new_sigle' => 'XYZ',
                'can_proceed' => true,
            ])
            ->assertJsonStructure([
                'current_sigle',
                'new_sigle',
                'equipements_affected',
                'equipment_sample',
                'warnings',
                'can_proceed',
            ]);
    }

    public function test_preview_sigle_change_detects_identical_sigle()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle/preview",
            ['new_sigle' => 'ABC']
        );

        $response->assertStatus(200)
            ->assertJson([
                'current_sigle' => 'ABC',
                'new_sigle' => 'ABC',
                'can_proceed' => false,
            ]);
    }

    public function test_preview_sigle_change_validates_required_field()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle/preview",
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('new_sigle');
    }

    public function test_preview_sigle_change_with_affected_equipment()
    {
        // Create equipment
        $site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
        ]);

        $category = CodificationElement::factory()->categorie()->create([
            'enterprise_id' => $this->enterprise->id,
            'code' => 'CAT',
        ]);

        $localisation = CodificationElement::factory()->localisation()->create([
            'code' => 'LOC',
            'enterprise_id' => $this->enterprise->id,
        ]);

        Equipement::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $site->id,
            'categorie_id' => $category->id,
            'localisation_id' => $localisation->id,
            'code_complet' => 'ABC-CAT-ABC-LOC-001-2024',
            'nom_commun' => 'Equipment 1',
            'nom_commun_abrege' => 'EQP',
            'indice' => '001',
            'annee_acquisition' => 2024,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle/preview",
            ['new_sigle' => 'XYZ']
        );

        $response->assertStatus(200)
            ->assertJson([
                'equipements_affected' => 1,
                'can_proceed' => true,
            ])
            ->assertJsonCount(1, 'equipment_sample');
    }

    public function test_update_sigle_creates_history_entry()
    {
        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle",
            [
                'new_sigle' => 'XYZ',
                'reason' => 'Reorganisation entreprise',
                'recode_equipements' => false,
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'old_sigle' => 'ABC',
                'new_sigle' => 'XYZ',
            ]);

        // Verify history entry created
        $history = EnterpriseSigleHistory::where('enterprise_id', $this->enterprise->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('ABC', $history->old_sigle);
        $this->assertEquals('XYZ', $history->new_sigle);
        $this->assertEquals('Reorganisation entreprise', $history->reason);

        // Verify enterprise sigle updated
        $this->assertEquals('XYZ', $this->enterprise->fresh()->sigle);
    }

    public function test_update_sigle_detects_identical_sigle()
    {
        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle",
            [
                'new_sigle' => 'ABC',
            ]
        );

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Le nouveau sigle est identique à l\'ancien.',
            ]);
    }

    public function test_update_sigle_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle",
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors('new_sigle');
    }

    public function test_update_sigle_transactional_rollback_on_error()
    {
        // Mock an error during recoding by using a non-standard mode
        $this->enterprise->update(['codification_mode' => 'custom']);

        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle",
            [
                'new_sigle' => 'XYZ',
                'recode_equipements' => true,
            ]
        );

        $response->assertStatus(422);

        // Verify sigle was NOT updated (transaction rolled back)
        $this->assertEquals('ABC', $this->enterprise->fresh()->sigle);

        // Verify no history entry created
        $history = EnterpriseSigleHistory::where('enterprise_id', $this->enterprise->id)->first();
        $this->assertNull($history);
    }

    public function test_get_sigle_history_returns_entries_in_reverse_chronological_order()
    {
        // Create multiple sigle changes
        for ($i = 0; $i < 5; $i++) {
            EnterpriseSigleHistory::create([
                'enterprise_id' => $this->enterprise->id,
                'old_sigle' => 'OLD' . $i,
                'new_sigle' => 'NEW' . $i,
                'reason' => 'Test history ' . $i,
                'recoding_mode' => 'none',
                'equipements_affected' => 0,
                'changed_by' => $this->user->id,
                'created_at' => now()->subMinutes(5 - $i),
                'updated_at' => now()->subMinutes(5 - $i),
            ]);
        }

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle/history"
        );

        $response->assertStatus(200)
            ->assertJsonCount(5)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'enterprise_id',
                    'old_sigle',
                    'new_sigle',
                    'reason',
                    'recoding_mode',
                    'equipements_affected',
                    'changed_by',
                    'is_reverted',
                    'created_at',
                ],
            ]);

        // Verify reverse chronological order (newest first)
        $data = $response->json();
        $this->assertGreaterThanOrEqual(
            strtotime($data[1]['created_at']),
            strtotime($data[0]['created_at'])
        );
    }

    public function test_get_sigle_history_limits_to_20_entries()
    {
        // Create 30 history entries
        for ($i = 0; $i < 30; $i++) {
            EnterpriseSigleHistory::create([
                'enterprise_id' => $this->enterprise->id,
                'old_sigle' => 'OS' . $i,
                'new_sigle' => 'NS' . $i,
                'reason' => 'Bulk history ' . $i,
                'recoding_mode' => 'none',
                'equipements_affected' => 0,
                'changed_by' => $this->user->id,
                'created_at' => now()->subMinutes(30 - $i),
                'updated_at' => now()->subMinutes(30 - $i),
            ]);
        }

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/enterprises/{$this->enterprise->id}/sigle/history"
        );

        $response->assertStatus(200)
            ->assertJsonCount(20);
    }

    public function test_user_cannot_update_other_enterprise_sigle()
    {
        // Setup: 2 users in 2 different enterprises
        $otherEnterprise = Enterprise::factory()->create([
            'sigle' => 'DEF',
            'codification_mode' => 'standard',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'enterprise_id' => $otherEnterprise->id,
            'user_type' => 'company',
        ]);

        // Act: User A tries to update User B's enterprise SIGLE
        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/enterprises/{$otherEnterprise->id}/sigle",
            [
                'new_sigle' => 'XYZ',
                'reason' => 'Unauthorized attempt',
                'recode_equipements' => false,
            ]
        );

        // Assert: Must return 403 Forbidden
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to enterprise data',
            ]);

        // Verify the enterprise SIGLE was NOT changed
        $this->assertEquals('DEF', $otherEnterprise->fresh()->sigle);
    }

    public function test_user_cannot_preview_other_enterprise_sigle()
    {
        // Setup: 2 users in 2 different enterprises
        $otherEnterprise = Enterprise::factory()->create([
            'sigle' => 'GHI',
            'codification_mode' => 'standard',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other2@test.com',
            'password' => bcrypt('password'),
            'enterprise_id' => $otherEnterprise->id,
            'user_type' => 'company',
        ]);

        // Act: User A tries to preview SIGLE change for User B's enterprise
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/enterprises/{$otherEnterprise->id}/sigle/preview",
            ['new_sigle' => 'XYZ']
        );

        // Assert: Must return 403 Forbidden
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to enterprise data',
            ]);
    }

    public function test_user_cannot_access_other_enterprise_sigle_history()
    {
        // Setup: 2 users in 2 different enterprises
        $otherEnterprise = Enterprise::factory()->create([
            'sigle' => 'JKL',
            'codification_mode' => 'standard',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other3@test.com',
            'password' => bcrypt('password'),
            'enterprise_id' => $otherEnterprise->id,
            'user_type' => 'company',
        ]);

        // Create some history for the other enterprise
        for ($i = 0; $i < 5; $i++) {
            EnterpriseSigleHistory::create([
                'enterprise_id' => $otherEnterprise->id,
                'old_sigle' => 'OLD' . $i,
                'new_sigle' => 'NEW' . $i,
                'reason' => 'Other enterprise history ' . $i,
                'recoding_mode' => 'none',
                'equipements_affected' => 0,
                'changed_by' => $otherUser->id,
            ]);
        }

        // Act: User A tries to access User B's enterprise SIGLE history
        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/enterprises/{$otherEnterprise->id}/sigle/history"
        );

        // Assert: Must return 403 Forbidden
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access to enterprise data',
            ]);
    }
}
