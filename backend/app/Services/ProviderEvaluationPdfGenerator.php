<?php

namespace App\Services;

use App\Models\Enterprise;
use Barryvdh\DomPDF\Facade\Pdf;

class ProviderEvaluationPdfGenerator
{
    /**
     * Generate Provider/External Partner Evaluation PDF (RGPD compliant)
     */
    public function generate(array $providerData, bool $anonymize = false, ?Enterprise $enterprise = null): string
    {
        $branding = $enterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'provider_evaluation')
            : null;

        $data = [
            'provider' => $providerData,
            'anonymize' => $anonymize,
            'generated_at' => now()->format('d/m/Y H:i'),
            'branding' => $branding,
        ];

        $pdf = Pdf::loadView('pdf.provider-evaluation', $data);
        
        $fileName = $anonymize 
            ? 'evaluation_prestataire_anonyme_' . uniqid() . '.pdf'
            : 'evaluation_prestataire_' . ($providerData['name'] ?? 'N-A') . '.pdf';

        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        $pdf->save($filePath);

        return $filePath;
    }

    /**
     * Generate PDF and return download response
     */
    public function download(array $providerData, bool $anonymize = false, ?Enterprise $enterprise = null)
    {
        $branding = $enterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'provider_evaluation')
            : null;

        $data = [
            'provider' => $providerData,
            'anonymize' => $anonymize,
            'generated_at' => now()->format('d/m/Y H:i'),
            'branding' => $branding,
        ];

        $pdf = Pdf::loadView('pdf.provider-evaluation', $data);
        
        $fileName = $anonymize 
            ? 'evaluation_prestataire_anonyme_' . uniqid() . '.pdf'
            : 'evaluation_prestataire_' . ($providerData['name'] ?? 'N-A') . '.pdf';

        return $pdf->download($fileName);
    }
}
