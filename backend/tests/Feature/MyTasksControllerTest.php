<?php

namespace Tests\Feature;

use App\Models\Enterprise;
use App\Models\NonConformity;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MyTasksControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Enterprise $enterprise;
    protected Site $site;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
        ]);
    }

    public function test_my_tasks_filters_non_conformities_by_month_range(): void
    {
        $month = now()->format('Y-m');
        $insideDeadline = now()->copy()->startOfMonth()->addDays(5);
        $outsideDeadline = now()->copy()->addMonth()->startOfMonth()->addDays(5);

        $inside = $this->createNonConformity($insideDeadline->toDateString());
        $this->createNonConformity($outsideDeadline->toDateString());

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/my-tasks?type=non_conformity&month={$month}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $inside->id)
            ->assertJsonPath('data.0.type', 'non_conformity');
    }

    public function test_my_tasks_non_conformity_query_works_without_investigator_column(): void
    {
        if (Schema::hasColumn('non_conformities', 'investigator_user_ids')) {
            Schema::table('non_conformities', function ($table) {
                $table->dropColumn('investigator_user_ids');
            });
        }

        $nc = $this->createNonConformity(now()->copy()->addDays(3)->toDateString());

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/my-tasks?type=non_conformity&month=' . now()->format('Y-m'));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $nc->id);
    }

    private function createNonConformity(string $deadline): NonConformity
    {
        return NonConformity::query()->create([
            'ref' => 'NC-TEST-' . uniqid(),
            'site_id' => $this->site->id,
            'title' => 'NC test',
            'type' => 'normative',
            'severity' => 'minor',
            'description' => 'Description de non-conformité de test.',
            'detected_by' => $this->user->id,
            'responsible_id' => $this->user->id,
            'deadline' => $deadline,
            'status' => 'open',
        ]);
    }
}
