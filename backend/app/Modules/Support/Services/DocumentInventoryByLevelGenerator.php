<?php

namespace App\Modules\Support\Services;

use App\Models\Document;
use App\Models\Enterprise;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DocumentInventoryByLevelExport;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DocumentInventoryByLevelGenerator
{
    public function __construct(
        protected DocumentBrandingService $documentBrandingService
    ) {
    }

    /**
     * Generate Excel Inventory by Pyramide Level (N1-N5)
     * 
     * @param string $level N1 (Politique), N2 (Manuel), N3 (Procédures), N4 (Instructions), N5 (Enregistrements)
     * @param int|null $siteId Filter by site
     * @return string File path
     */
    public function generate(string $level, ?int $siteId = null, ?Enterprise $enterprise = null): string
    {
        // Validate level
        $validLevels = ['N1', 'N2', 'N3', 'N4', 'N5'];
        if (!in_array($level, $validLevels)) {
            throw new \InvalidArgumentException("Invalid level: $level. Must be N1, N2, N3, N4, or N5");
        }

        // Map level to document types
        $typeMapping = [
            'N1' => 'politique',        // Politique Qualité
            'N2' => 'manuel',           // Manuel Qualité
            'N3' => 'procedure',        // Procédures
            'N4' => 'instruction',      // Instructions
            'N5' => 'enregistrement',   // Enregistrements
        ];

        $documentType = $typeMapping[$level];

        // Build query
        $query = Document::with(['process', 'author', 'approver', 'category', 'site'])
            ->where('type', $documentType);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $documents = $query->orderBy('code')->get();

        // Generate file
        $fileName = 'inventaire_' . strtolower($level) . '_' . now()->format('Y-m-d_His') . '.xlsx';
        $filePath = storage_path('app/temp/' . $fileName);

        Excel::store(
            new DocumentInventoryByLevelExport($documents, $level),
            'temp/' . $fileName,
            'local'
        );

        $this->applyEnterpriseBranding($filePath, $enterprise, "Inventaire documentaire {$level}");

        return $filePath;
    }

    /**
     * Generate complete pyramid inventory (all levels in separate sheets)
     * 
     * @param int|null $siteId Filter by site
     * @return string File path
     */
    public function generateComplete(?int $siteId = null, ?Enterprise $enterprise = null): string
    {
        $fileName = 'inventaire_pyramide_complet_' . now()->format('Y-m-d_His') . '.xlsx';
        $filePath = storage_path('app/temp/' . $fileName);

        Excel::store(
            new DocumentInventoryCompletePyramidExport($siteId),
            'temp/' . $fileName,
            'local'
        );

        $this->applyEnterpriseBranding($filePath, $enterprise, 'Inventaire pyramide documentaire');

        return $filePath;
    }

    /**
     * Get document count by level
     * 
     * @param int|null $siteId
     * @return array
     */
    public function getStatsByLevel(?int $siteId = null): array
    {
        $query = Document::query();
        
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $typeMapping = [
            'N1' => 'politique',
            'N2' => 'manuel',
            'N3' => 'procedure',
            'N4' => 'instruction',
            'N5' => 'enregistrement',
        ];

        $stats = [];
        foreach ($typeMapping as $level => $type) {
            $stats[$level] = [
                'level' => $level,
                'type' => $type,
                'total' => (clone $query)->where('type', $type)->count(),
                'approved' => (clone $query)->where('type', $type)->where('status', 'approved')->count(),
                'draft' => (clone $query)->where('type', $type)->where('status', 'draft')->count(),
                'obsolete' => (clone $query)->where('type', $type)->where('status', 'obsolete')->count(),
            ];
        }

        return $stats;
    }

    protected function applyEnterpriseBranding(string $filePath, ?Enterprise $enterprise, string $title): void
    {
        $enterprise ??= auth()->user()?->enterprise;
        if (!$enterprise || !is_file($filePath)) {
            return;
        }

        $spreadsheet = IOFactory::load($filePath);
        $this->documentBrandingService->applyXlsxBranding($spreadsheet, $enterprise, $title);
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($filePath);
    }
}
