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

class ActionCollaboratorDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Neutralise les garde-fous hors périmètre de ces tests fonctionnels.
        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
        ]);
    }

    public function test_actions_index_can_be_filtered_by_process_id(): void
    {
        $site = Site::factory()->create();
        /** @var User $user */
        $user = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $processA */
        $processA = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);
        /** @var Process $processB */
        $processB = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        /** @var Action $actionA */
        $actionA = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $processA->id,
            'responsible_id' => $user->id,
            'initiator_id' => $user->id,
        ]);

        Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $processB->id,
            'responsible_id' => $user->id,
            'initiator_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/actions?process_id={$processA->id}&per_page=50");

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->values();
        $this->assertCount(1, $ids);
        $this->assertSame([$actionA->id], $ids->all());
    }

    public function test_responsible_collaborator_can_update_action_progress_with_observation(): void
    {
        $site = Site::factory()->create();
        /** @var User $responsible */
        $responsible = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        /** @var Action $action */
        $action = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $responsible->id,
            'initiator_id' => $responsible->id,
            'progress' => 20,
        ]);

        $response = $this->actingAs($responsible)
            ->postJson("/api/v1/actions/{$action->id}/progress", [
                'progress' => 65,
                'notes' => 'Avancement saisi depuis le dashboard collaborateur.',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('progress', 65);

        $action->refresh();
        $this->assertSame(65, (int) $action->progress);

        $notes = collect($action->progress_notes ?? []);
        $this->assertGreaterThan(0, $notes->count());
        $lastNote = $notes->last();
        $this->assertSame('Avancement saisi depuis le dashboard collaborateur.', $lastNote['note'] ?? null);
        $this->assertSame($responsible->id, (int) ($lastNote['user_id'] ?? 0));
        $this->assertSame($responsible->name, $lastNote['user_name'] ?? null);
    }

    public function test_review_dashboard_actions_are_server_filtered_for_collaborator(): void
    {
        $site = Site::factory()->create();
        /** @var User $rq */
        $rq = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $collaborator */
        $collaborator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $otherCollaborator */
        $otherCollaborator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'pilot_id' => $rq->id,
            'copilot_id' => null,
        ]);

        /** @var Action $myAction */
        $myAction = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $collaborator->id,
            'initiator_id' => $rq->id,
        ]);

        Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $otherCollaborator->id,
            'initiator_id' => $rq->id,
        ]);

        $response = $this->actingAs($collaborator)
            ->getJson("/api/v1/processes/{$process->id}/reviews/dashboard-actions");

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->values();
        $this->assertSame([$myAction->id], $ids->all());
    }

    public function test_review_dashboard_actions_show_all_for_rq(): void
    {
        $site = Site::factory()->create();
        /** @var User $rq */
        $rq = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $collaboratorA */
        $collaboratorA = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $collaboratorB */
        $collaboratorB = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'pilot_id' => $rq->id,
            'copilot_id' => null,
        ]);

        /** @var Action $actionA */
        $actionA = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $collaboratorA->id,
            'initiator_id' => $rq->id,
        ]);
        /** @var Action $actionB */
        $actionB = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $collaboratorB->id,
            'initiator_id' => $rq->id,
        ]);

        $response = $this->actingAs($rq)
            ->getJson("/api/v1/processes/{$process->id}/reviews/dashboard-actions");

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($ids);
        $expected = [$actionA->id, $actionB->id];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function test_review_dashboard_actions_show_all_for_copilot(): void
    {
        $site = Site::factory()->create();
        /** @var User $rq */
        $rq = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $copilot */
        $copilot = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $collaboratorA */
        $collaboratorA = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $collaboratorB */
        $collaboratorB = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'pilot_id' => $rq->id,
            'copilot_id' => $copilot->id,
        ]);

        /** @var Action $actionA */
        $actionA = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $collaboratorA->id,
            'initiator_id' => $rq->id,
        ]);
        /** @var Action $actionB */
        $actionB = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $collaboratorB->id,
            'initiator_id' => $rq->id,
        ]);

        $response = $this->actingAs($copilot)
            ->getJson("/api/v1/processes/{$process->id}/reviews/dashboard-actions");

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        sort($ids);
        $expected = [$actionA->id, $actionB->id];
        sort($expected);

        $this->assertSame($expected, $ids);
    }

    public function test_rq_can_see_collaborator_progress_notes_in_review_dashboard_actions(): void
    {
        $site = Site::factory()->create();
        /** @var User $rq */
        $rq = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $collaborator */
        $collaborator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'pilot_id' => $rq->id,
            'copilot_id' => null,
        ]);

        /** @var Action $action */
        $action = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $collaborator->id,
            'initiator_id' => $rq->id,
            'progress' => 15,
        ]);

        $this->actingAs($collaborator)
            ->postJson("/api/v1/actions/{$action->id}/progress", [
                'progress' => 70,
                'notes' => 'Suivi collaborateur: analyse terminee.',
            ])
            ->assertOk();

        $response = $this->actingAs($rq)
            ->getJson("/api/v1/processes/{$process->id}/reviews/dashboard-actions");

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $action->id)
            ->assertJsonPath('data.0.progress', 70);

        $progressNotes = $response->json('data.0.progress_notes');
        $this->assertIsArray($progressNotes);
        $this->assertNotEmpty($progressNotes);
        $lastNote = $progressNotes[count($progressNotes) - 1];
        $this->assertSame('Suivi collaborateur: analyse terminee.', $lastNote['note'] ?? null);
    }

    public function test_non_responsible_collaborator_cannot_update_action_progress(): void
    {
        $site = Site::factory()->create();
        /** @var User $responsible */
        $responsible = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);
        /** @var User $otherUser */
        $otherUser = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var Process $process */
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        /** @var Action $action */
        $action = Action::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
            'responsible_id' => $responsible->id,
            'initiator_id' => $responsible->id,
            'progress' => 10,
        ]);

        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/actions/{$action->id}/progress", [
                'progress' => 55,
                'notes' => 'Tentative non autorisée.',
            ]);

        $response->assertForbidden();

        $action->refresh();
        $this->assertSame(10, (int) $action->progress);
    }
}
