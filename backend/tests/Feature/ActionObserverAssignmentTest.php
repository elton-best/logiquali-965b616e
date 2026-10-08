<?php

namespace Tests\Feature;

use App\Events\Action\ActionAssigned;
use App\Models\Action;
use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActionObserverAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_assigned_event_is_dispatched_on_create_when_responsible_exists(): void
    {
        Event::fake([ActionAssigned::class]);

        [$enterprise, $site, $process, $initiator, $responsible] = $this->buildActionContext();

        $action = Action::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $initiator->id,
            'responsible_id' => $responsible->id,
            'status' => 'planned',
        ]);

        Event::assertDispatched(ActionAssigned::class, function (ActionAssigned $event) use ($action, $responsible) {
            return $event->action->id === $action->id
                && (int) $event->action->responsible_id === (int) $responsible->id;
        });
    }

    public function test_action_assigned_event_is_not_dispatched_on_update_when_responsible_is_unchanged(): void
    {
        Event::fake([ActionAssigned::class]);

        [$enterprise, $site, $process, $initiator, $responsible] = $this->buildActionContext();

        $action = Action::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $initiator->id,
            'responsible_id' => $responsible->id,
            'status' => 'planned',
        ]);

        Event::assertDispatchedTimes(ActionAssigned::class, 1);

        $action->update([
            'title' => $action->title . ' (edited)',
        ]);

        Event::assertDispatchedTimes(ActionAssigned::class, 1);
    }

    public function test_action_assigned_event_is_dispatched_when_responsible_changes(): void
    {
        Event::fake([ActionAssigned::class]);

        [$enterprise, $site, $process, $initiator, $firstResponsible] = $this->buildActionContext();
        $secondResponsible = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $action = Action::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $initiator->id,
            'responsible_id' => $firstResponsible->id,
            'status' => 'planned',
        ]);

        $action->update([
            'responsible_id' => $secondResponsible->id,
        ]);

        Event::assertDispatchedTimes(ActionAssigned::class, 2);
        Event::assertDispatched(ActionAssigned::class, function (ActionAssigned $event) use ($action, $secondResponsible) {
            return $event->action->id === $action->id
                && (int) $event->action->responsible_id === (int) $secondResponsible->id;
        });
    }

    public function test_action_assigned_event_is_not_dispatched_when_status_changes_only(): void
    {
        Event::fake([ActionAssigned::class]);

        [$enterprise, $site, $process, $initiator, $responsible] = $this->buildActionContext();

        $action = Action::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $initiator->id,
            'responsible_id' => $responsible->id,
            'status' => 'planned',
        ]);

        Event::assertDispatchedTimes(ActionAssigned::class, 1);

        $action->update([
            'status' => 'completed',
        ]);

        Event::assertDispatchedTimes(ActionAssigned::class, 1);
    }

    /**
     * @return array{Enterprise, Site, Process, User, User}
     */
    private function buildActionContext(): array
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
        ]);
        $initiator = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $responsible = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $process = Process::factory()->create([
            'site_id' => $site->id,
            'pilot_id' => $initiator->id,
            'copilot_id' => null,
        ]);

        return [$enterprise, $site, $process, $initiator, $responsible];
    }
}
