<?php

namespace Tests\Feature\Commands;

use App\Models\Action;
use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Reclamation;
use App\Models\Site;
use App\Models\User;
use App\Notifications\Complaint\IncidentAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckIncidentAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_notifies_overdue_incident_and_triggers_overdue_action_event(): void
    {
        Event::fake();
        Notification::fake();

        $enterprise = Enterprise::factory()->create([
            'ref' => 'ENT-TEST-ALERTS-001',
        ]);
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
            'ref' => 'SITE-TEST-ALERTS-001',
        ]);
        $assignee = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
        ]);

        $incident = Reclamation::query()->create([
            'ref' => 'REC-TEST-INC-001',
            'site_id' => $site->id,
            'user_id' => $assignee->id,
            'assigned_to' => $assignee->id,
            'title' => 'Incident test commande',
            'description' => 'Incident à alerter',
            'severity' => 'major',
            'status' => 'in_progress',
            'received_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->subDay()->toDateString(), // J+1 de retard
        ]);

        $process = Process::query()->create([
            'ref' => 'PROC-TEST-ALERTS-001',
            'code' => 'PROC-ALERTS-001',
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'title' => 'Processus test alertes incidents',
            'type' => 'management',
            'purpose' => 'Processus de test pour alertes incidents.',
            'pilot_id' => $assignee->id,
            'copilot_id' => null,
            'is_validated' => true,
            'validated_by' => null,
            'validated_at' => null,
        ]);

        Action::factory()->create([
            'ref' => 'ACT-TEST-ALERTS-001',
            'site_id' => $site->id,
            'process_id' => $process->id,
            'initiator_id' => $assignee->id,
            'responsible_id' => $assignee->id,
            'source_type' => 'reclamation',
            'source_id' => $incident->id,
            'type' => 'corrective',
            'title' => 'Action incident échue',
            'status' => 'in_progress',
            'deadline' => now()->subDay()->toDateString(), // J+1 de retard
        ]);

        $code = Artisan::call('incidents:check-alerts', ['--days' => '1,7,14,30']);
        $this->assertSame(0, $code);

        Notification::assertSentTo(
            [$assignee],
            IncidentAlertNotification::class
        );
    }
}
