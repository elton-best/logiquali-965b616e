<?php

namespace App\Modules\Leadership\Services;

use App\Models\Norm;
use App\Models\NormVersion;
use App\Models\NormSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
use InvalidArgumentException;

class NormImportService
{
    private const FORMAT_STRUCTURED = 'structured';
    private const FORMAT_CLAUSE = 'clause';

    /**
     * Import norm from Excel file
     * 
     * Expected Excel format:
     * | Niveau | Numéro | Type | Titre | Contenu | Références |
     */
    public function importFromExcel(
        string $filePath,
        array $normData,
        ?Norm $existingNorm = null,
        string $action = 'create' // 'create', 'replace', 'merge'
    ): Norm {
        DB::beginTransaction();

        try {
            // Load Excel file
            $spreadsheet = IOFactory::load($filePath);
            [$rows, $headerContext] = $this->resolveSheetRows($spreadsheet);

            // Extract norm metadata from first data row or use provided data
            $normMetadata = $this->extractNormMetadata($rows, $normData);

            // Handle existing norm
            if ($existingNorm) {
                if ($action === 'replace') {
                    $norm = $this->replaceNorm($existingNorm, $normMetadata, $filePath, $rows, $headerContext);
                } elseif ($action === 'merge') {
                    $norm = $this->mergeNorm($existingNorm, $normMetadata, $filePath, $rows, $headerContext);
                } else {
                    throw new Exception("Norm with code {$existingNorm->code} already exists");
                }
            } else {
                $norm = $this->createNewNorm($normMetadata, $filePath, $rows, $headerContext);
            }

            DB::commit();

            return $norm;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Validate Excel headers
     */
    private function validateAndDetectHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $index => $header) {
            $key = $this->normalizeHeader((string) $header);
            if ($key !== '') {
                $normalized[$key] = (int) $index;
            }
        }

        $hasStructured = isset($normalized['niveau'])
            && isset($normalized['numero'])
            && isset($normalized['titre'])
            && isset($normalized['contenu']);

        if ($hasStructured) {
            return [
                'format' => self::FORMAT_STRUCTURED,
                'columns' => [
                    'level' => $normalized['niveau'],
                    'number' => $normalized['numero'],
                    'type' => $normalized['type'] ?? null,
                    'title' => $normalized['titre'],
                    'content' => $normalized['contenu'],
                    'references' => $normalized['references'] ?? null,
                ],
            ];
        }

        $hasClause = isset($normalized['clausenumero'])
            && isset($normalized['titredelaclause'])
            && isset($normalized['contenudelaclausetextedelanorme']);

        if ($hasClause) {
            return [
                'format' => self::FORMAT_CLAUSE,
                'columns' => [
                    'number' => $normalized['clausenumero'],
                    'title' => $normalized['titredelaclause'],
                    'content' => $normalized['contenudelaclausetextedelanorme'],
                ],
            ];
        }

        throw new InvalidArgumentException(
            "Format Excel non reconnu. Colonnes attendues: 'Niveau/Numéro/Type/Titre/Contenu' ou 'Clause Numéro/Titre de la Clause/Contenu de la Clause (Texte de la Norme)'."
        );
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(mb_strtolower($header));
        $header = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ä', 'ù', 'û', 'ü', 'ô', 'ö', 'î', 'ï', 'ç', 'œ'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'a', 'u', 'u', 'u', 'o', 'o', 'i', 'i', 'c', 'oe'],
            $header
        );
        return preg_replace('/[^a-z0-9]/', '', $header) ?? '';
    }

    /**
     * Extract norm metadata
     */
    private function extractNormMetadata(array $rows, array $providedData): array
    {
        return [
            'code' => $providedData['code'] ?? 'ISO-' . time(),
            'name' => $providedData['name'] ?? 'Norme importée',
            'description' => $providedData['description'] ?? null,
            'domain' => $providedData['domain'] ?? 'quality',
            'version_code' => $providedData['version_code'] ?? date('Y'),
        ];
    }

    /**
     * Create new norm with version and sections
     */
    private function createNewNorm(array $metadata, string $filePath, array $rows, array $headerContext): Norm
    {
        // Create Norm
        $norm = Norm::create([
            'code' => $metadata['code'],
            'name' => $metadata['name'],
            'description' => $metadata['description'],
            'domain' => $metadata['domain'],
            'status' => 'draft',
        ]);

        // Create Version
        $version = $this->createVersion($norm, $metadata['version_code'], $filePath);

        // Import sections
        $importedCount = $this->importSections($version, $rows, $headerContext);
        $this->assertSectionsImported($importedCount);

        // Set as current version
        $norm->setCurrentVersion($version);

        return $norm->fresh(['currentVersion', 'versions']);
    }

    /**
     * Replace existing norm with new version
     */
    private function replaceNorm(Norm $norm, array $metadata, string $filePath, array $rows, array $headerContext): Norm
    {
        // Archive current version if exists
        if ($norm->currentVersion) {
            $norm->currentVersion->archive();
        }

        // Create new version
        $version = $this->createVersion($norm, $metadata['version_code'], $filePath);

        // Import sections
        $importedCount = $this->importSections($version, $rows, $headerContext);
        $this->assertSectionsImported($importedCount);

        // Set as current version
        $norm->setCurrentVersion($version);

        // Update norm metadata
        $norm->update([
            'name' => $metadata['name'],
            'description' => $metadata['description'],
            'domain' => $metadata['domain'],
        ]);

        return $norm->fresh(['currentVersion', 'versions']);
    }

    /**
     * Merge sections into existing norm version
     */
    private function mergeNorm(Norm $norm, array $metadata, string $filePath, array $rows, array $headerContext): Norm
    {
        $version = $norm->currentVersion;

        if (!$version) {
            // If no current version, create one
            $version = $this->createVersion($norm, $metadata['version_code'], $filePath);
            $norm->setCurrentVersion($version);
        }

        // Import and merge sections
        $importedCount = $this->importSections($version, $rows, $headerContext, true);
        $this->assertSectionsImported($importedCount);

        return $norm->fresh(['currentVersion', 'versions']);
    }

    /**
     * Create norm version
     */
    private function createVersion(Norm $norm, string $versionCode, string $filePath): NormVersion
    {
        return NormVersion::create([
            'norm_id' => $norm->id,
            'version_code' => $versionCode,
            'full_code' => $this->buildFullCode($norm->code, $versionCode),
            'published_at' => now(),
            'excel_file_path' => $filePath,
            'is_current' => false,
        ]);
    }

    /**
     * Build normalized full code without duplicating version suffix.
     */
    private function buildFullCode(string $normCode, string $versionCode): string
    {
        $trimmedCode = trim($normCode);

        if (preg_match('/:\d{4}$/', $trimmedCode)) {
            return preg_replace('/:\d{4}$/', ':' . $versionCode, $trimmedCode);
        }

        return $trimmedCode . ':' . $versionCode;
    }

    /**
     * Import sections from Excel rows
     */
    private function importSections(NormVersion $version, array $rows, array $headerContext, bool $merge = false): int
    {
        // Skip header row
        $dataRows = array_slice($rows, 1);

        $parentStack = []; // Track parent sections by level
        $orderIndex = 0;
        $lastSection = null;
        $touchedCount = 0;

        foreach ($dataRows as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $sectionData = $this->parseRow($row, $headerContext);

            if (!$sectionData) {
                continue;
            }

            if (($sectionData['_mode'] ?? null) === 'append') {
                if ($lastSection && !empty($sectionData['content'])) {
                    $existingContent = (string) ($lastSection->content ?? '');
                    $lastSection->update([
                        'content' => trim($existingContent . "\n" . $sectionData['content']),
                    ]);
                    $touchedCount++;
                }
                continue;
            }

            // Determine parent based on level
            $parent = null;
            $level = (int) $sectionData['level'];

            if ($level > 1 && isset($parentStack[$level - 1])) {
                $parent = $parentStack[$level - 1];
            }

            // Build path
            $path = NormSection::buildPath($parent, $sectionData['number']);

            // Check if section exists (for merge mode)
            if ($merge) {
                $existing = $version->sections()
                    ->where('path', $path)
                    ->first();

                if ($existing) {
                    // Update existing section
                    $existing->update([
                        'title' => $sectionData['title'],
                        'content' => $sectionData['content'],
                        'references' => $sectionData['references'] ?? null,
                    ]);
                    $parentStack[$level] = $existing;
                    $touchedCount++;
                    continue;
                }
            }

            // Create new section
            $section = NormSection::create([
                'norm_version_id' => $version->id,
                'parent_id' => $parent?->id,
                'path' => $path,
                'level' => $level,
                'type' => $sectionData['type'],
                'number' => $sectionData['number'],
                'title' => $sectionData['title'],
                'content' => $sectionData['content'],
                'order_index' => $orderIndex++,
                'references' => $sectionData['references'] ?? null,
            ]);
            $touchedCount++;

            // Update parent stack
            $parentStack[$level] = $section;
            $lastSection = $section;

            // Clear deeper levels
            for ($i = $level + 1; $i <= 10; $i++) {
                unset($parentStack[$i]);
            }
        }
        return $touchedCount;
    }

    /**
     * Parse Excel row into section data
     */
    private function parseRow(array $row, array $headerContext): ?array
    {
        $format = $headerContext['format'] ?? self::FORMAT_STRUCTURED;
        $columns = $headerContext['columns'] ?? [];

        if ($format === self::FORMAT_CLAUSE) {
            $numero = trim((string) ($row[$columns['number']] ?? ''));
            $titre = trim((string) ($row[$columns['title']] ?? ''));
            $contenu = trim((string) ($row[$columns['content']] ?? ''));

            if ($numero === '') {
                if ($contenu !== '') {
                    return [
                        '_mode' => 'append',
                        'content' => $contenu,
                    ];
                }
                return null;
            }

            $level = substr_count($numero, '.') + 1;
            $type = $level === 1 ? 'chapter' : ($level === 2 ? 'subchapter' : 'paragraph');

            return [
                'level' => $level,
                'number' => $numero,
                'type' => $type,
                'title' => $titre !== '' ? $titre : null,
                'content' => $contenu !== '' ? $contenu : null,
                'references' => null,
            ];
        }

        $niveau = trim((string) ($row[$columns['level']] ?? ''));
        $numero = trim((string) ($row[$columns['number']] ?? ''));
        $type = isset($columns['type']) ? ($row[$columns['type']] ?? null) : null;
        $titre = isset($columns['title']) ? ($row[$columns['title']] ?? null) : null;
        $contenu = isset($columns['content']) ? ($row[$columns['content']] ?? null) : null;
        $references = isset($columns['references']) ? ($row[$columns['references']] ?? null) : null;

        if ($niveau === '' || $numero === '') {
            return null;
        }

        return [
            'level' => (int) $niveau,
            'number' => trim($numero),
            'type' => $this->normalizeType($type),
            'title' => $titre ? trim($titre) : null,
            'content' => $contenu ? trim($contenu) : null,
            'references' => $references ? $this->parseReferences($references) : null,
        ];
    }

    /**
     * Normalize section type
     */
    private function normalizeType(?string $type): string
    {
        if (!$type) {
            return 'paragraph';
        }

        $type = strtolower(trim($type));

        $mapping = [
            'chapitre' => 'chapter',
            'chapter' => 'chapter',
            'sous-chapitre' => 'subchapter',
            'subchapter' => 'subchapter',
            'paragraphe' => 'paragraph',
            'paragraph' => 'paragraph',
            'exigence' => 'paragraph',
            'point' => 'point',
            'note' => 'note',
            'annexe' => 'annex',
            'annex' => 'annex',
        ];

        return $mapping[$type] ?? 'paragraph';
    }

    /**
     * Parse references string into array
     */
    private function parseReferences(?string $references): ?array
    {
        if (!$references) {
            return null;
        }

        // Split by comma or semicolon
        $refs = preg_split('/[,;]/', $references);
        $refs = array_map('trim', $refs);
        $refs = array_filter($refs);

        return empty($refs) ? null : array_values($refs);
    }

    private function resolveSheetRows(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet): array
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows = $sheet->toArray();
            if (empty($rows) || empty($rows[0])) {
                continue;
            }

            try {
                $headerContext = $this->validateAndDetectHeaders($rows[0]);
                return [$rows, $headerContext];
            } catch (InvalidArgumentException $e) {
                // Try next sheet
                continue;
            }
        }

        throw new InvalidArgumentException(
            "Format Excel non reconnu. Colonnes attendues: 'Niveau/Numéro/Type/Titre/Contenu' ou 'Clause Numéro/Titre de la Clause/Contenu de la Clause (Texte de la Norme)'."
        );
    }

    private function assertSectionsImported(int $count): void
    {
        if ($count <= 0) {
            throw new InvalidArgumentException("Aucune section n'a ete importee. Verifiez le template et la feuille selectionnee.");
        }
    }

    /**
     * Validate norm structure after import
     */
    public function validateStructure(NormVersion $version): array
    {
        $errors = [];

        // Check for duplicate paths
        $paths = $version->sections()->pluck('path')->toArray();
        $duplicates = array_diff_assoc($paths, array_unique($paths));

        if (!empty($duplicates)) {
            $errors[] = "Duplicate section paths found: " . implode(', ', array_unique($duplicates));
        }

        // Check for orphaned sections (parent_id references non-existent section)
        $orphans = $version->sections()
            ->whereNotNull('parent_id')
            ->whereDoesntHave('parent')
            ->get();

        if ($orphans->count() > 0) {
            $errors[] = "{$orphans->count()} orphaned sections found";
        }

        return $errors;
    }
}
