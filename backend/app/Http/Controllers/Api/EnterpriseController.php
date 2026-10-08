<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EnterpriseResource;
use App\Models\Enterprise;
use App\Models\EnterpriseSigleHistory;
use App\Models\Equipement;
use App\Models\EquipementCodeAlias;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EnterpriseController extends Controller
{
    public function index()
    {
        $enterprises = Enterprise::with(['sites', 'users'])
            ->paginate(20);

        return EnterpriseResource::collection($enterprises);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sigle' => 'nullable|string|max:10',
            'codification_mode' => 'nullable|string|in:standard,custom',
            'email' => 'required|email|unique:enterprises',
            'logo_path' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'status' => 'required|in:pending,active,suspended,rejected',
            'trial_ends_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string',
            'suspension_reason' => 'nullable|string',
            'organigram_path' => 'nullable|string',
            'field' => 'nullable|string',
            'domaine_activite' => 'nullable|string|max:255',
            'domaine_activite_set' => 'nullable|boolean',
        ]);

        // Source de vérité: "field" (domaine d'activité) pilote le booléen setup.
        $resolvedField = $validated['field'] ?? ($validated['domaine_activite'] ?? null);
        $validated['field'] = $resolvedField;
        $validated['domaine_activite_set'] = filled($resolvedField);
        unset($validated['domaine_activite']);

        $enterprise = Enterprise::create($validated);

        return new EnterpriseResource($enterprise);
    }

    public function show(Enterprise $enterprise)
    {
        return new EnterpriseResource($enterprise->load(['sites', 'users']));
    }

    public function update(Request $request, Enterprise $enterprise)
    {
        $previousSigle = $enterprise->sigle;
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'sigle' => 'nullable|string|max:10',
            'codification_mode' => 'nullable|string|in:standard,custom',
            'recode_equipements' => 'nullable|boolean',
            'email' => 'sometimes|email|unique:enterprises,email,' . $enterprise->id,
            'logo_path' => 'nullable|string',
            'registration_number' => 'nullable|string',
            'status' => 'sometimes|in:pending,active,suspended,rejected',
            'approval_status' => 'sometimes|in:pending,approved,rejected',
            'trial_ends_at' => 'nullable|date',
            'rejection_reason' => 'nullable|string',
            'suspension_reason' => 'nullable|string',
            'organigram_path' => 'nullable|string',
            'field' => 'nullable|string',
            'domaine_activite' => 'nullable|string|max:255',
            'domaine_activite_set' => 'nullable|boolean',
        ]);

        // Compat legacy: accepter domaine_activite en entrée mais normaliser vers field.
        if (array_key_exists('domaine_activite', $validated) && !array_key_exists('field', $validated)) {
            $validated['field'] = $validated['domaine_activite'];
        }

        // Source de vérité backend: domaine_activite_set dérive toujours du domaine stocké.
        if (array_key_exists('field', $validated) || array_key_exists('domaine_activite', $validated)) {
            $validated['domaine_activite_set'] = filled($validated['field'] ?? null);
        }

        unset($validated['domaine_activite']);

        // Keep enterprise status and approval_status consistent with DB CHECK constraint.
        if (isset($validated['status']) && !isset($validated['approval_status'])) {
            $validated['approval_status'] = match ($validated['status']) {
                'pending' => 'pending',
                'active', 'suspended' => 'approved',
                'rejected' => 'rejected',
                default => $enterprise->approval_status,
            };
        }

        $recodeEquipements = (bool) ($validated['recode_equipements'] ?? false);
        unset($validated['recode_equipements']);

        $enterprise->update($validated);

        $newSigle = $enterprise->sigle;
        if ($recodeEquipements) {
            if (($enterprise->codification_mode ?? 'standard') !== 'standard') {
                return response()->json([
                    'message' => 'La recodification n’est disponible qu’en mode standard.',
                ], 422);
            }

            if (!$newSigle || trim((string) $newSigle) === '') {
                return response()->json([
                    'message' => 'Le sigle est requis pour recoder les équipements.',
                ], 422);
            }

            if ((string) $previousSigle !== (string) $newSigle) {
                $this->recodeEquipementsForEnterprise($enterprise, (int) ($request->user()?->id ?? 0));
            }
        }

        return new EnterpriseResource($enterprise);
    }

    private function recodeEquipementsForEnterprise(Enterprise $enterprise, int $changedByUserId): int
    {
        $sigle = (string) $enterprise->sigle;
        /** @var int $affectedCount */
        $affectedCount = 0;

        \App\Models\Equipement::query()
            ->where('enterprise_id', $enterprise->id)
            ->with(['categorie', 'localisation'])
            ->orderBy('id')
            ->chunkById(200, function ($equipements) use ($sigle, $enterprise, $changedByUserId, &$affectedCount) {
                foreach ($equipements as $equipement) {
                    $categoryCode = (string) ($equipement->categorie?->code ?? '');
                    $localisationCode = (string) ($equipement->localisation?->code ?? '');
                    if ($categoryCode === '' || $localisationCode === '') {
                        continue;
                    }

                    $newCode = \App\Models\Equipement::genererCodeComplet(
                        $sigle,
                        $categoryCode,
                        (string) $equipement->nom_commun_abrege,
                        $localisationCode,
                        (string) $equipement->indice,
                        (string) $equipement->annee_acquisition
                    );

                    $oldCode = (string) $equipement->code_complet;
                    if ($newCode === $oldCode) {
                        continue;
                    }

                    $exists = \App\Models\Equipement::query()
                        ->where('enterprise_id', $enterprise->id)
                        ->where('code_complet', $newCode)
                        ->where('id', '!=', (int) $equipement->id)
                        ->exists();
                    if ($exists) {
                        throw new \InvalidArgumentException('Conflit de codification détecté lors de la recodification.');
                    }

                    DB::transaction(function () use ($equipement, $enterprise, $oldCode, $newCode, $changedByUserId): void {
                        \App\Models\EquipementCodeAlias::query()->updateOrCreate(
                            [
                                'enterprise_id' => $enterprise->id,
                                'equipement_id' => $equipement->id,
                                'code_alias' => $oldCode,
                            ],
                            [
                                'change_reason' => 'sigle_change',
                                'changed_by' => $changedByUserId ?: null,
                                'effective_from' => $equipement->created_at ?? now(),
                                'effective_to' => now(),
                                'is_primary_at_time' => true,
                                'notes' => 'Code principal avant changement de sigle',
                                'metadata' => [
                                    'from' => $oldCode,
                                    'to' => $newCode,
                                ],
                            ]
                        );

                        \App\Models\EquipementCodeAlias::query()->updateOrCreate(
                            [
                                'enterprise_id' => $enterprise->id,
                                'equipement_id' => $equipement->id,
                                'code_alias' => $newCode,
                            ],
                            [
                                'change_reason' => 'sigle_change',
                                'changed_by' => $changedByUserId ?: null,
                                'effective_from' => now(),
                                'effective_to' => null,
                                'is_primary_at_time' => true,
                                'notes' => 'Recodification suite changement de sigle',
                                'metadata' => [
                                    'from' => $oldCode,
                                    'to' => $newCode,
                                ],
                            ]
                        );

                        $equipement->update([
                            'code_complet' => $newCode,
                        ]);

                        $affectedCount++;
                    });
                }
            });

        return $affectedCount;
    }

    public function destroy(Enterprise $enterprise)
    {
        $enterprise->delete();

        return response()->json(null, 204);
    }

    /**
     * Tâche 1.1.1 : Preview sigle change impact without persisting
     * GET|POST /api/v1/enterprises/{id}/sigle/preview
     */
    public function previewSigleChange(Request $request, Enterprise $enterprise)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$this->canManageEnterpriseSigle($user, $enterprise)) {
            return response()->json([
                'message' => 'Unauthorized access to enterprise data',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'new_sigle' => 'required|string|max:10|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currentSigle = (string) ($enterprise->sigle ?? '');
        $newSigle = strtoupper(trim((string) $request->input('new_sigle')));

        // Sigle unchanged
        if ($currentSigle === $newSigle) {
            return response()->json([
                'current_sigle' => $currentSigle,
                'new_sigle' => $newSigle,
                'equipements_affected' => 0,
                'equipment_sample' => [],
                'warnings' => ['Le nouveau sigle est identique à l\'ancien.'],
                'can_proceed' => false,
            ]);
        }

        // Only standard mode supports re-codification
        if (($enterprise->codification_mode ?? 'standard') !== 'standard') {
            return response()->json([
                'current_sigle' => $currentSigle,
                'new_sigle' => $newSigle,
                'equipements_affected' => 0,
                'equipment_sample' => [],
                'warnings' => ['La recodification n\'est disponible qu\'en mode standard.'],
                'can_proceed' => false,
            ]);
        }

        // Count affected equipment + generate sample
        $query = Equipement::query()
            ->where('enterprise_id', $enterprise->id)
            ->with(['categorie', 'localisation']);

        $totalAffected = $query->count();

        $sample = $query
            ->orderBy('id')
            ->limit(5)
            ->get()
            ->map(function ($equipement) use ($currentSigle, $newSigle, $enterprise) {
                $categoryCode = (string) ($equipement->categorie?->code ?? '');
                $localisationCode = (string) ($equipement->localisation?->code ?? '');

                $oldCode = (string) $equipement->code_complet;

                if ($categoryCode && $localisationCode) {
                    $newCode = Equipement::genererCodeComplet(
                        $newSigle,
                        $categoryCode,
                        (string) $equipement->nom_commun_abrege,
                        $localisationCode,
                        (string) $equipement->indice,
                        (string) $equipement->annee_acquisition
                    );
                } else {
                    $newCode = $oldCode; // Cannot recode without category/location
                }

                return [
                    'id' => $equipement->id,
                    'nom_commun' => $equipement->nom_commun,
                    'current_code' => $oldCode,
                    'new_code' => $newCode,
                ];
            })
            ->toArray();

        $warnings = [];
        if ($totalAffected > 100) {
            $warnings[] = sprintf('Attention: %d équipements seront recodifiés. L\'opération peut être longue.', $totalAffected);
        }

        return response()->json([
            'current_sigle' => $currentSigle,
            'new_sigle' => $newSigle,
            'equipements_affected' => $totalAffected,
            'equipment_sample' => $sample,
            'warnings' => $warnings,
            'can_proceed' => true,
        ]);
    }

    /**
     * Tâche 1.1.2 : Update sigle with transactional re-codification
     * PUT /api/v1/enterprises/{id}/sigle
     */
    public function updateSigle(Request $request, Enterprise $enterprise)
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$this->canManageEnterpriseSigle($user, $enterprise)) {
            return response()->json([
                'message' => 'Unauthorized access to enterprise data',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'new_sigle' => 'required|string|max:10|min:1',
            'reason' => 'nullable|string|max:255',
            'recode_equipements' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $currentSigle = (string) ($enterprise->sigle ?? '');
        $newSigle = strtoupper(trim((string) $request->input('new_sigle')));
        $reason = $request->input('reason');
        $recodeEquipements = (bool) $request->input('recode_equipements', false);
        $userId = $request->user()?->id;

        // Sigle unchanged
        if ($currentSigle === $newSigle) {
            return response()->json([
                'message' => 'Le nouveau sigle est identique à l\'ancien.',
            ], 422);
        }

        // Recoding only in standard mode
        if ($recodeEquipements && ($enterprise->codification_mode ?? 'standard') !== 'standard') {
            return response()->json([
                'message' => 'La recodification n\'est disponible qu\'en mode standard.',
            ], 422);
        }

        try {
            return DB::transaction(function () use (
                $enterprise,
                $currentSigle,
                $newSigle,
                $reason,
                $recodeEquipements,
                $userId,
            ) {
                // 1. Create sigle history entry
                $historyEntry = EnterpriseSigleHistory::create([
                    'enterprise_id' => $enterprise->id,
                    'old_sigle' => $currentSigle ?: null,
                    'new_sigle' => $newSigle,
                    'reason' => $reason,
                    'recoding_mode' => $recodeEquipements ? 'auto' : 'none',
                    'changed_by' => $userId,
                    'equipements_affected' => 0,
                ]);

                // 2. Update enterprise sigle
                $enterprise->update(['sigle' => $newSigle]);

                // 3. Recode equipment if requested
                if ($recodeEquipements) {
                    $affectedCount = $this->recodeEquipementsForEnterprise(
                        $enterprise,
                        $userId ?: 0
                    );

                    // Update history with count
                    $historyEntry->update(['equipements_affected' => $affectedCount]);
                }

                return response()->json([
                    'success' => true,
                    'enterprise_id' => $enterprise->id,
                    'old_sigle' => $currentSigle,
                    'new_sigle' => $newSigle,
                    'equipements_recoded' => $recodeEquipements ? $historyEntry->equipements_affected : 0,
                    'sigle_history_id' => $historyEntry->id,
                    'changed_by' => $userId,
                    'changed_at' => $historyEntry->created_at?->toIso8601String(),
                ]);
            });
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du sigle: ' . $exception->getMessage(),
            ], 422);
        }
    }

    /**
     * Tâche 1.1.3 : Get sigle change history
     * GET /api/v1/enterprises/{id}/sigle/history
     */
    public function getSigleHistory(Enterprise $enterprise)
    {
        /** @var User|null $user */
        $user = request()->user();
        if (!$this->canManageEnterpriseSigle($user, $enterprise)) {
            return response()->json([
                'message' => 'Unauthorized access to enterprise data',
            ], 403);
        }

        $history = EnterpriseSigleHistory::query()
            ->where('enterprise_id', $enterprise->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($history);
    }

    private function canManageEnterpriseSigle(?User $user, Enterprise $enterprise): bool
    {
        if (!$user) {
            return false;
        }

        if ((string) $user->user_type === 'super_admin') {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        return (int) ($user->enterprise_id ?? 0) === (int) $enterprise->id;
    }
}
