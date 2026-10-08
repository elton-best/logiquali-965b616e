<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use App\Models\Risk;
use App\Models\Site;
use App\Models\Process;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskModelTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function it_auto_calculates_criticality_on_save()
    {
        $site = Site::factory()->create();
        $process = Process::factory()->create(['site_id' => $site->id]);
        
        $risk = Risk::create([
            'title' => 'Test Risk',
            'description' => 'Test description for the risk',
            'site_id' => $site->id,
            'process_id' => $process->id,
            'type' => 'risk',
            'category' => 'safety',
            'probability' => 4,
            'gravity' => 5,
        ]);

        $this->assertEquals(20, $risk->criticality);
        $this->assertEquals('critical', $risk->criticality_level);
    }
    #[Test]
    public function it_updates_criticality_when_probability_changes()
    {
        $site = Site::factory()->create();
        $process = Process::factory()->create(['site_id' => $site->id]);
        
        $risk = Risk::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'probability' => 2,
            'gravity' => 3,
        ]);

        $this->assertEquals(6, $risk->criticality);
        $this->assertEquals('medium', $risk->criticality_level);

        $risk->update(['probability' => 5]);

        $this->assertEquals(15, $risk->criticality);
        $this->assertEquals('critical', $risk->criticality_level);
    }
    #[Test]
    public function it_categorizes_criticality_levels_correctly()
    {
        $site = Site::factory()->create();
        $process = Process::factory()->create(['site_id' => $site->id]);
        
        // Low: 1-4
        $lowRisk = Risk::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'probability' => 1,
            'gravity' => 1,
        ]);
        $this->assertEquals('low', $lowRisk->criticality_level);

        // Medium: 5-9
        $mediumRisk = Risk::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'probability' => 3,
            'gravity' => 2,
        ]);
        $this->assertEquals('medium', $mediumRisk->criticality_level);

        // Critical (matrice 4x4): >= 12
        $highRisk = Risk::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'probability' => 3,
            'gravity' => 4,
        ]);
        $this->assertEquals('critical', $highRisk->criticality_level);

        // Critical: ≥15
        $criticalRisk = Risk::factory()->create([
            'site_id' => $site->id,
            'process_id' => $process->id,
            'probability' => 5,
            'gravity' => 5,
        ]);
        $this->assertEquals('critical', $criticalRisk->criticality_level);
    }
    #[Test]
    public function it_has_title_in_fillable()
    {
        $risk = new Risk();
        $this->assertContains('title', $risk->getFillable());
        $this->assertContains('criticality_level', $risk->getFillable());
        $this->assertContains('actual_occurrences', $risk->getFillable());
    }
}
