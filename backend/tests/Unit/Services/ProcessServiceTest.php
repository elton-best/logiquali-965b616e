<?php

namespace Tests\Unit\Services;

use App\Models\Site;
use App\Services\ProcessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_category_maps_to_pilotage_code(): void
    {
        $site = Site::factory()->create();
        $service = app(ProcessService::class);

        $code = $service->generateProcessCode('management', $site->id);

        $this->assertStringStartsWith('P-PIL-', $code);
    }
}
