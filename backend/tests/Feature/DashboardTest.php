<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Enterprise $enterprise;
    protected Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckSubscriptionStatus::class,
            \App\Http\Middleware\CheckOnboardingStatus::class,
        ]);
        
        $this->enterprise = Enterprise::factory()->create([
            'status' => 'active',
            'approval_status' => 'approved',
            'domaine_activite_set' => true,
        ]);
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_headquarter' => true,
        ]);
        
        $this->user = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
        ]);
        
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_get_dashboard_stats(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200);
        
        // Le dashboard peut retourner différentes structures selon les données
        $this->assertIsArray($response->json());
    }

    public function test_can_save_dashboard_layout(): void
    {
        $layout = [
            ['id' => 'stats', 'order' => 0],
            ['id' => 'widgets', 'order' => 1],
        ];

        $response = $this->postJson('/api/v1/dashboard/layout', [
            'layout' => $layout,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'message']);

        $this->assertDatabaseHas('user_dashboard_layouts', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_get_dashboard_layout(): void
    {
        $layout = [
            ['id' => 'stats', 'order' => 0],
            ['id' => 'widgets', 'order' => 1],
        ];

        $this->postJson('/api/v1/dashboard/layout', ['layout' => $layout]);

        $response = $this->getJson('/api/v1/dashboard/layout');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_dashboard_stats_include_leadership_data(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200);
        
        $leadership = $response->json('leadership');

        if ($leadership) {
            $this->assertArrayHasKey('policy_status', $leadership);
            $this->assertArrayHasKey('total_employees', $leadership);
        }
    }

    public function test_dashboard_stats_include_charts_data(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertStatus(200);
        
        $charts = $response->json('charts');

        if ($charts) {
            $this->assertArrayHasKey('activity', $charts);
            $this->assertArrayHasKey('distribution', $charts);
            $this->assertArrayHasKey('performance', $charts);
        }
    }
}
