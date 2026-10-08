<?php

namespace App\Modules\Support\Services;

use App\Models\Enterprise;
use App\Models\Document;
use App\Models\Process;
use App\Models\Site;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpWord\SimpleType\Jc;

class DocumentBrandingService
{
    public function applyXlsxBranding(
        Spreadsheet $spreadsheet,
        Enterprise $enterprise,
        ?string $documentTitle = null
    ): void {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $this->applyXlsxHeaderFooter($sheet, $enterprise, $documentTitle);
        }
    }

    public function applyXlsxHeaderFooter(
        Worksheet $sheet,
        Enterprise $enterprise,
        ?string $documentTitle = null,
        ?string $documentType = null
    ): void {
        $branding = $this->getPdfBranding($enterprise, $documentType);
        $headerFooter = $sheet->getHeaderFooter();

        $leftHeader = $this->escapeForExcelHeaderFooter($enterprise->name);
        $centerHeader = $documentTitle
            ? $this->escapeForExcelHeaderFooter($documentTitle)
            : '';
        $rightHeader = '';

        if (($branding['header']['show_certifications'] ?? false) && !empty($branding['header']['certifications'])) {
            $certification = $branding['header']['certifications'][0];
            $rightHeader = $this->escapeForExcelHeaderFooter((string) ($certification['name'] ?? ''));
        }

        $headerFooter->setOddHeader("&L{$leftHeader}&C{$centerHeader}&R{$rightHeader}");

        $footerText = $branding['footer']['custom_text'] ?? null;
        if (empty($footerText) && ($branding['footer']['show_contact'] ?? true)) {
            $contact = $branding['footer']['contact'] ?? [];
            $footerText = implode(' | ', array_filter([
                trim(($contact['address'] ?? '') . ' ' . ($contact['postal_code'] ?? '') . ' ' . ($contact['city'] ?? '')),
                $contact['phone_primary'] ?? null,
                $contact['email'] ?? null,
            ]));
        }

        $footerLeft = $this->escapeForExcelHeaderFooter((string) ($footerText ?: $enterprise->name));
        $footerRight = ($branding['footer']['show_page_numbers'] ?? true) ? 'Page &P/&N' : '';
        $headerFooter->setOddFooter("&L{$footerLeft}&R{$footerRight}");
    }

    public function getPdfBranding(
        Enterprise $enterprise,
        ?string $documentType = null,
        ?int $documentId = null,
        ?array $content = null
    ): array {
        $config = $enterprise->header_footer_config ?? [];
        $headerConfig = $config['header'] ?? [];
        $footerConfig = $config['footer'] ?? [];
        $documentConfig = $this->getDocumentTypeConfig($config, $documentType);

        $certifications = $enterprise->activeCertifications()
            ->with('certification')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->certification->name ?? null,
                'number' => $item->certification_number,
            ])
            ->filter(fn ($item) => !empty($item['name']))
            ->values()
            ->all();

        $contactAddress = trim(
            (($enterprise->address_line_1 ?: $enterprise->address) ?? '') . ' ' . ($enterprise->address_line_2 ?? '')
        );
        $contactPhone = $enterprise->phone_primary ?: $enterprise->phone;
        $contactEmail = $enterprise->email_general ?: $enterprise->email;

        $legalInfo = $enterprise->getLegalInfo();
        if (empty($legalInfo)) {
            $fallbackLegal = array_filter([
                'RCCM' => $enterprise->rccm_number ?: $enterprise->registration_number,
                'IFU' => $enterprise->ifu_number ?: null,
                'SIRET' => $enterprise->siret ?: null,
                'N TVA' => $enterprise->vat_number ?: null,
            ]);
            $legalInfo = $fallbackLegal;
        }

        $documentMeta = $this->buildDocumentMeta($enterprise, $documentType, $documentId, $content);
        $footerLines = $this->buildFooterLines($enterprise);

        $gdAvailable = extension_loaded('gd');
        $logoPath = $gdAvailable ? $this->getLogoAbsolutePath($enterprise) : null;

        return [
            'styles' => [
                'font' => $enterprise->header_font ?? 'Arial',
                'colors' => $enterprise->brand_colors ?? [
                    'primary' => '#1B5E96',
                    'secondary' => '#FF6B00',
                    'accent' => '#4CAF50',
                ],
            ],
            'header' => [
                // Dompdf requires GD for embedded image rendering.
                // If GD is unavailable, keep branding text but disable logo to avoid hard 500.
                'show_logo' => ($headerConfig['show_logo'] ?? true) && $gdAvailable && !empty($logoPath),
                'logo_path' => $logoPath,
                'enterprise_name' => $enterprise->name,
                'show_slogan' => $headerConfig['show_slogan'] ?? true,
                'slogan' => $enterprise->slogan,
                'show_certifications' => true,
                'layout' => $documentConfig['header_style'] ?? ($headerConfig['layout'] ?? 'professional'),
                'certifications' => $certifications,
                'document_meta' => $documentMeta,
            ],
            'footer' => [
                'show_contact' => true,
                'show_page_numbers' => true,
                'show_legal_info' => true,
                'show_qr_code' => true,
                'show_social' => $footerConfig['show_social'] ?? false,
                'custom_text' => $footerConfig['custom_text'] ?? null,
                'legal' => $this->formatLegalInfo($legalInfo),
                'social' => $enterprise->social_media,
                'contact' => [
                    'address' => $contactAddress,
                    'city' => $enterprise->city,
                    'postal_code' => $enterprise->postal_code,
                    'phone_primary' => $contactPhone,
                    'email' => $contactEmail,
                ],
                'identity_line' => $footerLines['identity_line'],
                'legal_line' => $footerLines['legal_line'],
                'contact_line' => $footerLines['contact_line'],
            ],
        ];
    }

    public function applyDocxHeaderFooter(
        $section,
        Enterprise $enterprise,
        ?string $documentType = null,
        ?int $documentId = null,
        ?array $content = null,
        $document = null
    ): void
    {
        $branding = $this->getPdfBranding($enterprise, $documentType, $documentId, $content);
        $meta = $branding['header']['document_meta'] ?? [];

        $header = $section->addHeader();
        $headerTable = $header->addTable([
            'borderSize' => 8,
            'borderColor' => 'A5B4C7',
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $headerTable->addRow();

        $leftCell = $headerTable->addCell(6800);
        $leftCell->addText((string) ($meta['document_label'] ?? 'FICHE'), ['bold' => true, 'size' => 10, 'color' => '2E3B55']);
        $leftCell->addText((string) ($meta['document_title'] ?? $enterprise->name), ['bold' => true, 'size' => 11, 'color' => '1E293B']);

        if (($branding['header']['show_slogan'] ?? false) && !empty($branding['header']['slogan'])) {
            $leftCell->addText((string) $branding['header']['slogan'], ['italic' => true, 'size' => 8, 'color' => '666666']);
        }

        $rightCell = $headerTable->addCell(3200);
        // FIX: Use current document code from Document model, not cached metadata
        $currentCode = $document?->code ?? $meta['code'] ?? '';
        $currentVersion = $document?->version ?? $meta['version'] ?? '';
        $rightCell->addText('Code : ' . (string) $currentCode, ['size' => 8]);
        $rightCell->addText('Version : ' . (string) $currentVersion, ['size' => 8]);
        $rightCell->addText('Date : ' . (string) ($meta['effective_date'] ?? ''), ['size' => 8]);

        $logoPath = $branding['header']['logo_path'] ?? null;
        if (($branding['header']['show_logo'] ?? false) && $logoPath && is_file($logoPath)) {
            $rightCell->addImage($logoPath, ['height' => 30, 'alignment' => Jc::END]);
        }

        $footer = $section->addFooter();
        $footerTable = $footer->addTable(['borderSize' => 0, 'width' => 100 * 50, 'unit' => 'pct']);
        $footerTable->addRow();
        $footerTop = $footerTable->addCell(10000);
        if (!empty($branding['footer']['identity_line'])) {
            $footerTop->addText((string) $branding['footer']['identity_line'], ['size' => 8, 'bold' => true, 'color' => '334155']);
        }
        if (($branding['footer']['show_legal_info'] ?? true) && !empty($branding['footer']['legal_line'])) {
            $footerTop->addText((string) $branding['footer']['legal_line'], ['size' => 8, 'color' => '475569']);
        }
        if (($branding['footer']['show_contact'] ?? true) && !empty($branding['footer']['contact_line'])) {
            $footerTop->addText((string) $branding['footer']['contact_line'], ['size' => 8, 'color' => '475569']);
        }

        if ($branding['footer']['show_page_numbers'] ?? true) {
            // FIX: Add single footer cell for page numbers (prevent duplication on page 1)
            $footerPage = $footerTable->addCell(10000);
            $footerPage->addPreserveText('Page {PAGE}', ['size' => 8], ['alignment' => Jc::END]);
        }
    }

    public function getLogoAbsolutePath(Enterprise $enterprise): ?string
    {
        if (empty($enterprise->logo_path)) {
            return null;
        }

        $normalizedPath = preg_replace('#^/?storage/#', '', (string) $enterprise->logo_path) ?? (string) $enterprise->logo_path;
        $path = Storage::disk('public')->path($normalizedPath);
        return is_file($path) ? $path : null;
    }

    public function formatLegalInfo(array $legalInfo): ?string
    {
        if (empty($legalInfo)) {
            return null;
        }

        return collect($legalInfo)
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode(' | ');
    }

    private function escapeForExcelHeaderFooter(string $value): string
    {
        return str_replace('&', '&&', $value);
    }

    private function getDocumentTypeConfig(array $config, ?string $documentType): array
    {
        if (!$documentType) {
            return [];
        }

        return $config['document_types'][$documentType] ?? [];
    }

    private function buildDocumentMeta(
        Enterprise $enterprise,
        ?string $documentType,
        ?int $documentId,
        ?array $content = null
    ): array {
        $title = trim((string) ($content['title'] ?? ''));
        $label = 'FICHE';
        $resolvedTitle = $title !== '' ? $title : $this->resolveDocumentTitle($documentType);
        $resolvedVersion = trim((string) ($content['version'] ?? ''));
        $resolvedEffectiveDate = trim((string) ($content['effective_date'] ?? ''));
        $resolvedCode = trim((string) ($content['code'] ?? ''));

        return [
            'document_label' => $label,
            'document_title' => $resolvedTitle,
            'code' => $resolvedCode !== ''
                ? $resolvedCode
                : $this->resolveDocumentCode($enterprise, $documentType, $documentId),
            'version' => $resolvedVersion !== '' ? $resolvedVersion : '1.0',
            'effective_date' => $resolvedEffectiveDate !== ''
                ? $resolvedEffectiveDate
                : now()->format('d/m/Y'),
        ];
    }

    private function resolveDocumentTitle(?string $documentType): string
    {
        $map = [
            'qhse_policy' => 'PROCESSUS SOLUTIONS NUMERIQUES',
            'process' => 'FICHE PROCESSUS',
            'risk' => 'FICHE RISQUES',
            'audit' => 'RAPPORT D AUDIT',
            'non_conformity' => 'FICHE NON CONFORMITE',
        ];

        if ($documentType && isset($map[$documentType])) {
            return $map[$documentType];
        }

        return 'DOCUMENT INTERNE';
    }

    private function resolveDocumentCode(Enterprise $enterprise, ?string $documentType, ?int $documentId): string
    {
        $existingCode = $this->resolveCodeFromInventory($enterprise, $documentType, $documentId);
        if ($existingCode) {
            return $existingCode;
        }

        [$type, $processCode] = $this->resolveTypeAndProcessCode($enterprise, $documentType, $documentId);
        $numero = max(1, (int) ($documentId ?? 1));
        $numeroStr = str_pad((string) $numero, 3, '0', STR_PAD_LEFT);

        return "{$type}/{$processCode}/{$numeroStr}";
    }

    private function resolveCodeFromInventory(Enterprise $enterprise, ?string $documentType, ?int $documentId): ?string
    {
        if (!$documentType || !$documentId) {
            return null;
        }

        $normalizedType = strtolower(trim($documentType));

        $siteIds = Site::query()
            ->where('enterprise_id', (int) $enterprise->id)
            ->pluck('id')
            ->all();
        if (empty($siteIds)) {
            return null;
        }

        $query = Document::query()
            ->whereIn('site_id', $siteIds)
            ->whereNotNull('code');

        if ($normalizedType === 'qhse_policy') {
            $query->where('metadata->source', 'qhse_policy')
                ->where('metadata->source_id', $documentId);
        } elseif (in_array($normalizedType, ['process', 'risk', 'audit_report', 'non_conformity', 'job_description', 'client_satisfaction_form', 'employee_evaluation', 'provider_evaluation'], true)) {
            $query->where('metadata->source', 'generated_process_document')
                ->where(function ($q) use ($normalizedType, $documentId): void {
                    $kindMap = [
                        'process' => 'process_sheet',
                        'risk' => 'risk_plan',
                        'audit_report' => 'audit_report',
                        'non_conformity' => 'nonconformity_procedure',
                        'job_description' => 'job_description_pdf',
                        'client_satisfaction_form' => 'client_satisfaction_form_pdf',
                        'employee_evaluation' => 'employee_evaluation_pdf',
                        'provider_evaluation' => 'provider_evaluation_pdf',
                    ];
                    $kind = $kindMap[$normalizedType] ?? null;
                    if ($kind) {
                        $q->where('metadata->document_kind', $kind);
                    }
                    $q->orWhere('metadata->source_id', $documentId);
                });
        } else {
            return null;
        }

        return $query
            ->orderByDesc('id')
            ->value('code');
    }

    private function resolveTypeAndProcessCode(Enterprise $enterprise, ?string $documentType, ?int $documentId): array
    {
        $normalizedType = strtolower(trim((string) $documentType));

        return match ($normalizedType) {
            'qhse_policy' => ['POL', 'DIR-1'],
            'process' => ['PRD', $this->resolveProcessCodeFromProcessId($documentId) ?? 'GEN'],
            'risk' => ['PRD', $this->resolveProcessCodeFromProcessId($documentId) ?? 'GEN'],
            'audit_report' => ['ENR', 'DIR-1'],
            'non_conformity' => ['ENR', 'DIR-1'],
            'job_description' => ['ENR', 'DIR-1'],
            'client_satisfaction_form' => ['ENR', 'DIR-1'],
            'employee_evaluation' => ['ENR', 'DIR-1'],
            'provider_evaluation' => ['ENR', 'DIR-1'],
            default => ['ENR', $this->resolveDefaultEnterpriseProcessCode($enterprise) ?? 'GEN'],
        };
    }

    private function resolveProcessCodeFromProcessId(?int $processId): ?string
    {
        if (!$processId || $processId <= 0) {
            return null;
        }

        return Process::query()
            ->where('id', $processId)
            ->value('code');
    }

    private function resolveDefaultEnterpriseProcessCode(Enterprise $enterprise): ?string
    {
        return Process::query()
            ->where('enterprise_id', (int) $enterprise->id)
            ->orderByRaw("CASE WHEN code = 'DIR-1' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->value('code');
    }

    private function buildFooterLines(Enterprise $enterprise): array
    {
        $identityLine = $enterprise->name;
        if (!empty($enterprise->slogan)) {
            $identityLine .= ', ' . $enterprise->slogan;
        }

        $legalChunks = array_filter([
            $enterprise->rccm_number ? 'RCCM: ' . $enterprise->rccm_number : null,
            $enterprise->ifu_number ? 'IFU: ' . $enterprise->ifu_number : null,
            $enterprise->cnss_number ? 'CNSS: ' . $enterprise->cnss_number : null,
            $enterprise->fodefca_number ? 'FODEFCA: ' . $enterprise->fodefca_number : null,
        ]);

        $contactChunks = array_filter([
            trim(
                (($enterprise->address_line_1 ?: $enterprise->address) ?? '') . ' ' .
                ($enterprise->address_line_2 ?? '') . ' ' .
                ($enterprise->postal_code ?? '') . ' ' .
                ($enterprise->city ?? '')
            ),
            $enterprise->phone_primary ?: $enterprise->phone,
            $enterprise->phone_secondary,
            $enterprise->email_general ?: $enterprise->email,
            $enterprise->website ? 'Web: ' . $enterprise->website : null,
        ]);

        $legalLine = implode(' - ', $legalChunks);
        if ($legalLine === '') {
            $legalLine = 'Informations légales: non renseignées';
        }

        $contactLine = implode(' | ', $contactChunks);
        if ($contactLine === '') {
            $contactLine = 'Contact: non renseigné';
        }

        return [
            'identity_line' => trim($identityLine) ?: 'Entreprise',
            'legal_line' => $legalLine,
            'contact_line' => $contactLine,
        ];
    }
}
