<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\DocumentTypeConfigurationService;
use App\Models\DocumentTypeConfiguration;
use App\Models\CodeStructurePart;
use App\Models\User;
use App\Models\Enterprise;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DocumentTypeConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private DocumentTypeConfigurationService $service;
    private User $user;
    private Enterprise $enterprise;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DocumentTypeConfigurationService::class);
        
        $this->enterprise = Enterprise::factory()->create();
        $this->user = User::factory()->create(['enterprise_id' => $this->enterprise->id]);
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_create_configuration()
    {
        $data = [
            'abbreviation' => 'DOC',
            'name' => 'Document Test',
            'abbreviation_length' => 3,
            'scope' => 'site',
            'is_active' => true,
            'enterprise_id' => $this->enterprise->id,
        ];

        $config = $this->service->createConfiguration($data);

        $this->assertInstanceOf(DocumentTypeConfiguration::class, $config);
        $this->assertEquals('DOC', $config->abbreviation);
        $this->assertEquals('Document Test', $config->name);
        $this->assertDatabaseHas('document_type_configurations', [
            'abbreviation' => 'DOC',
            'name' => 'Document Test'
        ]);
    }

    /** @test */
    public function it_can_update_configuration()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'name' => 'Original Label',
            'enterprise_id' => $this->enterprise->id
        ]);

        $updated = $this->service->updateConfiguration($config->id, [
            'name' => 'Updated Label'
        ]);

        $this->assertEquals('Updated Label', $updated->name);
        $this->assertDatabaseHas('document_type_configurations', [
            'id' => $config->id,
            'name' => 'Updated Label'
        ]);
    }

    /** @test */
    public function it_can_delete_configuration()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'enterprise_id' => $this->enterprise->id
        ]);
        $configId = $config->id;

        $this->service->deleteConfiguration($configId);

        $this->assertSoftDeleted('document_type_configurations', ['id' => $configId]);
    }

    /** @test */
    public function it_validates_structure()
    {
        $structure = [
            ['part_type' => 'fixed_abbreviation', 'part_order' => 1, 'part_name' => 'Type', 'part_length' => 3],
            ['part_type' => 'sequence', 'part_order' => 2, 'part_name' => 'Sequence', 'part_length' => 4, 'sequence_scope' => 'global']
        ];

        $validation = $this->service->validateStructure($structure);

        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertTrue($validation['valid']);
    }

    /** @test */
    public function it_rejects_structure_when_sequence_is_not_last()
    {
        $structure = [
            ['part_type' => 'sequence', 'part_order' => 1, 'part_name' => 'Sequence', 'part_length' => 4, 'sequence_scope' => 'global'],
            ['part_type' => 'year', 'part_order' => 2, 'part_name' => 'Year', 'part_length' => 4],
        ];

        $validation = $this->service->validateStructure($structure);

        $this->assertFalse($validation['valid']);
        $this->assertTrue(collect($validation['errors'])->contains(fn ($e) => str_contains($e, 'dernière position')));
    }

    /** @test */
    public function it_generates_preview_code()
    {
        $config = DocumentTypeConfiguration::factory()->create([
            'abbreviation' => 'TEST',
            'enterprise_id' => $this->enterprise->id
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_type' => 'fixed_abbreviation',
            'part_name' => 'Type',
            'part_order' => 1,
            'part_length' => 4
        ]);
        
        CodeStructurePart::create([
            'document_type_configuration_id' => $config->id,
            'part_type' => 'sequence',
            'part_name' => 'Sequence',
            'part_order' => 2,
            'part_length' => 4,
            'sequence_scope' => 'global'
        ]);

        $preview = $this->service->previewCode($config->id, []);

        $this->assertIsString($preview);
        $this->assertStringContainsString('TEST', $preview);
    }

    /** @test */
    public function it_can_duplicate_configuration()
    {
        $original = DocumentTypeConfiguration::factory()->create([
            'abbreviation' => 'ORIG',
            'name' => 'Original',
            'enterprise_id' => $this->enterprise->id
        ]);

        CodeStructurePart::create([
            'document_type_configuration_id' => $original->id,
            'part_type' => 'fixed_abbreviation',
            'part_name' => 'Type',
            'part_order' => 1,
            'part_length' => 4
        ]);

        $duplicate = $this->service->duplicateConfiguration($original->id, [
            'abbreviation' => 'DUP',
            'name' => 'Duplicate'
        ]);

        $this->assertNotEquals($original->id, $duplicate->id);
        $this->assertEquals('DUP', $duplicate->abbreviation);
        $this->assertEquals('Duplicate', $duplicate->name);
        $this->assertCount(1, $duplicate->structureParts);
    }
}
