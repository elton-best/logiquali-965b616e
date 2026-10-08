<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Communication;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
        ]);
        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
    }

    public function test_store_rejects_ponctuelle_with_standard_period_mode(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/communications', [
                'type' => 'communication',
                'designation' => 'Campagne sécurité',
                'cibles' => ['Personnel'],
                'moyens' => ['Email'],
                'chronogramme' => array_fill(0, 12, false),
                'organizer_user_id' => $this->admin->id,
                'frequency' => 'ponctuelle',
                'period_mode' => 'standard',
                'dateDebut' => '2026-07-01',
                'dateFin' => '2026-07-10',
                'site_id' => $this->site->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['period_mode']);
    }

    public function test_store_creates_and_syncs_communication_plan_automatically(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/communications', [
                'type' => 'communication',
                'designation' => 'Campagne qualité',
                'cibles' => ['Personnel'],
                'moyens' => ['Réunion'],
                'chronogramme' => array_fill(0, 12, false),
                'organizer_user_id' => $this->admin->id,
                'frequency' => 'ponctuelle',
                'period_mode' => 'custom',
                'dateDebut' => '2026-08-05',
                'dateFin' => '2026-08-06',
                'site_id' => $this->site->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('communication_plans', [
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'year' => 2026,
            'planned_actions' => 1,
        ]);
    }

    public function test_complete_updates_communication_plan_spent_amount(): void
    {
        $communication = Communication::create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'numero' => 12,
            'type' => 'communication',
            'designation' => 'Action communication test',
            'cibles' => ['Personnel'],
            'moyens' => ['Email'],
            'chronogramme' => array_fill(0, 12, false),
            'responsable' => 'Responsable com',
            'organizer_user_id' => $this->admin->id,
            'participant_user_ids' => [$this->admin->id],
            'date_debut' => '2026-10-01',
            'date_fin' => '2026-10-02',
            'period_mode' => 'custom',
            'plan_year' => 2026,
            'status' => 'planifiee',
            'frequency' => 'ponctuelle',
            'cout' => 20000,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/communications/' . $communication->id . '/complete', [
                'proofs' => [\Illuminate\Http\UploadedFile::fake()->create('preuve.pdf', 120, 'application/pdf')],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('communication_plans', [
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'year' => 2026,
            'planned_actions' => 1,
            'spent_amount' => 20000,
        ]);
    }
}

