<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReclamationE7WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $manager;
    private User $assignee;

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
        Permission::firstOrCreate(['name' => 'reclamations.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reclamations.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reclamations.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'reclamations.manage', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'reclamations.create',
            'reclamations.read',
            'reclamations.update',
            'reclamations.manage',
        ]);

        $this->manager = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->manager->assignRole($role);

        $this->assignee = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->assignee->assignRole($role);
    }

    public function test_reclamation_syncs_tracking_and_appears_in_my_tasks(): void
    {
        $create = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/reclamations', [
                'site_id' => $this->site->id,
                'customer_name' => 'Client Test',
                'customer_email' => 'client@example.test',
                'title' => 'Incident produit',
                'type' => 'quality',
                'description' => 'Description détaillée de la réclamation pour test E7.',
                'received_date' => now()->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
            ]);

        $create->assertStatus(201);
        $reclamationId = (int) $create->json('id');

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->manager->id,
            'trackable_type' => \App\Models\Reclamation::class,
            'trackable_id' => $reclamationId,
            'status' => 'non_demarre',
            'progress_rate' => 0,
        ]);

        $update = $this->actingAs($this->manager, 'sanctum')
            ->putJson("/api/v1/reclamations/{$reclamationId}", [
                'assigned_to' => $this->assignee->id,
                'status' => 'in_analysis',
            ]);

        $update->assertStatus(200);

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->assignee->id,
            'trackable_type' => \App\Models\Reclamation::class,
            'trackable_id' => $reclamationId,
            'status' => 'en_cours',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->assignee->id,
            'notifiable_type' => User::class,
        ]);

        $myTasks = $this->actingAs($this->assignee, 'sanctum')
            ->getJson('/api/v1/my-tasks?type=reclamation');

        $myTasks->assertStatus(200);
        $myTasks->assertJsonFragment([
            'type' => 'reclamation',
            'id' => $reclamationId,
        ]);
    }
}

