<?php

namespace Tests\Feature;

use App\Models\Equipement;
use App\Models\EquipementTransferHistory;
use App\Models\TransferReasonCode;
use App\Models\Site;
use App\Models\CodificationElement;
use App\Models\Enterprise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EquipmentTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Enterprise $enterprise;
    private Site $site1;
    private Site $site2;
    private CodificationElement $localisation1;
    private CodificationElement $localisation2;
    private Equipement $equipement;
    private TransferReasonCode $transferReason;

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
            'email' => 'equipement@test.com',
        ]);

        // Create enterprise
        $this->enterprise = Enterprise::factory()->create();
        $this->user->forceFill([
            'enterprise_id' => $this->enterprise->id,
            'user_type' => 'company',
        ])->save();

        Permission::findOrCreate('equipements.transfer', 'web');
        Permission::findOrCreate('equipements.update', 'web');
        Permission::findOrCreate('equipements.read', 'web');
        $this->user->givePermissionTo(['equipements.transfer', 'equipements.read']);

        // Create sites
        $this->site1 = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'name' => 'Site A',
        ]);
        $this->site2 = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'name' => 'Site B',
        ]);

        // Create localisations
        $this->localisation1 = CodificationElement::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'type' => 'localisation',
            'code' => 'LOC1',
        ]);
        $this->localisation2 = CodificationElement::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'type' => 'localisation',
            'code' => 'LOC2',
        ]);

        // Create equipment
        $this->equipement = Equipement::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site1->id,
            'localisation_id' => $this->localisation1->id,
        ]);

        // Create transfer reason code
        $this->transferReason = TransferReasonCode::create([
            'enterprise_id' => $this->enterprise->id,
            'code' => 'MAINTENANCE',
            'name' => 'Maintenance préventive',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_register_transfer_creates_history_entry()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/equipements/{$this->equipement->id}/transfer",
            [
                'new_site_id' => $this->site2->id,
                'new_localisation_id' => $this->localisation2->id,
                'transfer_reason_code' => 'MAINTENANCE',
                'transfer_notes' => 'Transfert pour maintenance',
            ]
        );

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'equipement_id' => $this->equipement->id,
            ]);

        // Verify transfer history entry created
        $history = EquipementTransferHistory::where('equipement_id', $this->equipement->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->site1->id, $history->previous_site_id);
        $this->assertEquals($this->site2->id, $history->new_site_id);
        $this->assertEquals('MAINTENANCE', $history->transfer_reason_code);

        // Verify equipment location updated
        $this->assertEquals($this->site2->id, $this->equipement->fresh()->site_id);
        $this->assertEquals($this->localisation2->id, $this->equipement->fresh()->localisation_id);
    }

    public function test_register_transfer_validates_required_fields()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/equipements/{$this->equipement->id}/transfer",
            []
        );

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Paramètres invalides.',
            ]);
    }

    public function test_register_transfer_validates_site_belongs_to_enterprise()
    {
        $otherEnterprise = Enterprise::factory()->create();
        $otherSite = Site::factory()->create([
            'enterprise_id' => $otherEnterprise->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/equipements/{$this->equipement->id}/transfer",
            [
                'new_site_id' => $otherSite->id,
                'new_localisation_id' => $this->localisation2->id,
                'transfer_reason_code' => 'MAINTENANCE',
            ]
        );

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Paramètres invalides.',
            ]);
    }

    public function test_register_transfer_detects_self_transfer()
    {
        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/equipements/{$this->equipement->id}/transfer",
            [
                'new_site_id' => $this->site1->id,
                'new_localisation_id' => $this->localisation1->id,
                'transfer_reason_code' => 'MAINTENANCE',
            ]
        );

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Paramètres invalides.',
            ]);
    }

    public function test_get_equipment_transfers_returns_history()
    {
        // Create transfer
        for ($i = 0; $i < 3; $i++) {
            EquipementTransferHistory::create([
                'equipement_id' => $this->equipement->id,
                'enterprise_id' => $this->enterprise->id,
                'previous_site_id' => $this->site1->id,
                'previous_localisation_id' => $this->localisation1->id,
                'new_site_id' => $this->site2->id,
                'new_localisation_id' => $this->localisation2->id,
                'transfer_reason_code' => 'MAINTENANCE',
                'transferred_by' => $this->user->id,
                'transferred_at' => now()->subMinutes(3 - $i),
                'is_verified' => false,
            ]);
        }

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/equipements/{$this->equipement->id}/transfers"
        );

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_list_transfers_by_enterprise()
    {
        for ($i = 0; $i < 5; $i++) {
            EquipementTransferHistory::create([
                'equipement_id' => $this->equipement->id,
                'enterprise_id' => $this->enterprise->id,
                'previous_site_id' => $this->site1->id,
                'previous_localisation_id' => $this->localisation1->id,
                'new_site_id' => $this->site2->id,
                'new_localisation_id' => $this->localisation2->id,
                'transfer_reason_code' => 'MAINTENANCE',
                'transferred_by' => $this->user->id,
                'transferred_at' => now()->subMinutes(5 - $i),
                'is_verified' => false,
            ]);
        }

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/transfers?enterprise_id={$this->enterprise->id}"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'equipement_id',
                        'new_site_id',
                        'transfer_reason_code',
                        'is_verified',
                    ],
                ],
                'total',
                'per_page',
                'current_page',
            ]);
    }

    public function test_verify_transfer()
    {
        $transfer = EquipementTransferHistory::create([
            'equipement_id' => $this->equipement->id,
            'enterprise_id' => $this->enterprise->id,
            'previous_site_id' => $this->site1->id,
            'previous_localisation_id' => $this->localisation1->id,
            'new_site_id' => $this->site2->id,
            'new_localisation_id' => $this->localisation2->id,
            'transfer_reason_code' => 'MAINTENANCE',
            'transferred_by' => $this->user->id,
            'transferred_at' => now(),
            'is_verified' => false,
        ]);

        $response = $this->actingAs($this->user)->putJson(
            "/api/v1/transfers/{$transfer->id}/verify",
            [
                'verification_notes' => 'Vérification complète',
            ]
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_verified' => true,
            ]);

        // Verify transfer marked as verified
        $this->assertTrue($transfer->fresh()->is_verified);
        $this->assertNotNull($transfer->fresh()->verified_at);
    }

    public function test_get_transfer_reasons()
    {
        for ($i = 1; $i <= 5; $i++) {
            TransferReasonCode::create([
                'enterprise_id' => $this->enterprise->id,
                'code' => sprintf('REASON_%d', $i),
                'name' => sprintf('Reason %d', $i),
                'is_active' => true,
                'created_by' => $this->user->id,
            ]);
        }

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/enterprises/{$this->enterprise->id}/transfer-reasons"
        );

        $response->assertStatus(200)
            ->assertJsonCount(6) // 5 + 1 from setUp
            ->assertJsonStructure([
                '*' => ['id', 'code', 'name'],
            ]);
    }

    public function test_register_transfer_is_allowed_with_equipements_update_permission(): void
    {
        $this->user->revokePermissionTo('equipements.transfer');
        $this->user->givePermissionTo('equipements.update');

        $response = $this->actingAs($this->user)->postJson(
            "/api/v1/equipements/{$this->equipement->id}/transfer",
            [
                'new_site_id' => $this->site2->id,
                'new_localisation_id' => $this->localisation2->id,
                'transfer_reason_code' => 'MAINTENANCE',
                'transfer_notes' => 'Transfert legacy update permission',
            ]
        );

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'equipement_id' => $this->equipement->id,
            ]);
    }

    public function test_get_transfer_reasons_forbidden_for_other_enterprise(): void
    {
        $otherEnterprise = Enterprise::factory()->create();

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/enterprises/{$otherEnterprise->id}/transfer-reasons"
        );

        $response->assertStatus(403);
    }

    public function test_list_transfers_forbidden_for_other_enterprise(): void
    {
        $otherEnterprise = Enterprise::factory()->create();

        $response = $this->actingAs($this->user)->getJson(
            "/api/v1/transfers?enterprise_id={$otherEnterprise->id}"
        );

        $response->assertStatus(403);
    }
}
