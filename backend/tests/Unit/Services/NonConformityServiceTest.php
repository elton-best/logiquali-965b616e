<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use App\Models\NonConformity;
use App\Models\Risk;
use App\Models\Site;
use App\Models\Process;
use App\Services\NonConformityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NonConformityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NonConformityService $service;
    protected Site $site;
    protected Process $process;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(NonConformityService::class);
        $this->site = Site::factory()->create();
        $this->process = Process::factory()->create(['site_id' => $this->site->id]);
    }
    #[Test]
    public function it_creates_non_conformity_successfully()
    {
        $user = \App\Models\User::factory()->create(['site_id' => $this->site->id]);
        
        $data = [
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'title' => 'Test NC',
            'description' => 'Test non-conformity description',
            'type' => 'normative',
            'severity' => 'major',
            'source' => 'audit',
            'detected_by' => $user->id,
        ];

        $nc = $this->service->create($data);

        $this->assertInstanceOf(NonConformity::class, $nc);
        $this->assertEquals('Test NC', $nc->title);
        $this->assertEquals('major', $nc->severity);
        $this->assertDatabaseHas('non_conformities', [
            'title' => 'Test NC',
            'severity' => 'major',
        ]);
    }
    #[Test]
    public function it_increments_risk_occurrence_when_nc_linked_to_risk()
    {
        $user = \App\Models\User::factory()->create(['site_id' => $this->site->id]);
        
        $risk = Risk::factory()->create([
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'actual_occurrences' => 0,
        ]);

        $this->assertEquals(0, $risk->actual_occurrences);
        $this->assertNull($risk->last_occurrence_at);

        $data = [
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'title' => 'NC from Risk',
            'description' => 'Non-conformity linked to a risk',
            'type' => 'normative',
            'severity' => 'major',
            'source' => 'risk',
            'risk_id' => $risk->id,
            'detected_by' => $user->id,
        ];

        $nc = $this->service->create($data);

        $risk->refresh();
        
        $this->assertEquals(1, $risk->actual_occurrences);
        $this->assertNotNull($risk->last_occurrence_at);
        $this->assertTrue($nc->risks->contains($risk->id));
    }
    #[Test]
    public function it_increments_occurrence_multiple_times()
    {
        $user = \App\Models\User::factory()->create(['site_id' => $this->site->id]);
        
        $risk = Risk::factory()->create([
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'actual_occurrences' => 2,
        ]);

        $data = [
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'title' => 'Another NC',
            'description' => 'Another occurrence of the same risk',
            'type' => 'normative',
            'severity' => 'minor',
            'source' => 'risk',
            'risk_id' => $risk->id,
            'detected_by' => $user->id,
        ];

        $this->service->create($data);

        $risk->refresh();
        $this->assertEquals(3, $risk->actual_occurrences);
    }
}
