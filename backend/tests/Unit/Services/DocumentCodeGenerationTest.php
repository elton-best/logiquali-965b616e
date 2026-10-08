<?php

namespace Tests\Unit\Services;

use App\Models\CodeStructurePart;
use App\Models\DocumentTypeConfiguration;
use App\Models\Enterprise;
use App\Models\Site;
use App\Services\DocumentCodeGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCodeGenerationTest extends TestCase
{
    use RefreshDatabase;

    private DocumentCodeGenerationService $service;
    private Enterprise $enterprise;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DocumentCodeGenerationService::class);
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
        ]);
    }

    public function test_it_returns_fallback_when_no_configuration_exists(): void
    {
        $result = $this->service->generateForSiteAndType($this->site->id, 'PROC');

        $this->assertTrue($result['is_fallback']);
        $this->assertSame('[NON-CONFIGURÉ-PROC]', $result['code']);
        $this->assertNull($result['document_type_configuration_id']);
    }

    public function test_it_generates_code_from_active_configuration(): void
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'abbreviation' => 'PROC',
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'is_active' => true,
        ]);

        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 1,
            'part_name' => 'Type',
            'part_type' => 'fixed_abbreviation',
            'default_value' => 'PROC',
            'separator_after' => '-',
        ]);

        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 2,
            'part_name' => 'Sequence',
            'part_type' => 'sequence',
            'part_length' => 3,
            'is_required' => true,
            'sequence_scope' => 'global',
        ]);

        $result = $this->service->generateForSiteAndType($this->site->id, 'PROC');

        $this->assertFalse($result['is_fallback']);
        $this->assertSame($config->id, $result['document_type_configuration_id']);
        $this->assertStringStartsWith('PROC-', $result['code']);
        $this->assertMatchesRegularExpression('/PROC-\d{3}/', $result['code']);
    }
}
