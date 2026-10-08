<?php

namespace App\Services;

use App\Models\Enterprise;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfGeneratorService
{
    public function __construct(
        private QRCodeService $qrCodeService,
        private DocumentBrandingService $brandingService,
        private DocumentTemplateRegistry $templateRegistry
    ) {}

    public function generateDocument(
        Enterprise $enterprise,
        string $documentType,
        array $content,
        array $options = []
    ): string {
        $data = $this->buildViewData($enterprise, $documentType, $content, $options);
        $pdf = Pdf::loadView($data['template']['pdf_view'], $data);
        
        return $pdf->output();
    }

    public function previewDocument(
        Enterprise $enterprise,
        string $documentType,
        array $content,
        array $options = []
    ): string {
        $data = $this->buildViewData($enterprise, $documentType, $content, $options);
        return view($data['template']['preview_view'], $data)->render();
    }

    private function getDefaultLayout(Enterprise $enterprise, string $documentType): string
    {
        $config = $enterprise->header_footer_config;
        $resolved = $this->templateRegistry->resolve($documentType);
        $allowedLayouts = config('documents.template_registry.allowed_layouts', ['professional', 'minimal']);
        
        if (isset($config['document_types'][$documentType]['header_style'])) {
            $documentLayout = $config['document_types'][$documentType]['header_style'];
            if (in_array($documentLayout, $allowedLayouts, true)) {
                return $documentLayout;
            }
        }

        $globalLayout = $config['header']['layout'] ?? $resolved['layout'];
        return in_array($globalLayout, $allowedLayouts, true) ? $globalLayout : $resolved['layout'];
    }

    private function buildFooter(
        Enterprise $enterprise,
        string $documentType,
        ?int $documentId,
        array $baseFooter,
        array $content
    ): array
    {
        $footer = array_merge($baseFooter, [
            'show_social' => ($enterprise->header_footer_config['footer']['show_social'] ?? false),
            'social' => $enterprise->social_media,
        ]);

        if ($footer['show_qr_code'] && $documentId) {
            $hash = $this->qrCodeService->generate($documentType, $documentId, [
                'document_title' => (string) ($content['title'] ?? ('Document ' . $documentId)),
                'enterprise_name' => (string) ($enterprise->name ?? config('app.name')),
            ]);
            $qrSvg = $this->generateQrCodeSvg($hash);
            if (!empty($qrSvg)) {
                $footer['qr_code'] = $qrSvg;
            }
        }

        return $footer;
    }

    private function getStyles(Enterprise $enterprise, string $layout, array $baseStyles): array
    {
        return array_merge($baseStyles, [
            'layout' => $layout,
        ]);
    }

    private function generateQrCodeSvg(string $hash): ?string
    {
        $qrFacadeClass = \SimpleSoftwareIO\QrCode\Facades\QrCode::class;
        if (!class_exists($qrFacadeClass)) {
            return null;
        }

        $url = $this->qrCodeService->getVerificationUrl($hash);

        try {
            return $qrFacadeClass::size(100)->generate($url);
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildViewData(
        Enterprise $enterprise,
        string $documentType,
        array $content,
        array $options = []
    ): array {
        $layout = $options['layout'] ?? $this->getDefaultLayout($enterprise, $documentType);
        $template = $this->templateRegistry->resolve($documentType, $layout);

        $branding = $this->brandingService->getPdfBranding(
            $enterprise,
            $documentType,
            $content['document_id'] ?? null,
            $content
        );

        return [
            'layout' => $layout,
            'template' => $template,
            'enterprise' => $enterprise,
            'content' => $content,
            'isDraft' => $options['is_draft'] ?? false,
            'isExpired' => $options['is_expired'] ?? false,
            'header' => array_merge($branding['header'], ['layout' => $layout]),
            'footer' => $this->buildFooter($enterprise, $documentType, $content['document_id'] ?? null, $branding['footer'], $content),
            'styles' => $this->getStyles($enterprise, $layout, $branding['styles']),
        ];
    }
}
