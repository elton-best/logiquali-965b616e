<?php

namespace App\Services;

use App\Models\EmployeeEvaluation;
use App\Models\Enterprise;
use Barryvdh\DomPDF\Facade\Pdf;

class EmployeeEvaluationPdfGenerator
{
    /**
     * Generate Employee Evaluation PDF (RGPD compliant with anonymization option)
     */
    public function generate(EmployeeEvaluation $evaluation, bool $anonymize = false, ?Enterprise $enterprise = null): string
    {
        $resolvedEnterprise = $enterprise ?? $evaluation->site?->enterprise;
        $branding = $resolvedEnterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($resolvedEnterprise, 'employee_evaluation', $evaluation->id)
            : null;

        $data = [
            'evaluation' => $evaluation,
            'anonymize' => $anonymize,
            'generated_at' => now()->format('d/m/Y H:i'),
            'branding' => $branding,
        ];

        $pdf = Pdf::loadView('pdf.employee-evaluation', $data);
        
        $fileName = $anonymize 
            ? 'evaluation_employe_anonyme_' . $evaluation->id . '.pdf'
            : 'evaluation_employe_' . ($evaluation->employee->name ?? 'N-A') . '.pdf';

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
    public function download(EmployeeEvaluation $evaluation, bool $anonymize = false, ?Enterprise $enterprise = null)
    {
        $resolvedEnterprise = $enterprise ?? $evaluation->site?->enterprise;
        $branding = $resolvedEnterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($resolvedEnterprise, 'employee_evaluation', $evaluation->id)
            : null;

        $data = [
            'evaluation' => $evaluation,
            'anonymize' => $anonymize,
            'generated_at' => now()->format('d/m/Y H:i'),
            'branding' => $branding,
        ];

        $pdf = Pdf::loadView('pdf.employee-evaluation', $data);
        
        $fileName = $anonymize 
            ? 'evaluation_employe_anonyme_' . $evaluation->id . '.pdf'
            : 'evaluation_employe_' . ($evaluation->employee->name ?? 'N-A') . '.pdf';

        return $pdf->download($fileName);
    }
}
