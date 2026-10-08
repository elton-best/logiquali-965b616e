<?php

namespace Tests\Feature;

use App\Models\Action;
use App\Models\Audit;
use App\Models\User;
use App\Models\Site;
use App\Models\Enterprise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PlanSMControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Enterprise $enterprise;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::findOrCreate('view_all_tasks');

        // Create enterprise & site
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        // Create user with permission
        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
        $this->user->givePermissionTo('view_all_tasks');
    }

    /**
     * Test GET /api/plan-sm without authentication
     */
    public function test_plan_sm_requires_authentication()
    {
        $response = $this->getJson('/api/v1/plan-sm');
        $response->assertUnauthorized();
    }

    /**
     * Test GET /api/plan-sm without permission
     */
    public function test_plan_sm_requires_view_all_tasks_permission()
    {
        $userWithoutPermission = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);

        $response = $this->actingAs($userWithoutPermission, 'sanctum')->getJson('/api/v1/plan-sm');
        $response->assertForbidden();
    }

    /**
     * Test GET /api/plan-sm returns empty array initially
     */
    public function test_plan_sm_returns_empty_tasks_initially()
    {
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'pagination',
                'filters',
            ])
            ->assertJsonPath('data', [])
            ->assertJsonPath('pagination.total', 0);
    }

    /**
     * Test GET /api/plan-sm with actions
     */
    public function test_plan_sm_aggregates_actions()
    {
        $action = Action::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'responsible_id' => $this->user->id,
            'title' => 'Test Action',
            'deadline' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'action')
            ->assertJsonPath('data.0.title', 'Test Action')
            ->assertJsonPath('data.0.responsible_id', $this->user->id)
            ->assertJsonPath('data.0.color', '#FF5252');
    }

    /**
     * Test GET /api/plan-sm with audits
     */
    public function test_plan_sm_aggregates_audits()
    {
        $audit = Audit::factory()->create([
            'site_id' => $this->site->id,
            'lead_auditor_id' => $this->user->id,
            'scheduled_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'audit')
            ->assertJsonPath('data.0.color', '#FF9800');
    }

    /**
     * Test GET /api/plan-sm with view filter
     */
    public function test_plan_sm_filters_by_view()
    {
        // Create action within current month
        Action::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'deadline' => now(),
        ]);

        // Create action outside current month
        Action::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'deadline' => now()->addMonths(3),
        ]);

        // Test default view (month)
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm?view=month');
        $response->assertJsonPath('pagination.total', 1);

        // Test day view
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm?view=day');
        $response->assertJsonPath('pagination.total', 1);
    }

    /**
     * Test GET /api/plan-sm with type filter
     */
    public function test_plan_sm_filters_by_type()
    {
        Action::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'deadline' => now(),
        ]);

        Audit::factory()->create([
            'site_id' => $this->site->id,
            'scheduled_date' => now(),
        ]);

        // Get only actions
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm?type=action');
        $response->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'action');

        // Get only audits
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm?type=audit');
        $response->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('data.0.type', 'audit');
    }

    /**
     * Test GET /api/plan-sm with pagination
     */
    public function test_plan_sm_paginates_results()
    {
        // Create 75 actions
        Action::factory(75)->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'deadline' => now(),
        ]);

        // Default pagination (50 per page)
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm');
        $response->assertJsonPath('pagination.total', 75)
            ->assertJsonPath('pagination.per_page', 50)
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2);

        // Second page
        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm?page=2');
        $response->assertJsonPath('pagination.current_page', 2);
    }

    /**
     * Test GET /api/plan-sm response structure
     */
    public function test_plan_sm_response_structure()
    {
        Action::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'responsible_id' => $this->user->id,
            'deadline' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'start_date',
                        'deadline',
                        'status',
                        'responsible_id',
                        'responsible_name',
                        'involved_people',
                        'frequency',
                        'process_id',
                        'color',
                    ],
                ],
                'pagination' => [
                    'total',
                    'per_page',
                    'current_page',
                    'last_page',
                ],
                'filters' => [
                    'view',
                    'start_date',
                    'end_date',
                    'user_ids',
                    'process_id',
                    'types',
                ],
            ]);
    }

    /**
     * Test tenant isolation - user only sees enterprise tasks
     */
    public function test_plan_sm_respects_tenant_isolation()
    {
        // Create action in same enterprise
        Action::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'deadline' => now(),
        ]);

        // Create action in different enterprise
        $otherEnterprise = Enterprise::factory()->create();
        Action::factory()->create([
            'enterprise_id' => $otherEnterprise->id,
            'site_id' => Site::factory()->create(['enterprise_id' => $otherEnterprise->id])->id,
            'deadline' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/v1/plan-sm');

        // Should only see 1 action (from same enterprise)
        $response->assertJsonPath('pagination.total', 1);
    }
}
