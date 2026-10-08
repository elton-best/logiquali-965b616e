<?php

namespace App\Jobs;

use App\Imports\JobDescriptionImport;
use App\Models\ImportLog;
use App\Notifications\ImportJobDescriptionCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ImportJobDescriptionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 heure max
    public int $tries = 1; // Pas de retry automatique pour éviter les doublons

    public function __construct(
        private readonly int $importLogId,
        private readonly string $filePath,
        private readonly int $enterpriseId,
        private readonly int $siteId,
        private readonly string $importMode = 'flexible',
        private readonly ?int $importedByUserId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importLog = ImportLog::findOrFail($this->importLogId);
        
        try {
            // Marquer comme en cours de traitement
            $importLog->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            Log::info('Starting job descriptions import', [
                'import_log_id' => $this->importLogId,
                'file_path' => $this->filePath,
                'enterprise_id' => $this->enterpriseId,
                'import_mode' => $this->importMode,
            ]);

            // Vérifier que le fichier existe
            $fullPath = Storage::path($this->filePath);
            if (!file_exists($fullPath)) {
                throw new \Exception("Le fichier d'import n'existe pas : {$this->filePath}");
            }

            // Créer l'instance d'import
            $import = new JobDescriptionImport(
                $this->enterpriseId,
                $this->siteId,
                $this->importMode,
                $this->importedByUserId
            );

            // Exécuter l'import
            Excel::import($import, $fullPath);

            // Récupérer les statistiques d'import
            $stats = $import->getStatistics();
            $failures = collect($import->failures());
            $errors = collect($import->errors());

            // Calculer les totaux
            $processedRows = $stats['processed_rows'];
            $successfulRows = $stats['successful_rows'];
            $failedRows = $stats['failed_rows'];

            Log::info('Job descriptions import completed', [
                'import_log_id' => $this->importLogId,
                'processed_rows' => $processedRows,
                'successful_rows' => $successfulRows,
                'failed_rows' => $failedRows,
            ]);

            // Générer le rapport d'erreurs si nécessaire
            $errorReportPath = null;
            if ($failedRows > 0 && ($failures->isNotEmpty() || $errors->isNotEmpty())) {
                $errorReportPath = $this->generateErrorReport($failures, $errors, $importLog);
                
                Log::info('Error report generated', [
                    'import_log_id' => $this->importLogId,
                    'error_report_path' => $errorReportPath,
                    'failures_count' => $failures->count(),
                    'errors_count' => $errors->count(),
                ]);
            }

            // Mettre à jour le log d'import avec les résultats
            $importLog->update([
                'status' => 'completed',
                'processed_rows' => $processedRows,
                'successful_rows' => $successfulRows,
                'failed_rows' => $failedRows,
                'completed_at' => now(),
                'error_report_path' => $errorReportPath,
            ]);

            // Envoyer la notification de complétion
            $this->sendCompletionNotification($importLog);

            // Nettoyer le fichier temporaire d'import (garder le rapport d'erreurs)
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
                Log::info('Temporary import file deleted', ['file_path' => $this->filePath]);
            }

        } catch (\Exception $e) {
            // Marquer l'import comme échoué
            $importLog->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            Log::error('Job descriptions import failed critically', [
                'import_log_id' => $this->importLogId,
                'file_path' => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Envoyer quand même une notification d'échec
            $this->sendCompletionNotification($importLog);

            // Nettoyer le fichier temporaire même en cas d'erreur
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }

            throw $e;
        }
    }

    /**
     * Génère un rapport d'erreurs Excel avec les lignes échouées
     */
    private function generateErrorReport($failures, $errors, ImportLog $importLog): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Erreurs d\'import');

            // En-têtes du rapport
            $headers = [
                'Ligne',
                'Champ',
                'Valeur',
                'Erreur',
                'Type',
            ];

            // Style de l'en-tête (fond rouge, texte blanc, gras)
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC3545'], // Rouge Bootstrap danger
                ],
            ];

            // Écrire les en-têtes
            $sheet->fromArray($headers, null, 'A1');
            $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

            // Auto-size des colonnes
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $currentRow = 2;

            // Ajouter les failures (erreurs de validation)
            foreach ($failures as $failure) {
                $rowNumber = $failure->row();
                $attribute = $failure->attribute();
                $errors = $failure->errors();
                $values = $failure->values();

                foreach ($errors as $error) {
                    $sheet->fromArray([
                        $rowNumber,
                        $attribute,
                        $values[$attribute] ?? '',
                        $error,
                        'Validation',
                    ], null, "A{$currentRow}");
                    
                    // Alterner les couleurs de fond pour la lisibilité
                    if ($currentRow % 2 === 0) {
                        $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8F9FA'], // Gris clair
                            ],
                        ]);
                    }
                    
                    $currentRow++;
                }
            }

            // Ajouter les errors (erreurs d'exécution)
            foreach ($errors as $error) {
                $sheet->fromArray([
                    $error->row() ?? 'N/A',
                    'N/A',
                    'N/A',
                    $error->errors()[0] ?? 'Erreur inconnue',
                    'Exécution',
                ], null, "A{$currentRow}");
                
                if ($currentRow % 2 === 0) {
                    $sheet->getStyle("A{$currentRow}:E{$currentRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }
                
                $currentRow++;
            }

            // Ajouter un résumé en haut de la feuille
            $sheet->insertNewRowBefore(1, 3);
            $sheet->setCellValue('A1', 'RAPPORT D\'ERREURS - IMPORT FICHES DE POSTE');
            $sheet->setCellValue('A2', "Fichier : {$importLog->file_name}");
            $sheet->setCellValue('A3', "Date : " . now()->format('d/m/Y H:i:s'));
            
            $sheet->getStyle('A1')->applyFromArray([
                'font' => ['bold' => true, 'size' => 14],
            ]);
            
            // Décaler les données de 3 lignes
            $currentRow += 3;

            // Générer le nom du fichier du rapport
            $timestamp = now()->format('Y-m-d_His');
            $fileName = "import_errors_{$importLog->id}_{$timestamp}.xlsx";
            $relativePath = "imports/errors/{$fileName}";
            
            // S'assurer que le répertoire existe
            $directory = storage_path('app/imports/errors');
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Sauvegarder le fichier
            $writer = new Xlsx($spreadsheet);
            $absolutePath = storage_path("app/{$relativePath}");
            $writer->save($absolutePath);

            return $relativePath;

        } catch (\Exception $e) {
            Log::error('Failed to generate error report', [
                'import_log_id' => $importLog->id,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Envoie la notification de complétion à l'utilisateur
     */
    private function sendCompletionNotification(ImportLog $importLog): void
    {
        try {
            $user = $importLog->user;
            
            if ($user) {
                $user->notify(new ImportJobDescriptionCompletedNotification($importLog));
                
                Log::info('Import completion notification sent', [
                    'import_log_id' => $importLog->id,
                    'user_id' => $user->id,
                    'status' => $importLog->status,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send import completion notification', [
                'import_log_id' => $importLog->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $importLog = ImportLog::find($this->importLogId);
        
        if ($importLog) {
            $importLog->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);
            
            // Envoyer la notification d'échec
            $this->sendCompletionNotification($importLog);
        }

        Log::error('Import job failed', [
            'import_log_id' => $this->importLogId,
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
