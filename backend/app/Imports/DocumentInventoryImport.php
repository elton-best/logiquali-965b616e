<?php

namespace App\Imports;

use App\Models\Document;
use App\Models\Site;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Import Document Inventory from XLSX file
 * 
 * Format attendu (basé sur M5-D2-INVENTAIRE DOCUMENTAIRE.xlsx):
 * - Sheet 1: "Cartouche & légende" (skip)
 * - Sheet 2: "Inventaire" avec colonnes:
 *   N°, PRC (Processus), Type, Nom, Code, Version, État, Observation
 */
class DocumentInventoryImport
{
    protected array $errors = [];
    protected array $imported = [];
    protected int $currentRow = 0;
    protected ?int $siteId = null;
    
    /**
     * Import XLSX file
     */
    public function import(string $filePath, ?int $siteId = null): array
    {
        $this->siteId = $siteId;
        $this->errors = [];
        $this->imported = [];
        
        try {
            $spreadsheet = IOFactory::load($filePath);
            
            // Skip first sheet (Cartouche), use second sheet (Inventaire)
            if ($spreadsheet->getSheetCount() < 2) {
                throw new \Exception('Le fichier doit contenir au moins 2 feuilles: Cartouche et Inventaire');
            }
            
            $sheet = $spreadsheet->getSheet(1); // Index 1 = 2ème feuille
            
            $this->processSheet($sheet);
            
        } catch (\Exception $e) {
            $this->errors[] = [
                'row' => 0,
                'error' => 'Erreur lecture fichier: ' . $e->getMessage()
            ];
        }
        
        return [
            'success' => count($this->imported),
            'errors' => count($this->errors),
            'imported' => $this->imported,
            'error_details' => $this->errors,
        ];
    }
    
    /**
     * Process inventory sheet
     */
    protected function processSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        
        // Skip header row (row 1)
        for ($row = 2; $row <= $highestRow; $row++) {
            $this->currentRow = $row;
            
            $rowData = [
                'numero' => $sheet->getCell('A' . $row)->getValue(),
                'processus' => $sheet->getCell('B' . $row)->getValue(),
                'type' => $sheet->getCell('C' . $row)->getValue(),
                'nom' => $sheet->getCell('D' . $row)->getValue(),
                'code' => $sheet->getCell('E' . $row)->getValue(),
                'version' => $sheet->getCell('F' . $row)->getValue(),
                'etat' => $sheet->getCell('G' . $row)->getValue(),
                'observation' => $sheet->getCell('H' . $row)->getValue(),
            ];
            
            // Skip empty rows
            if (empty($rowData['nom']) && empty($rowData['code'])) {
                continue;
            }
            
            $this->processRow($rowData);
        }
    }
    
    /**
     * Process single row
     */
    protected function processRow(array $rowData): void
    {
        // Validation
        $validator = Validator::make($rowData, [
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:documents,code',
            'type' => 'nullable|string|in:politique,manuel,procedure,instruction,enregistrement,formulaire',
            'version' => 'nullable|string|max:20',
            'etat' => 'nullable|string|in:brouillon,en_revue,valide,obsolete,archive',
        ]);
        
        if ($validator->fails()) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'data' => $rowData,
                'errors' => $validator->errors()->all(),
            ];
            return;
        }
        
        try {
            // Map type
            $type = $this->mapType($rowData['type']);
            
            // Map status
            $status = $this->mapStatus($rowData['etat']);
            
            // Create document
            $document = Document::create([
                'site_id' => $this->siteId,
                'nom' => $rowData['nom'],
                'code' => $rowData['code'] ?: $this->generateCode($type),
                'type' => $type,
                'niveau' => $this->detectNiveau($type),
                'version' => $rowData['version'] ?: '1.0',
                'statut' => $status,
                'description' => $rowData['observation'],
                'processus' => $rowData['processus'],
                'created_by' => auth()->id(),
            ]);
            
            $this->imported[] = [
                'row' => $this->currentRow,
                'document_id' => $document->id,
                'nom' => $document->nom,
                'code' => $document->code,
            ];
            
        } catch (\Exception $e) {
            $this->errors[] = [
                'row' => $this->currentRow,
                'data' => $rowData,
                'errors' => ['Erreur création: ' . $e->getMessage()],
            ];
        }
    }
    
    /**
     * Map Excel type to DB type
     */
    protected function mapType(?string $type): string
    {
        if (!$type) return 'procedure';
        
        $type = strtolower(trim($type));
        
        return match(true) {
            str_contains($type, 'politique') => 'politique',
            str_contains($type, 'manuel') => 'manuel',
            str_contains($type, 'procédure') || str_contains($type, 'procedure') => 'procedure',
            str_contains($type, 'instruction') => 'instruction',
            str_contains($type, 'enregistrement') => 'enregistrement',
            str_contains($type, 'formulaire') => 'formulaire',
            default => 'procedure',
        };
    }
    
    /**
     * Map Excel status to DB status
     */
    protected function mapStatus(?string $etat): string
    {
        if (!$etat) return 'brouillon';
        
        $etat = strtolower(trim($etat));
        
        return match(true) {
            str_contains($etat, 'brouillon') || str_contains($etat, 'draft') => 'brouillon',
            str_contains($etat, 'revue') || str_contains($etat, 'review') => 'en_revue',
            str_contains($etat, 'validé') || str_contains($etat, 'valide') || str_contains($etat, 'approved') => 'valide',
            str_contains($etat, 'obsolète') || str_contains($etat, 'obsolete') => 'obsolete',
            str_contains($etat, 'archivé') || str_contains($etat, 'archive') => 'archive',
            default => 'brouillon',
        };
    }
    
    /**
     * Detect document level (pyramide N1-N5)
     */
    protected function detectNiveau(string $type): int
    {
        return match($type) {
            'politique' => 1,
            'manuel' => 2,
            'procedure' => 3,
            'instruction' => 4,
            'enregistrement', 'formulaire' => 5,
            default => 3,
        };
    }
    
    /**
     * Generate auto code if not provided
     */
    protected function generateCode(string $type): string
    {
        $prefix = match($type) {
            'politique' => 'POL',
            'manuel' => 'MAN',
            'procedure' => 'PRD',
            'instruction' => 'INS',
            'enregistrement' => 'ENR',
            'formulaire' => 'FOR',
            default => 'DOC',
        };
        
        $count = Document::where('type', $type)->count() + 1;
        $year = date('Y');
        
        return sprintf('%s_%03d_%d', $prefix, $count, $year);
    }
    
    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get imported documents
     */
    public function getImported(): array
    {
        return $this->imported;
    }
}
