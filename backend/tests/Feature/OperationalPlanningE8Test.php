<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Action;
use App\Models\Enterprise;
use App\Models\OperationalProject;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OperationalPlanningE8Test extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $user;
    private Process $process;

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
        Permission::firstOrCreate(['name' => 'actions.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'actions.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'actions.update', 'guard_name' => 'web']);
        $role->givePermissionTo(['actions.read', 'actions.create', 'actions.update']);

        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->user->assignRole($role);
    }

    public function test_overview_includes_my_tasks_aggregation_and_project_tracking_sync(): void
    {
        Action::query()->create([
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id,
            'process_id' => $this->process->id,
            'type' => 'corrective',
            'title' => 'Action E8',
            'description' => 'Action visible dans mes tâches',
            'initiator_id' => $this->user->id,
            'responsible_id' => $this->user->id,
            'status' => 'planned',
            'created_by' => $this->user->id,
        ]);

        $overview = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/operational-planning/overview?site_id=' . $this->site->id);

        $overview->assertStatus(200);
        $overview->assertJsonFragment([
            'source_module' => 'actions',
            'title' => 'Action E8',
            'source_title' => 'Action E8',
        ]);

        $projectResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/operational-projects', [
                'site_id' => $this->site->id,
                'title' => 'Projet E8',
                'status' => 'planned',
                'priority' => 'medium',
            ]);
        $projectResponse->assertStatus(201);
        $projectId = (int) $projectResponse->json('data.id');

        $activityResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/operational-projects/{$projectId}/activities", [
                'title' => 'Activité E8',
                'status' => 'in_progress',
                'priority' => 'high',
                'responsible_user_id' => $this->user->id,
            ]);
        $activityResponse->assertStatus(201);
        $activityId = (int) $activityResponse->json('data.id');

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->user->id,
            'trackable_type' => \App\Models\OperationalProjectActivity::class,
            'trackable_id' => $activityId,
            'status' => 'en_cours',
        ]);

        $taskResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/operational-project-activities/{$activityId}/tasks", [
                'title' => 'Tâche E8',
                'status' => 'todo',
                'priority' => 'medium',
                'responsible_user_id' => $this->user->id,
            ]);
        $taskResponse->assertStatus(201);
        $taskId = (int) $taskResponse->json('data.id');

        $this->assertDatabaseHas('task_tracking', [
            'user_id' => $this->user->id,
            'trackable_type' => \App\Models\OperationalProjectTask::class,
            'trackable_id' => $taskId,
            'status' => 'non_demarre',
            'progress_rate' => 0,
        ]);
    }
}
