<?php

namespace App\Modules\Leadership\Controllers;

use App\Models\Site;
use App\Services\DocumentSyncService;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobDescriptionImportRequest;
use App\Jobs\ImportJobDescriptionsJob;
use App\Models\ImportLog;
use App\Models\User;
use App\Services\JobDescriptionTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JobDescriptionImportController extends Controller
{
    public function __construct(
        private readonly JobDescriptionTemplateService $templateService
    ) {
        // Permissions métier fiches de poste
        $manage = 'permission:leadership.roles_responsabilites.fiche_poste.create'
            . '|job_descriptions.manage';

        $this->middleware($manage)->only(['upload', 'confirm']);
        $this->middleware('throttle:10,60')->only(['upload']); // Max 10 uploads par heure
    }

    /**
     * Étape 1 : Upload du fichier d'import
     * 
     * POST /api/v1/job-descriptions/import/upload
     * 
     * @param JobDescriptionImportRequest $request
     * @return JsonResponse
     */
    public function upload(JobDescriptionImportRequest $request): JsonResponse
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $file = $request->file('file');
            $importMode = $request->input('import_mode', 'flexible');

            // Stocker le fichier temporairement
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $filePath = $file->store('imports/temp', 'local');

            Log::info('Job description import file uploaded', [
                'user_id' => $currentUser->id,
                'enterprise_id' => $currentUser->enterprise_id,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'import_mode' => $importMode,
            ]);

            // Compter le nombre de lignes (hors en-tête)
            $fullPath = Storage::path($filePath);
            $totalRows = $this->countExcelRows($fullPath);

            // Créer l'enregistrement import_log
            $importLog = ImportLog::create([
                'user_id' => $currentUser->id,
                'enterprise_id' => $currentUser->enterprise_id,
                'site_id' => $currentUser->site_id,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'total_rows' => $totalRows,
                'status' => 'pending',
            ]);

            // Soft limit warning (5000 lignes)
            $warning = null;
            if ($totalRows > 5000) {
                $warning = "Attention : Le fichier contient {$totalRows} lignes. " .
                    "Les imports volumineux peuvent prendre plusieurs minutes. " .
                    "Il est recommandé de découper les imports en fichiers de moins de 5000 lignes.";
                
                Log::warning('Large import file detected', [
                    'import_log_id' => $importLog->id,
                    'total_rows' => $totalRows,
                ]);
            }

            return response()->json([
                'message' => 'Fichier uploadé avec succès',
                'data' => [
                    'import_id' => $importLog->id,
                    'file_name' => $fileName,
                    'total_rows' => $totalRows,
                    'import_mode' => $importMode,
                    'status' => 'pending',
                    'warning' => $warning,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to upload import file', [
                'user_id' => $currentUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Échec de l\'upload du fichier',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Étape 2 : Preview de l'import (optionnel - pour afficher un aperçu avant confirmation)
     * 
     * GET /api/v1/job-descriptions/import/preview/{importId}
     * 
     * @param int $importId
     * @return JsonResponse
     */
    public function preview(int $importId): JsonResponse
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $importLog = ImportLog::find($importId);

        if (!$importLog) {
            return response()->json(['message' => 'Import introuvable'], 404);
        }

        // Vérifier que l'utilisateur a accès à cet import
        if ($importLog->user_id !== $currentUser->id && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Vérifier cross-tenant access
        if ($importLog->enterprise_id !== $currentUser->enterprise_id && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        return response()->json([
            'data' => [
                'import_id' => $importLog->id,
                'file_name' => $importLog->file_name,
                'file_size' => $importLog->file_size,
                'total_rows' => $importLog->total_rows,
                'processed_rows' => $importLog->processed_rows,
                'successful_rows' => $importLog->successful_rows,
                'failed_rows' => $importLog->failed_rows,
                'status' => $importLog->status,
                'started_at' => $importLog->started_at?->toIso8601String(),
                'completed_at' => $importLog->completed_at?->toIso8601String(),
                'duration_seconds' => $importLog->getDurationInSeconds(),
                'success_rate' => $importLog->getSuccessRate(),
                'has_errors' => $importLog->hasErrors(),
                'error_report_available' => !empty($importLog->error_report_path),
            ],
        ]);
    }

    /**
     * Étape 3 : Confirmer et lancer l'import
     * 
     * POST /api/v1/job-descriptions/import/confirm/{importId}
     * 
     * @param Request $request
     * @param int $importId
     * @return JsonResponse
     */
    public function confirm(Request $request, int $importId): JsonResponse
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $importLog = ImportLog::find($importId);

        if (!$importLog) {
            return response()->json(['message' => 'Import introuvable'], 404);
        }

        // Vérifier que l'utilisateur a accès à cet import
        if ($importLog->user_id !== $currentUser->id && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Vérifier cross-tenant access
        if ($importLog->enterprise_id !== $currentUser->enterprise_id && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        // Vérifier que l'import est en statut 'pending'
        if ($importLog->status !== 'pending') {
            return response()->json([
                'message' => "L'import ne peut pas être confirmé",
                'error' => "Statut actuel : {$importLog->status}. Seuls les imports en statut 'pending' peuvent être confirmés.",
            ], 422);
        }

        // Récupérer le import_mode de la requête (ou garder 'flexible' par défaut)
        $importMode = $request->input('import_mode', 'flexible');
        
        // Valider l'import_mode
        if (!in_array($importMode, ['strict', 'flexible'], true)) {
            return response()->json([
                'message' => 'Mode d\'import invalide',
                'error' => "Le mode doit être 'strict' ou 'flexible'",
            ], 422);
        }

        try {
            // Récupérer le chemin du fichier uploadé
            $filePath = "imports/temp/" . basename($importLog->file_name);
            
            // Vérifier que le fichier existe toujours
            if (!Storage::exists($filePath)) {
                // Chercher avec un pattern plus flexible
                $files = Storage::files('imports/temp');
                $matchingFile = null;
                
                foreach ($files as $file) {
                    if (str_contains($file, (string) $importLog->id) || 
                        basename($file) === $importLog->file_name) {
                        $matchingFile = $file;
                        break;
                    }
                }
                
                if (!$matchingFile) {
                    return response()->json([
                        'message' => 'Fichier d\'import introuvable',
                        'error' => 'Le fichier temporaire a peut-être été supprimé. Veuillez ré-uploader le fichier.',
                    ], 404);
                }
                
                $filePath = $matchingFile;
            }

            Log::info('Dispatching job description import job', [
                'import_log_id' => $importLog->id,
                'file_path' => $filePath,
                'import_mode' => $importMode,
            ]);

            // Dispatcher le job de queue
            ImportJobDescriptionsJob::dispatch(
                $importLog->id,
                $filePath,
                $currentUser->enterprise_id,
                $currentUser->site_id,
                $importMode,
                $currentUser->id
            );

            // Marquer comme validating (le job mettra à jour à 'processing')
            $importLog->update(['status' => 'validating']);

            return response()->json([
                'message' => 'Import lancé avec succès',
                'data' => [
                    'import_id' => $importLog->id,
                    'status' => 'validating',
                    'message' => 'L\'import est en cours de traitement. Vous recevrez une notification par email et dans l\'application lorsque l\'import sera terminé.',
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to confirm import', [
                'import_log_id' => $importLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Échec de la confirmation de l\'import',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Télécharger le template Excel pré-rempli
     * 
     * GET /api/v1/job-descriptions/import/template
     * 
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadTemplate(): BinaryFileResponse|JsonResponse
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            Log::info('Job description template downloaded', [
                'user_id' => $currentUser->id,
                'enterprise_id' => $currentUser->enterprise_id,
            ]);

            $template = $this->templateService->generateTemplate((int) $currentUser->enterprise_id);
            $filename = (string) ($template['filename'] ?? 'modele_fiches_poste.xlsx');
            $tempPath = (string) ($template['path'] ?? '');

            if ($tempPath === '' || !is_file($tempPath)) {
                return response()->json([
                    'message' => 'Échec de la génération du template',
                ], 500);
            }

            $siteId = (int) ($currentUser->site_id ?? 0);
            if ($siteId <= 0 && $currentUser->enterprise_id) {
                $siteId = (int) \App\Models\Site::query()
                    ->where('enterprise_id', (int) $currentUser->enterprise_id)
                    ->orderBy('id')
                    ->value('id');
            }
            if ($siteId > 0) {
                app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                    'site_id' => $siteId,
                    'process_id' => null,
                    'process_code' => 'GEN',
                    'document_kind' => 'job_description_template_xlsx',
                    'title' => 'Modèle import fiches de poste',
                    'description' => 'Template XLSX d’import des fiches de poste.',
                    'file_source_path' => $tempPath,
                    'file_extension' => 'xlsx',
                    'created_by' => $currentUser->id,
                    'type' => 'FOR',
                ]);
            }

            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Failed to generate import template', [
                'user_id' => $currentUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Échec de la génération du template',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Télécharger le rapport d'erreurs d'un import
     * 
     * GET /api/v1/job-descriptions/import/logs/{logId}/errors
     * 
     * @param int $logId
     * @return BinaryFileResponse|JsonResponse
     */
    public function downloadErrorReport(int $logId): BinaryFileResponse|JsonResponse
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $importLog = ImportLog::find($logId);

        if (!$importLog) {
            return response()->json(['message' => 'Import introuvable'], 404);
        }

        // Vérifier que l'utilisateur a accès à cet import
        if ($importLog->user_id !== $currentUser->id && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Vérifier cross-tenant access
        if ($importLog->enterprise_id !== $currentUser->enterprise_id && !$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Accès interdit'], 403);
        }

        // Vérifier qu'un rapport d'erreurs existe
        if (empty($importLog->error_report_path)) {
            return response()->json([
                'message' => 'Aucun rapport d\'erreurs disponible',
                'error' => 'Cet import ne contient aucune erreur ou le rapport n\'a pas été généré.',
            ], 404);
        }

        // Vérifier que le fichier existe
        if (!Storage::exists($importLog->error_report_path)) {
            return response()->json([
                'message' => 'Fichier de rapport introuvable',
                'error' => 'Le rapport d\'erreurs a peut-être été supprimé.',
            ], 404);
        }

        try {
            Log::info('Error report downloaded', [
                'import_log_id' => $importLog->id,
                'user_id' => $currentUser->id,
            ]);

            $fileName = 'rapport_erreurs_' . $importLog->id . '.xlsx';
            
            return Storage::download($importLog->error_report_path, $fileName);

        } catch (\Exception $e) {
            Log::error('Failed to download error report', [
                'import_log_id' => $importLog->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Échec du téléchargement du rapport d\'erreurs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lister les imports de l'utilisateur (historique)
     * 
     * GET /api/v1/job-descriptions/import/logs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logs(Request $request): JsonResponse
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = ImportLog::query()
            ->where('enterprise_id', $currentUser->enterprise_id);

        // Filtrer par utilisateur si pas super admin
        if (!$currentUser->isSuperAdmin()) {
            $query->where('user_id', $currentUser->id);
        }

        // Filtres optionnels
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }

        if ($request->has('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $logs = $query
            ->with('user:id,first_name,last_name,email')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'file_name' => $log->file_name,
                    'file_size' => $log->file_size,
                    'total_rows' => $log->total_rows,
                    'processed_rows' => $log->processed_rows,
                    'successful_rows' => $log->successful_rows,
                    'failed_rows' => $log->failed_rows,
                    'status' => $log->status,
                    'started_at' => $log->started_at?->toIso8601String(),
                    'completed_at' => $log->completed_at?->toIso8601String(),
                    'duration_seconds' => $log->getDurationInSeconds(),
                    'success_rate' => $log->getSuccessRate(),
                    'has_errors' => $log->hasErrors(),
                    'error_report_available' => !empty($log->error_report_path),
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->first_name . ' ' . $log->user->last_name,
                        'email' => $log->user->email,
                    ] : null,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Helper : Compter le nombre de lignes dans un fichier Excel (hors en-tête)
     */
    private function countExcelRows(string $filePath): int
    {
        try {
            $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array): array
                {
                    return $array;
                }
            }, $filePath);

            // Compter les lignes de la première feuille (hors en-tête)
            if (!empty($data) && !empty($data[0])) {
                // Soustraire 1 pour l'en-tête
                $totalRows = count($data[0]) - 1;
                return max(0, $totalRows);
            }

            return 0;

        } catch (\Exception $e) {
            Log::warning('Failed to count Excel rows, returning 0', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);
            
            return 0;
        }
    }

    /**
     * Helper : Récupérer l'utilisateur courant
     */
    private function currentUser(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }
}
