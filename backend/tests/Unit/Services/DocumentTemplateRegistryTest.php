<?php

namespace Tests\Unit\Services;

use App\Services\DocumentTemplateRegistry;
use Tests\TestCase;

class DocumentTemplateRegistryTest extends TestCase
{
    public function test_resolves_layout_by_document_type(): void
    {
        $registry = app(DocumentTemplateRegistry::class);

        $resolved = $registry->resolve('qhse_policy');

        $this->assertSame('minimal', $resolved['layout']);
        $this->assertSame('pdf.layouts.minimal', $resolved['pdf_view']);
        $this->assertSame('pdf.layouts.preview.minimal', $resolved['preview_view']);
    }

    public function test_falls_back_to_default_layout_for_unknown_type(): void
    {
        $registry = app(DocumentTemplateRegistry::class);

        $resolved = $registry->resolve('unknown_custom_type');

        $this->assertSame('professional', $resolved['layout']);
        $this->assertSame('pdf.layouts.professional', $resolved['pdf_view']);
        $this->assertSame('pdf.layouts.preview.professional', $resolved['preview_view']);
    }

    public function test_rejects_invalid_requested_layout(): void
    {
        $registry = app(DocumentTemplateRegistry::class);

        $resolved = $registry->resolve('process', 'invalid-layout');

        $this->assertSame('professional', $resolved['layout']);
    }
}

