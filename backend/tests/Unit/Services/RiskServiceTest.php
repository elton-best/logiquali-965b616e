<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use App\Models\Risk;
use App\Models\Site;
use App\Models\Process;
use App\Services\RiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RiskService $service;
    protected Site $site;
    protected Process $process;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(RiskService::class);
        $this->site = Site::factory()->create();
        $this->process = Process::factory()->create(['site_id' => $this->site->id]);
    }
    #[Test]
    public function it_creates_risk_with_all_required_fields()
    {
        $data = [
            'title' => 'Fire Risk',
            'description' => 'Risk of fire in the production area',
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'type' => 'risk',
            'category' => 'safety',
            'probability' => 3,
            'gravity' => 4,
        ];

        $risk = $this->service->create($data);

        $this->assertInstanceOf(Risk::class, $risk);
        $this->assertEquals('Fire Risk', $risk->title);
        $this->assertEquals(3, $risk->probability);
        $this->assertEquals(4, $risk->gravity);
        $this->assertEquals(12, $risk->criticality);
        $this->assertEquals('critical', $risk->criticality_level);
    }
    #[Test]
    public function it_generates_unique_reference()
    {
        $data = [
            'title' => 'Risk 1',
            'description' => 'First risk description',
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'type' => 'risk',
            'category' => 'operational',
            'probability' => 2,
            'gravity' => 2,
        ];

        $risk1 = $this->service->create($data);
        
        $data['title'] = 'Risk 2';
        $risk2 = $this->service->create($data);

        $this->assertNotEquals($risk1->ref, $risk2->ref);
        $this->assertMatchesRegularExpression('/^RSQ-\d{4}-\d+$/', $risk1->ref);
        $this->assertMatchesRegularExpression('/^RSQ-\d{4}-\d+$/', $risk2->ref);
    }
    #[Test]
    public function it_handles_opportunity_type()
    {
        $data = [
            'title' => 'Market Opportunity',
            'description' => 'Potential new market segment',
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'type' => 'opportunity',
            'category' => 'strategic',
            'probability' => 4,
            'gravity' => 3,
        ];

        $opportunity = $this->service->create($data);

        $this->assertEquals('opportunity', $opportunity->type);
        $this->assertMatchesRegularExpression('/^OPP-\d{4}-\d+$/', $opportunity->ref);
    }
    #[Test]
    public function it_saves_initial_assessment()
    {
        $data = [
            'title' => 'Assessment Test',
            'description' => 'Testing initial assessment storage',
            'site_id' => $this->site->id,
            'process_id' => $this->process->id,
            'type' => 'risk',
            'category' => 'compliance',
            'probability' => 5,
            'gravity' => 5,
        ];

        $risk = $this->service->create($data);

        $this->assertNotNull($risk->initial_assessment);
        $this->assertIsArray($risk->initial_assessment);
        $this->assertEquals(5, $risk->initial_assessment['probability']);
        $this->assertEquals(5, $risk->initial_assessment['gravity']);
        $this->assertEquals(25, $risk->initial_assessment['criticality']);
    }
}
