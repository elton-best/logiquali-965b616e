<?php

namespace App\Services;

class DocumentTemplateRegistry
{
    /**
     * @return array{
     *   document_type:string,
     *   layout:string,
     *   pdf_view:string,
     *   preview_view:string
     * }
     */
    public function resolve(string $documentType, ?string $requestedLayout = null): array
    {
        $config = config('documents.template_registry', []);
        $allowedLayouts = $config['allowed_layouts'] ?? ['professional', 'minimal'];
        $defaultLayout = (string) ($config['default']['layout'] ?? 'professional');
        $normalizedType = strtolower(trim($documentType));
        $typeConfig = $config['types'][$normalizedType] ?? [];

        $layout = $requestedLayout
            ?: (string) ($typeConfig['layout'] ?? $defaultLayout);

        if (!in_array($layout, $allowedLayouts, true)) {
            $layout = 'professional';
        }

        return [
            'document_type' => $normalizedType,
            'layout' => $layout,
            'pdf_view' => "pdf.layouts.{$layout}",
            'preview_view' => "pdf.layouts.preview.{$layout}",
        ];
    }
}

