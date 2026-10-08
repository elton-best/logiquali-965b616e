<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;

use App\Models\Reclamation;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReclamationModelTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function it_auto_generates_reference_on_create()
    {
        $site = Site::factory()->create();
        
        $reclamation = Reclamation::create([
            'site_id' => $site->id,
            'title' => 'Test Reclamation',
            'description' => 'Test description for reclamation',
            'source' => 'customer',
            'severity' => 'major',
            'status' => 'pending',
            'received_date' => now(),
        ]);

        $this->assertNotNull($reclamation->ref);
        $this->assertMatchesRegularExpression('/^REC-\d{4}-\d{3}$/', $reclamation->ref);
    }
    #[Test]
    public function it_calculates_deadline_based_on_severity_critical()
    {
        $site = Site::factory()->create();
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        $reclamation = Reclamation::create([
            'site_id' => $site->id,
            'title' => 'Critical Issue',
            'description' => 'Very urgent problem needing immediate attention',
            'source' => 'customer',
            'severity' => 'critical',
            'status' => 'pending',
            'received_date' => $now,
        ]);

        $expectedDeadline = $now->copy()->addHours(24);
        $this->assertEquals($expectedDeadline->format('Y-m-d'), $reclamation->due_date->format('Y-m-d'));
    }
    #[Test]
    public function it_calculates_deadline_based_on_severity_major()
    {
        $site = Site::factory()->create();
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        $reclamation = Reclamation::create([
            'site_id' => $site->id,
            'title' => 'Major Issue',
            'description' => 'Important problem that needs quick response',
            'source' => 'customer',
            'severity' => 'major',
            'status' => 'pending',
            'received_date' => $now,
        ]);

        $expectedDeadline = $now->copy()->addDays(3);
        $this->assertEquals($expectedDeadline->format('Y-m-d'), $reclamation->due_date->format('Y-m-d'));
    }
    #[Test]
    public function it_calculates_deadline_based_on_severity_minor()
    {
        $site = Site::factory()->create();
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        $reclamation = Reclamation::create([
            'site_id' => $site->id,
            'title' => 'Minor Issue',
            'description' => 'Small problem that can wait a bit',
            'source' => 'customer',
            'severity' => 'minor',
            'status' => 'pending',
            'received_date' => $now,
        ]);

        $expectedDeadline = $now->copy()->addDays(7);
        $this->assertEquals($expectedDeadline->format('Y-m-d'), $reclamation->due_date->format('Y-m-d'));
    }
}
