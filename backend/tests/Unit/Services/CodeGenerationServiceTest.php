<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CodeGenerationService;
use App\Models\DocumentTypeConfiguration;
use App\Models\CodeStructurePart;
use App\Models\CodeSequence;
use App\Models\Enterprise;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CodeGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private CodeGenerationService $service;
    private Enterprise $enterprise;
    private $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CodeGenerationService::class);
        $this->enterprise = Enterprise::factory()->create();
        $this->site = \App\Models\Site::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
    }

    /** @test */
    public function it_generates_code_with_sequence()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'abbreviation' => 'TEST',
            'enterprise_id' => $this->enterprise->id
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 1,
            'part_name' => 'Type',
            'part_type' => 'fixed_abbreviation',
            'default_value' => 'TEST'
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 2,
            'part_name' => 'Sequence',
            'part_type' => 'sequence',
            'part_length' => 4,
            'is_required' => true,
            'sequence_scope' => 'global'
        ]);

        $result = $this->service->generateCode($config->id, [
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('code', $result);
        $this->assertStringStartsWith('TEST', $result['code']);
        $this->assertMatchesRegularExpression('/TEST\d{4}/', $result['code']);
    }

    /** @test */
    public function it_generates_code_with_year()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'abbreviation' => 'DOC',
            'enterprise_id' => $this->enterprise->id
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 1,
            'part_name' => 'Type',
            'part_type' => 'fixed_abbreviation',
            'default_value' => 'DOC',
            'separator_after' => '-'
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 2,
            'part_name' => 'Year',
            'part_type' => 'year'
        ]);

        $result = $this->service->generateCode($config->id, [
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id
        ]);

        $this->assertStringContainsString('DOC-', $result['code']);
        $this->assertStringContainsString((string) date('Y'), $result['code']);
    }

    /** @test */
    public function it_recycles_released_codes()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 1,
            'part_name' => 'Sequence',
            'part_type' => 'sequence',
            'part_length' => 3,
            'is_required' => true,
            'sequence_scope' => 'global'
        ]);

        $sequence = CodeSequence::create([
            'document_type_configuration_id' => $config->id,
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'last_sequence_number' => 5,
            'available_numbers' => [2, 3]
        ]);

        $result1 = $this->service->generateCode($config->id, [
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id
        ]);
        $this->assertStringContainsString('002', $result1['code']);

        $result2 = $this->service->generateCode($config->id, [
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id
        ]);
        $this->assertStringContainsString('003', $result2['code']);

        $result3 = $this->service->generateCode($config->id, [
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id
        ]);
        $this->assertStringContainsString('006', $result3['code']);
    }

    /** @test */
    public function it_checks_code_availability()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $available = $this->service->isCodeAvailable('UNIQUE-001');
        
        $this->assertTrue($available);
    }

    /** @test */
    public function it_releases_code_for_recycling()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_order' => 1,
            'part_name' => 'Sequence',
            'part_type' => 'sequence',
            'part_length' => 3,
            'is_required' => true,
            'sequence_scope' => 'global'
        ]);

        $sequence = CodeSequence::create([
            'document_type_configuration_id' => $config->id,
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'last_sequence_number' => 5,
            'available_numbers' => []
        ]);

        $document = Document::factory()->create([
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id,
            'code' => '003',
            'code_status' => 'active',
            'document_type_configuration_id' => $config->id
        ]);

        $this->service->releaseCode('003', $config->id);

        $sequence->refresh();
        $this->assertContains(3, $sequence->available_numbers);
    }

    /** @test */
    public function it_reserves_code()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'abbreviation' => 'RES',
            'enterprise_id' => $this->enterprise->id
        ]);
        
        $document = Document::factory()->create([
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id,
            'code' => 'TEMP',
            'code_status' => 'reserved'
        ]);

        $result = $this->service->reserveCode('RES001', $document->id);

        $this->assertTrue($result);
        
        $document->refresh();
        $this->assertEquals('RES001', $document->code);
        $this->assertEquals('reserved', $document->code_status);
    }

    /** @test */
    public function it_activates_reserved_code()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $document = Document::factory()->create([
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id,
            'code' => 'TEST-001',
            'code_status' => 'reserved',
            'document_type_configuration_id' => $config->id
        ]);
        
        $result = $this->service->activateCode('TEST-001', $config->id);

        $this->assertTrue($result);
        
        $document->refresh();
        $this->assertEquals('active', $document->code_status);
    }

    /** @test */
    public function it_does_not_reserve_code_for_approved_document()
    {
        $document = Document::factory()->create([
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id,
            'code' => 'IMM-001',
            'code_status' => 'active',
            'workflow_status' => 'approved',
            'status' => 'approved',
        ]);

        $result = $this->service->reserveCode('IMM-999', $document->id);

        $this->assertFalse($result);
        $document->refresh();
        $this->assertEquals('IMM-001', $document->code);
    }

    /** @test */
    public function it_does_not_release_code_for_approved_document()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);

        $document = Document::factory()->create([
            'site_id' => $this->site->id,
            'enterprise_id' => $this->enterprise->id,
            'code' => 'IMM-REL-001',
            'code_status' => 'active',
            'document_type_configuration_id' => $config->id,
            'workflow_status' => 'approved',
            'status' => 'approved',
        ]);

        $result = $this->service->releaseCode('IMM-REL-001', $config->id);

        $this->assertFalse($result);
        $document->refresh();
        $this->assertEquals('active', $document->code_status);
    }
}
