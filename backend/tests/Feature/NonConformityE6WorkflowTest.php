<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NonConformityE6WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private Process $process;
    private User $manager;
    private User $responsible;

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
        $this->process = Process::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'non_conformities.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'non_conformities.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'non_conformities.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'non_conformities.manage', 'guard_name' => 'web']);
        $role->givePermissionTo([
            'non_conformities.create',
            'non_conformities.read',
            'non_conformities.update',
            'non_conformities.manage',
        ]);

        $this->manager = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->manager->assignRole($role);

        $this->responsible = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->responsible->assignRole($role);
    }

    public function test_non_conformity_syncs_task_tracking_notifies_and_appears_in_my_tasks(): void
    {
        $createResponse = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/v1/non-conformities', [
                'site_id' => $this->site->id,
                'process_id' => $this->process->id,
                'title' => 'NC E6',
                'description' => 'Test E6 workflow NC',
                'type' => 'normative',
                'severity' => 'major',
                'source' => 'internal',
                'responsible_id' => $this->manager->id,
                'detected_by' => $this->manager->id,
                'deadline' => now()->addDays(7)->toDateString(),
            ]);

        $createResponse->assertStatus(201);
        $ncId = (int) $createResponse->json('id');

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->manager->id,
            'trackable_type' => \App\Models\NonConformity::class,
            'trackable_id' => $ncId,
            'status' => 'non_demarre',
            'progress_rate' => 0,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/non-conformities/{$ncId}/assign", [
                'responsible_id' => $this->responsible->id,
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->responsible->id,
            'trackable_type' => \App\Models\NonConformity::class,
            'trackable_id' => $ncId,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->responsible->id,
            'notifiable_type' => User::class,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/v1/non-conformities/{$ncId}/status", [
                'status' => 'analysis',
                'comments' => 'Analyse démarrée',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->responsible->id,
            'trackable_type' => \App\Models\NonConformity::class,
            'trackable_id' => $ncId,
            'status' => 'en_cours',
        ]);

        $myTasks = $this->actingAs($this->responsible, 'sanctum')
            ->getJson('/api/v1/my-tasks?type=non_conformity');

        $myTasks->assertStatus(200);
        $myTasks->assertJsonFragment([
            'type' => 'non_conformity',
            'id' => $ncId,
        ]);
    }
}

