<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\Formation;
use App\Models\FormationAlert;
use App\Models\Site;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Notifications\FormationReminderNotification;
use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormationWorkflowTest extends TestCase
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
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        $role = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        foreach (['formations.read', 'formations.create', 'formations.update', 'formations.delete'] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(['formations.read', 'formations.create', 'formations.update', 'formations.delete']);

        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->admin->assignRole($role);
    }

    public function test_index_returns_runtime_status_en_attente_when_period_is_passed(): void
    {
        Carbon::setTestNow('2026-03-09 09:00:00');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-05',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/formations');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $formation->id,
            'status' => 'en_attente',
        ]);

        Carbon::setTestNow();
    }

    public function test_store_rejects_ponctuelle_with_standard_period_mode(): void
    {
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $payload = [
            'designation' => 'Formation interne process',
            'target_user_ids' => [$collaborator->id],
            'chronogramme' => array_fill(0, 12, false),
            'formateur' => 'Responsable QHSE',
            'dateDebut' => '2026-04-01',
            'dateFin' => '2026-04-30',
            'period_mode' => 'standard',
            'period_label' => 'Avril 2026',
            'frequency' => 'ponctuelle',
            'site_id' => $this->site->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['period_mode']);
    }

    public function test_store_accepts_ponctuelle_with_custom_period_mode(): void
    {
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $payload = [
            'designation' => 'Formation interne process',
            'target_user_ids' => [$collaborator->id],
            'chronogramme' => array_fill(0, 12, false),
            'formateur' => 'Responsable QHSE',
            'dateDebut' => '2026-04-01',
            'dateFin' => '2026-04-30',
            'period_mode' => 'custom',
            'period_label' => 'Avril 2026',
            'frequency' => 'ponctuelle',
            'site_id' => $this->site->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'period_mode' => 'custom',
            'period_label' => 'Avril 2026',
        ]);
        $response->assertJsonPath('target_user_ids.0', $collaborator->id);
    }

    public function test_update_rejects_recurrente_with_custom_period_mode(): void
    {
        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-05-01',
            'date_fin' => '2026-05-31',
            'period_mode' => 'standard',
            'frequency' => 'mensuelle',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/formations/' . $formation->id, [
                'period_mode' => 'custom',
                'frequency' => 'mensuelle',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['period_mode']);
    }

    public function test_store_recurrente_requires_date_debut_reference(): void
    {
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $payload = [
            'designation' => 'Communication mensuelle sécurité',
            'target_user_ids' => [$collaborator->id],
            'chronogramme' => array_fill(0, 12, false),
            'formateur' => 'Responsable HSE',
            'period_mode' => 'standard',
            'period_label' => 'Mensuel 2026',
            'frequency' => 'mensuelle',
            'site_id' => $this->site->id,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dateDebut']);
    }

    public function test_update_rejects_ponctuelle_without_dates(): void
    {
        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-07-01',
            'date_fin' => '2026-07-15',
            'period_mode' => 'custom',
            'frequency' => 'ponctuelle',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/v1/formations/' . $formation->id, [
                'frequency' => 'ponctuelle',
                'period_mode' => 'custom',
                'date_debut' => null,
                'date_fin' => null,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['dateDebut']);
    }

    public function test_complete_sets_realisee_and_creates_completed_history_entry(): void
    {
        Carbon::setTestNow('2026-03-09 09:00:00');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-05',
        ]);

        $proof = UploadedFile::fake()->create('attestation.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/formations/' . $formation->id . '/complete', [
                'proofs' => [$proof],
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'realisee']);

        $this->assertDatabaseHas('formation_history', [
            'formation_id' => $formation->id,
            'action' => 'completed',
            'user_id' => $this->admin->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_complete_is_allowed_without_proof(): void
    {
        Carbon::setTestNow('2026-03-09 09:00:00');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-05',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations/' . $formation->id . '/complete', []);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'realisee']);

        Carbon::setTestNow();
    }

    public function test_cancel_is_rejected_before_first_january_of_next_year(): void
    {
        Carbon::setTestNow('2026-12-15 09:00:00');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-05-10',
            'date_fin' => '2026-05-12',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations/' . $formation->id . '/cancel', [
                'reason' => 'Annulation de test',
            ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Annulation autorisée à partir du 01/01/2027',
        ]);

        Carbon::setTestNow();
    }

    public function test_cancel_is_allowed_from_first_january_of_next_year(): void
    {
        Carbon::setTestNow('2027-01-02 09:00:00');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-05-10',
            'date_fin' => '2026-05-12',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations/' . $formation->id . '/cancel', [
                'reason' => 'Annulation N+1',
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['status' => 'annulee']);

        Carbon::setTestNow();
    }

    public function test_reminder_command_uses_alert_table_and_marks_alert_as_sent(): void
    {
        Carbon::setTestNow('2026-03-09 08:30:00');
        Notification::fake();

        $manager = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $manager->assignRole('admin_entreprise');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-03-16',
            'date_fin' => '2026-03-18',
        ]);

        $alert = FormationAlert::create([
            'formation_id' => $formation->id,
            'type' => 'J-7',
            'date' => '2026-03-09',
            'sent' => false,
        ]);

        Artisan::call('formations:check-reminders');

        $alert->refresh();
        $this->assertTrue($alert->sent);
        $this->assertNotNull($alert->sent_at);

        Notification::assertSentTo(
            [$this->admin, $manager],
            FormationReminderNotification::class
        );

        Carbon::setTestNow();
    }

    public function test_user_without_formations_read_permission_cannot_list_formations(): void
    {
        $userWithoutPermission = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        /** @var User $userWithoutPermission */

        $response = $this->actingAs($userWithoutPermission, 'sanctum')
            ->getJson('/api/v1/formations');

        $response->assertStatus(403);
    }

    public function test_store_creates_and_syncs_training_plan_automatically(): void
    {
        $collaborator = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations', [
                'designation' => 'Formation auto-plan',
                'target_user_ids' => [$collaborator->id],
                'period_mode' => 'custom',
                'frequency' => 'ponctuelle',
                'dateDebut' => '2026-09-10',
                'dateFin' => '2026-09-11',
                'site_id' => $this->site->id,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('training_plans', [
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'year' => 2026,
            'planned_formations' => 1,
        ]);
    }

    public function test_complete_updates_training_plan_spent_amount(): void
    {
        Carbon::setTestNow('2026-03-09 09:00:00');

        $formation = Formation::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'created_by' => $this->admin->id,
            'status' => 'planifiee',
            'date_debut' => '2026-03-20',
            'date_fin' => '2026-03-21',
            'cout' => 1250,
            'plan_year' => 2026,
        ]);

        TrainingPlan::create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'year' => 2026,
            'status' => 'active',
            'planned_formations' => 0,
            'spent_amount' => 0,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/formations/' . $formation->id . '/complete', []);

        $response->assertStatus(200);

        $plan = TrainingPlan::query()
            ->where('enterprise_id', $this->enterprise->id)
            ->where('site_id', $this->site->id)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($plan);
        $this->assertSame(1, (int) $plan->planned_formations);
        $this->assertSame(1250.0, (float) $plan->spent_amount);

        Carbon::setTestNow();
    }
}
