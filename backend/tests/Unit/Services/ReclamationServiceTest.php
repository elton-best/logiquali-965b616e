<?php

namespace Tests\Unit\Services;

use App\Models\NonConformity;
use App\Models\Process;
use App\Models\Reclamation;
use App\Models\Site;
use App\Models\User;
use App\Models\WorkflowState;
use App\Services\ReclamationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReclamationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WorkflowState::query()->create([
            'entity_type' => 'Reclamation',
            'code' => 'closed',
            'label' => 'Clôturée',
            'color' => '#2E7D32',
            'is_initial' => false,
            'is_final' => true,
            'is_active' => true,
            'allowed_transitions' => [],
            'order' => 99,
        ]);
    }

    #[Test]
    public function it_creates_nc_and_corrective_action_links_when_closing_incident(): void
    {
        $site = Site::factory()->create();
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);
        /** @var User $user */
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $this->actingAs($user);

        $reclamation = Reclamation::create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'assigned_to' => $user->id,
            'title' => 'Presque accident manutention',
            'description' => 'Glissade sans blessure dans la zone de chargement.',
            'severity' => 'major',
            'status' => 'in_progress',
            'received_date' => now()->subDay(),
            'due_date' => now()->addDays(10),
        ]);

        // Force process resolution path from linked actions.
        $reclamation->addAction(\App\Models\Action::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $user->id,
            'responsible_id' => $user->id,
            'type' => 'corrective',
            'source' => 'complaint',
            'title' => 'Action initiale incident',
            'status' => 'in_progress',
        ]));

        $service = app(ReclamationService::class);
        $service->close($reclamation, [
            'satisfaction_score' => 4,
            'satisfaction_comments' => 'Traitement satisfaisant.',
        ]);

        $ncTitle = 'NC Incident - ' . $reclamation->ref;
        $nc = NonConformity::query()
            ->where('site_id', $site->id)
            ->where('source', 'complaint')
            ->where('title', $ncTitle)
            ->first();

        $this->assertNotNull($nc);
        $this->assertSame($process->id, (int) $nc->process_id);

        $linkedAction = $reclamation->fresh()->actions()
            ->where('source_type', 'reclamation')
            ->where('source_id', $reclamation->id)
            ->where('type', 'corrective')
            ->latest('actions.id')
            ->first();

        $this->assertNotNull($linkedAction);
        $this->assertTrue($nc->actions()->where('actions.id', $linkedAction->id)->exists());
        $this->assertSame($reclamation->id, (int) $linkedAction->complaint_id);
        $this->assertSame(4, (int) $reclamation->fresh()->satisfaction_rating);
    }

    #[Test]
    public function it_does_not_duplicate_nc_or_corrective_action_on_multiple_closures(): void
    {
        $site = Site::factory()->create();
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);
        /** @var User $user */
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $this->actingAs($user);

        $reclamation = Reclamation::create([
            'site_id' => $site->id,
            'user_id' => $user->id,
            'assigned_to' => $user->id,
            'title' => 'Incident répétitif atelier',
            'description' => 'Presque accident signalé.',
            'severity' => 'minor',
            'status' => 'in_progress',
            'received_date' => now()->subDay(),
            'due_date' => now()->addDays(7),
        ]);

        $reclamation->addAction(\App\Models\Action::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $user->id,
            'responsible_id' => $user->id,
            'type' => 'corrective',
            'source' => 'complaint',
            'title' => 'Action initiale 2',
            'status' => 'planned',
        ]));

        $service = app(ReclamationService::class);
        $service->close($reclamation);
        $service->close($reclamation->fresh());

        $ncTitle = 'NC Incident - ' . $reclamation->ref;
        $this->assertSame(1, NonConformity::query()
            ->where('site_id', $site->id)
            ->where('source', 'complaint')
            ->where('title', $ncTitle)
            ->count());

        $this->assertSame(1, $reclamation->fresh()->actions()
            ->where('source_type', 'reclamation')
            ->where('source_id', $reclamation->id)
            ->where('type', 'corrective')
            ->count());
    }
}
