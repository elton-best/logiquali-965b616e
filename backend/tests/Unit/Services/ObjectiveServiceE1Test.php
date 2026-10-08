<?php

namespace Tests\Unit\Services;

use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use App\Services\ObjectiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObjectiveServiceE1Test extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_planned_actions_and_computes_objective_progress_from_actions(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $user = User::factory()->create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum');
        $process = Process::factory()->create([
            'site_id' => $site->id,
            'pilot_id' => $user->id,
            'copilot_id' => null,
        ]);

        $service = app(ObjectiveService::class);
        $objective = $service->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'title' => 'Objectif E1',
            'target_value' => 100,
            'deadline' => now()->addMonth()->toDateString(),
            'planned_actions' => [
                [
                    'title' => 'Action 1',
                    'description' => 'Description action 1',
                    'responsible_user_id' => $user->id,
                    'status' => 'planned',
                ],
                [
                    'title' => 'Action 2',
                    'description' => 'Description action 2',
                    'responsible_user_id' => $user->id,
                    'status' => 'in_progress',
                    'progress' => 50,
                ],
                [
                    'title' => 'Action 3',
                    'description' => 'Description action 3',
                    'responsible_user_id' => $user->id,
                    'status' => 'completed',
                ],
            ],
        ]);

        $objective->refresh();
        $actions = $objective->actions()->where('source_type', 'objective')->orderBy('title')->get();

        $this->assertCount(3, $actions);
        $this->assertSame(0, (int) ($actions[0]->progress_rate ?? $actions[0]->progress ?? 0));
        $this->assertSame(100, (int) ($actions[2]->progress_rate ?? $actions[2]->progress ?? 0));
        $this->assertSame(50, (int) $objective->progress);
    }
}
