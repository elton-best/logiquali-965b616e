<?php

namespace App\Imports;

use App\Models\JobDescription;
use App\Models\JobDescriptionHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class JobDescriptionImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsEmptyRows, WithChunkReading, SkipsOnFailure, SkipsOnError
{
    use SkipsFailures, SkipsErrors;

    private int $processedRows = 0;
    private int $successfulRows = 0;
    private int $failedRows = 0;

    public function __construct(
        private readonly int $enterpriseId,
        private readonly int $siteId,
        private readonly string $importMode = 'flexible', // 'strict' ou 'flexible'
        private readonly ?int $importedByUserId = null
    ) {}

    /**
     * Validation rules pour chaque ligne
     */
    public function rules(): array
    {
        return [
            // Champs obligatoires
            'titre_du_poste' => ['required', 'string', 'max:255'],
            'mission' => ['required', 'string'],

            // Champs optionnels avec validation
            'id_collaborateur' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('enterprise_id', $this->enterpriseId);
                }),
            ],
            'departement' => ['nullable', 'string', 'max:255'],
            'superieur_hierarchique_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('enterprise_id', $this->enterpriseId);
                }),
            ],
            'titre_de_remplacement' => ['nullable', 'string', 'max:255'],
            'activites' => ['nullable', 'string'],
            'activites_principales' => ['nullable', 'string'], // Séparé par ;
            'activites_secondaires' => ['nullable', 'string'], // Séparé par ;
            'lieu_de_travail' => ['nullable', 'string', 'max:255'],
            'horaire_de_travail' => ['nullable', 'string', 'max:255'],
            'deplacement_requis' => ['nullable', 'string', 'in:oui,non,Oui,Non,OUI,NON,1,0'],
            'competences_requises' => ['nullable', 'string'], // Séparé par ;
            'experience_requise' => ['nullable', 'string'],
            'formation_requise' => ['nullable', 'string'],
        ];
    }

    /**
     * Messages d'erreur personnalisés en français
     */
    public function customValidationMessages(): array
    {
        return [
            'titre_du_poste.required' => 'Le titre du poste est obligatoire.',
            'titre_du_poste.max' => 'Le titre du poste ne peut pas dépasser 255 caractères.',
            'mission.required' => 'La mission est obligatoire.',
            'id_collaborateur.exists' => 'Le collaborateur spécifié n\'existe pas dans votre entreprise.',
            'superieur_hierarchique_id.exists' => 'Le supérieur hiérarchique spécifié n\'existe pas dans votre entreprise.',
            'deplacement_requis.in' => 'Le champ déplacement requis doit être "oui" ou "non".',
        ];
    }

    /**
     * Traitement de chaque ligne du fichier
     */
    public function onRow(Row $row): void
    {
        $this->processedRows++;
        $data = $row->toArray();

        try {
            DB::beginTransaction();

            // Parser les données de la ligne
            $parsedData = $this->parseRowData($data);

            // Validation cross-tenant pour user_id et reports_to_id
            if (!$this->validateCrossTenantFields($parsedData)) {
                Log::warning('Cross-tenant validation failed', [
                    'row' => $row->getIndex(),
                    'enterprise_id' => $this->enterpriseId,
                    'user_id' => $parsedData['user_id'] ?? null,
                    'reports_to_id' => $parsedData['reports_to_id'] ?? null,
                ]);
                $this->failedRows++;
                DB::rollBack();
                return;
            }

            // Créer la fiche de poste
            $jobDescription = JobDescription::create([
                'enterprise_id' => $this->enterpriseId,
                'site_id' => $this->siteId,
                'user_id' => $parsedData['user_id'],
                'job_title' => $parsedData['job_title'],
                'replacement_job_title' => $parsedData['replacement_job_title'],
                'department' => $parsedData['department'],
                'reports_to_id' => $parsedData['reports_to_id'],
                'mission' => $parsedData['mission'],
                'activities' => $parsedData['activities'],
                'main_activities' => $parsedData['main_activities'],
                'secondary_activities' => $parsedData['secondary_activities'],
                'work_location' => $parsedData['work_location'],
                'work_schedule' => $parsedData['work_schedule'],
                'travel_required' => $parsedData['travel_required'],
                'required_skills' => $parsedData['required_skills'],
                'required_experience' => $parsedData['required_experience'],
                'required_education' => $parsedData['required_education'],
                'created_by' => $this->importedByUserId ?? Auth::id(),
            ]);

            // Enregistrer l'historique de création
            $this->recordCreationHistory($jobDescription);

            $this->successfulRows++;
            DB::commit();

            Log::info('Job description imported successfully', [
                'job_description_id' => $jobDescription->id,
                'row' => $row->getIndex(),
                'job_title' => $jobDescription->job_title,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->failedRows++;

            Log::error('Failed to import job description', [
                'row' => $row->getIndex(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Parse les données brutes de la ligne Excel
     */
    private function parseRowData(array $data): array
    {
        // Nettoyer les clés (supprimer les espaces, accents, etc.)
        $normalizedData = $this->normalizeKeys($data);

        return [
            'user_id' => $this->parseUserId($normalizedData['id_collaborateur'] ?? null),
            'job_title' => trim($normalizedData['titre_du_poste'] ?? ''),
            'replacement_job_title' => $this->parseString($normalizedData['titre_de_remplacement'] ?? null),
            'department' => $this->parseString($normalizedData['departement'] ?? null),
            'reports_to_id' => $this->parseUserId($normalizedData['superieur_hierarchique_id'] ?? null),
            'mission' => trim($normalizedData['mission'] ?? ''),
            'activities' => $this->parseString($normalizedData['activites'] ?? null),
            'main_activities' => $this->parseArrayField($normalizedData['activites_principales'] ?? null),
            'secondary_activities' => $this->parseArrayField($normalizedData['activites_secondaires'] ?? null),
            'work_location' => $this->parseString($normalizedData['lieu_de_travail'] ?? null),
            'work_schedule' => $this->parseString($normalizedData['horaire_de_travail'] ?? null),
            'travel_required' => $this->parseBoolean($normalizedData['deplacement_requis'] ?? null),
            'required_skills' => $this->parseArrayField($normalizedData['competences_requises'] ?? null),
            'required_experience' => $this->parseString($normalizedData['experience_requise'] ?? null),
            'required_education' => $this->parseString($normalizedData['formation_requise'] ?? null),
        ];
    }

    /**
     * Normalise les clés du tableau pour gérer les variations
     */
    private function normalizeKeys(array $data): array
    {
        $normalized = [];
        
        foreach ($data as $key => $value) {
            // Convertir en minuscules et remplacer les espaces par des underscores
            $normalizedKey = strtolower(trim($key));
            $normalizedKey = str_replace([' ', '-', '.'], '_', $normalizedKey);
            
            // Supprimer les accents
            $normalizedKey = $this->removeAccents($normalizedKey);
            
            $normalized[$normalizedKey] = $value;
        }
        
        return $normalized;
    }

    /**
     * Supprime les accents d'une chaîne
     */
    private function removeAccents(string $str): string
    {
        $unwanted_array = [
            'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A',
            'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
            'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U',
            'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a',
            'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i',
            'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u',
            'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y'
        ];
        
        return strtr($str, $unwanted_array);
    }

    /**
     * Parse un champ user_id avec validation
     */
    private function parseUserId(?string $value): ?int
    {
        if (empty($value)) {
            return null;
        }

        // Si c'est déjà un nombre
        if (is_numeric($value)) {
            $userId = (int) $value;
            
            // Vérifier que l'utilisateur existe et appartient à la bonne entreprise
            $user = User::where('id', $userId)
                ->where('enterprise_id', $this->enterpriseId)
                ->first();
            
            return $user ? $userId : null;
        }

        return null;
    }

    /**
     * Parse une chaîne de caractères
     */
    private function parseString(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $cleaned = trim($value);
        return empty($cleaned) ? null : $cleaned;
    }

    /**
     * Parse un champ booléen
     */
    private function parseBoolean(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        $normalized = strtolower(trim($value));
        
        return in_array($normalized, ['oui', 'yes', '1', 'true', 'vrai'], true);
    }

    /**
     * Parse un champ array (séparé par des points-virgules)
     */
    private function parseArrayField(?string $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        // Séparer par point-virgule
        $items = explode(';', $value);
        
        // Nettoyer chaque élément
        $cleanedItems = array_filter(array_map('trim', $items), function($item) {
            return !empty($item);
        });

        return empty($cleanedItems) ? null : array_values($cleanedItems);
    }

    /**
     * Valide que les user_id et reports_to_id appartiennent bien à l'entreprise
     */
    private function validateCrossTenantFields(array $parsedData): bool
    {
        // Valider user_id
        if (!empty($parsedData['user_id'])) {
            $userExists = User::where('id', $parsedData['user_id'])
                ->where('enterprise_id', $this->enterpriseId)
                ->exists();
            
            if (!$userExists) {
                Log::warning('User does not belong to enterprise', [
                    'user_id' => $parsedData['user_id'],
                    'enterprise_id' => $this->enterpriseId,
                ]);
                return false;
            }
        }

        // Valider reports_to_id
        if (!empty($parsedData['reports_to_id'])) {
            $managerExists = User::where('id', $parsedData['reports_to_id'])
                ->where('enterprise_id', $this->enterpriseId)
                ->exists();
            
            if (!$managerExists) {
                Log::warning('Manager does not belong to enterprise', [
                    'reports_to_id' => $parsedData['reports_to_id'],
                    'enterprise_id' => $this->enterpriseId,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * Enregistre l'historique de création dans job_description_histories
     */
    private function recordCreationHistory(JobDescription $jobDescription): void
    {
        // Enregistrer les champs principaux dans l'historique
        $fieldsToTrack = [
            'job_title' => $jobDescription->job_title,
            'mission' => $jobDescription->mission,
            'user_id' => $jobDescription->user_id,
            'department' => $jobDescription->department,
            'reports_to_id' => $jobDescription->reports_to_id,
            'replacement_job_title' => $jobDescription->replacement_job_title,
        ];

        foreach ($fieldsToTrack as $fieldKey => $newValue) {
            if (!empty($newValue)) {
                JobDescriptionHistory::create([
                    'job_description_id' => $jobDescription->id,
                    'enterprise_id' => $this->enterpriseId,
                    'site_id' => $this->siteId,
                    'field_key' => $fieldKey,
                    'old_value' => null,
                    'new_value' => is_array($newValue) ? json_encode($newValue) : (string) $newValue,
                    'changed_by' => $this->importedByUserId ?? Auth::id(),
                    'change_source' => 'import',
                    'changed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Taille des chunks pour le traitement par lots
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Récupérer les statistiques d'importation
     */
    public function getStatistics(): array
    {
        return [
            'processed_rows' => $this->processedRows,
            'successful_rows' => $this->successfulRows,
            'failed_rows' => $this->failedRows,
        ];
    }
}
