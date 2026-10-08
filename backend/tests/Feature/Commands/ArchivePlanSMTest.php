<?php

namespace Tests\Feature\Commands;

use App\Models\Enterprise;
use App\Models\Plan;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ArchivePlanSMTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_plan_sm_archives_current_year_plan_and_seeds_next_year_plan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 12, 31, 23, 55, 0));

        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
        ]);

        $currentYear = now()->year;
        $nextYear = $currentYear + 1;

        $plan = Plan::query()->create([
            'site_id' => $site->id,
            'type' => 'smq',
            'title' => "Planification du SMQ {$currentYear}",
            'year' => $currentYear,
            'content' => [
                'note' => 'Plan existant',
                'format_plan' => 'jour',
                'display_mode' => 'liste',
                'activities' => [],
            ],
            'status' => 'validated',
        ]);

        $code = Artisan::call('plans:archive-sm');

        Carbon::setTestNow();

        $this->assertSame(0, $code);

        $plan->refresh();
        $this->assertSame('archived', $plan->status);

        $nextPlan = Plan::query()
            ->where('site_id', $site->id)
            ->where('type', 'smq')
            ->where('year', $nextYear)
            ->first();

        $this->assertNotNull($nextPlan);
        $this->assertSame('draft', $nextPlan->status);
        $this->assertSame("Planification du SMQ {$nextYear}", $nextPlan->title);
        $this->assertSame('Plan existant', data_get($nextPlan->content, 'note'));
    }
}
