<?php

namespace App\Imports;

use App\Models\Action;
use App\Models\User;
use App\Models\Process;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Validator;

/**
 * Import Action Plan from XLSX file
 * 
 * Format attendu:
 * Colonnes: N°, Titre, Description, Type, Priorité, Responsable (email), 
 *           Processus (code), Délai (date), Statut
 */
class ActionPlanImport
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
            $sheet = $spreadsheet->getActiveSheet();
            
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
     * Process sheet
     */
    protected function processSheet(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestRow();
        
        // Skip header row
        for ($row = 2; $row <= $highestRow; $row++) {
            $this->currentRow = $row;
            
            $rowData = [
                'numero' => $sheet->getCell('A' . $row)->getValue(),
                'titre' => $sheet->getCell('B' . $row)->getValue(),
                'description' => $sheet->getCell('C' . $row)->getValue(),
                'type' => $sheet->getCell('D' . $row)->getValue(),
                'priorite' => $sheet->getCell('E' . $row)->getValue(),
                'responsable_email' => $sheet->getCell('F' . $row)->getValue(),
                'processus_code' => $sheet->getCell('G' . $row)->getValue(),
                'delai' => $sheet->getCell('H' . $row)->getValue(),
                'statut' => $sheet->getCell('I' . $row)->getValue(),
            ];
            
            // Skip empty rows
            if (empty($rowData['titre'])) {
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
            'titre' => 'required|string|max:255',
            'type' => 'nullable|string|in:corrective,preventive,amelioration',
            'priorite' => 'nullable|string|in:basse,moyenne,haute,critique',
            'responsable_email' => 'nullable|email|exists:users,email',
            'processus_code' => 'nullable|string|exists:processes,code',
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
            // Find responsible user
            $responsableId = null;
            if ($rowData['responsable_email']) {
                $user = User::where('email', $rowData['responsable_email'])->first();
                $responsableId = $user?->id;
            }
            
            // Find process
            $processId = null;
            if ($rowData['processus_code']) {
                $process = Process::where('code', $rowData['processus_code'])->first();
                $processId = $process?->id;
            }
            
            // Parse date
            $delai = null;
            if ($rowData['delai']) {
                try {
                    $delai = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rowData['delai']);
                } catch (\Exception $e) {
                    // Try parsing as string
                    $delai = new \DateTime($rowData['delai']);
                }
            }
            
            // Create action
            $action = Action::create([
                'site_id' => $this->siteId,
                'titre' => $rowData['titre'],
                'description' => $rowData['description'],
                'type' => $this->mapType($rowData['type']),
                'priorite' => $this->mapPriorite($rowData['priorite']),
                'responsable_id' => $responsableId,
                'process_id' => $processId,
                'delai' => $delai,
                'statut' => $this->mapStatut($rowData['statut']),
                'created_by' => auth()->id(),
            ]);
            
            $this->imported[] = [
                'row' => $this->currentRow,
                'action_id' => $action->id,
                'titre' => $action->titre,
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
     * Map type
     */
    protected function mapType(?string $type): string
    {
        if (!$type) return 'corrective';
        
        $type = strtolower(trim($type));
        
        return match(true) {
            str_contains($type, 'corrective') || str_contains($type, 'correctif') => 'corrective',
            str_contains($type, 'preventive') || str_contains($type, 'préventif') => 'preventive',
            str_contains($type, 'amélioration') || str_contains($type, 'amelioration') => 'amelioration',
            default => 'corrective',
        };
    }
    
    /**
     * Map priorite
     */
    protected function mapPriorite(?string $priorite): string
    {
        if (!$priorite) return 'moyenne';
        
        $priorite = strtolower(trim($priorite));
        
        return match(true) {
            str_contains($priorite, 'critique') || str_contains($priorite, 'critical') => 'critique',
            str_contains($priorite, 'haute') || str_contains($priorite, 'high') => 'haute',
            str_contains($priorite, 'moyenne') || str_contains($priorite, 'medium') => 'moyenne',
            str_contains($priorite, 'basse') || str_contains($priorite, 'low') => 'basse',
            default => 'moyenne',
        };
    }
    
    /**
     * Map statut
     */
    protected function mapStatut(?string $statut): string
    {
        if (!$statut) return 'planifiee';
        
        $statut = strtolower(trim($statut));
        
        return match(true) {
            str_contains($statut, 'planifiée') || str_contains($statut, 'planifiee') || str_contains($statut, 'planned') => 'planifiee',
            str_contains($statut, 'en cours') || str_contains($statut, 'progress') => 'en_cours',
            str_contains($statut, 'terminée') || str_contains($statut, 'terminee') || str_contains($statut, 'done') => 'terminee',
            str_contains($statut, 'validée') || str_contains($statut, 'validee') || str_contains($statut, 'validated') => 'validee',
            str_contains($statut, 'annulée') || str_contains($statut, 'annulee') || str_contains($statut, 'cancelled') => 'annulee',
            default => 'planifiee',
        };
    }
}
