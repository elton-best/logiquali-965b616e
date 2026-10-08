<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\AspectEnvironnemental;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AspectEnvironnementalTest extends TestCase
{
    use RefreshDatabase;

    public function test_criticite_calculation()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        $aspect = AspectEnvironnemental::create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'type' => 'emission_air',
            'designation' => 'Test Aspect',
            'condition' => 'normale',
            'gravite' => 4,
            'frequence' => 5,
            'detectabilite' => 3,
            'mesures_maitrise' => 'Test'
        ]);

        $this->assertEquals(60, $aspect->criticite);
    }

    public function test_aspect_significatif_auto_set()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        $aspect = AspectEnvironnemental::create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'type' => 'emission_air',
            'designation' => 'Test Aspect Significatif',
            'condition' => 'normale',
            'gravite' => 5,
            'frequence' => 5,
            'detectabilite' => 5,
            'mesures_maitrise' => 'Test'
        ]);

        $this->assertTrue($aspect->aspect_significatif);
        $this->assertEquals(125, $aspect->criticite);
    }

    public function test_aspect_non_significatif()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        $aspect = AspectEnvironnemental::create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'type' => 'emission_air',
            'designation' => 'Test Aspect Non Significatif',
            'condition' => 'normale',
            'gravite' => 2,
            'frequence' => 2,
            'detectabilite' => 2,
            'mesures_maitrise' => 'Test'
        ]);

        $this->assertFalse($aspect->aspect_significatif);
        $this->assertEquals(8, $aspect->criticite);
    }

    public function test_significatifs_scope()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        AspectEnvironnemental::create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'type' => 'emission_air',
            'designation' => 'Aspect Significatif',
            'condition' => 'normale',
            'gravite' => 5,
            'frequence' => 5,
            'detectabilite' => 5,
            'mesures_maitrise' => 'Test'
        ]);

        AspectEnvironnemental::create([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'type' => 'emission_air',
            'designation' => 'Aspect Non Significatif',
            'condition' => 'normale',
            'gravite' => 1,
            'frequence' => 1,
            'detectabilite' => 1,
            'mesures_maitrise' => 'Test'
        ]);

        $significatifs = AspectEnvironnemental::significatifs()->get();
        $this->assertCount(1, $significatifs);
    }
}
