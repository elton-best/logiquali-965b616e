<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Models\Action;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaboratorDashboardActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
        ]);
    }

    public function test_dashboard_collaborator_actions_returns_only_current_user_actions(): void
    {
        $site = Site::factory()->create();
        /** @var User $me */
        $me = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $other */
        $other = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        /** @var Action $myOpenAction */
        $myOpenAction = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $me->id,
            'initiator_id' => $me->id,
            'status' => 'in_progress',
            'deadline' => now()->subDay()->toDateString(),
        ]);

        Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $other->id,
            'initiator_id' => $other->id,
            'status' => 'in_progress',
        ]);

        Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $me->id,
            'initiator_id' => $me->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($me)
            ->getJson('/api/v1/dashboard/collaborator-actions');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stats.open_count', 1)
            ->assertJsonPath('data.stats.overdue_count', 1)
            ->assertJsonPath('data.by_process.0.process_id', $process->id);

        $firstProcessRow = $response->json('data.by_process.0');
        $this->assertIsArray($firstProcessRow);
        $this->assertGreaterThanOrEqual(1, (int) ($firstProcessRow['open_count'] ?? 0));

        $ids = collect($response->json('data.actions'))->pluck('id')->map(fn ($id) => (int) $id)->values();
        $this->assertSame([$myOpenAction->id], $ids->all());
    }

    public function test_dashboard_collaborator_actions_can_include_closed(): void
    {
        $site = Site::factory()->create();
        /** @var User $me */
        $me = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        /** @var Action $openAction */
        $openAction = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $me->id,
            'initiator_id' => $me->id,
            'status' => 'in_progress',
        ]);
        /** @var Action $closedAction */
        $closedAction = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $me->id,
            'initiator_id' => $me->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($me)
            ->getJson('/api/v1/dashboard/collaborator-actions?include_closed=1');

        $response->assertOk();

        $ids = collect($response->json('data.actions'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($ids);
        $expected = [$openAction->id, $closedAction->id];
        sort($expected);

        $this->assertSame($expected, $ids);
    }
}
