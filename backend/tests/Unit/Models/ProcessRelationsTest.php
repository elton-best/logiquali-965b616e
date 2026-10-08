<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\OperationalControl;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProcessRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_process_has_owner_and_operational_controls_relations(): void
    {
        $site = Site::factory()->create();
        $owner = User::factory()->create(['site_id' => $site->id, 'enterprise_id' => $site->enterprise_id]);

        $process = Process::factory()->create([
            'site_id' => $site->id,
            'process_owner_id' => $owner->id,
        ]);

        OperationalControl::factory()->count(2)->create([
            'enterprise_id' => $site->enterprise_id,
            'process_id' => $process->id,
        ]);

        $this->assertNotNull($process->owner);
        $this->assertEquals(2, $process->operationalControls()->count());
    }
}
