<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\FormationHistory;
use App\Models\FormationProof;
use App\Models\TrainingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class FormationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.competences.read')->only(['index', 'show', 'stats']);
        $this->middleware('permission:support.competences.create')->only(['store', 'importFile']);
        $this->middleware('permission:support.competences.update')->only(['update', 'complete', 'reschedule', 'cancel', 'uploadProof', 'deleteProof']);
        $this->middleware('permission:support.competences.delete')->only(['destroy']);
    }

    public function importFile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'year' => 'required|integer|min:2020|max:2100',
            'target_user_ids' => 'required|array|min:1',
            'target_user_ids.*' => 'exists:users,id',
        ]);

        $targetUsers = User::query()
            ->whereIn('id', $validated['target_user_ids'])
            ->get(['id', 'enterprise_id', 'site_id', 'first_name', 'last_name', 'name', 'email']);

        if ($targetUsers->count() !== count($validated['target_user_ids'])) {
            return response()->json([
                'message' => 'Un ou plusieurs collaborateurs sélectionnés sont introuvables'
            ], 422);
        }

        if ($user->enterprise_id) {
            $invalidUser = $targetUsers->first(fn (User $targetUser) => (int) $targetUser->enterprise_id !== (int) $user->enterprise_id);
            if ($invalidUser) {
                return response()->json([
                    'message' => 'Un ou plusieurs collaborateurs sélectionnés ne sont pas dans votre entreprise'
                ], 422);
            }
        }

        $targetLabels = $targetUsers->map(function (User $rowUser) {
            $fullName = trim(($rowUser->first_name ?? '') . ' ' . ($rowUser->last_name ?? ''));
            return $fullName !== '' ? $fullName : ($rowUser->name ?: $rowUser->email);
        })->values()->all();

        $path = $validated['file']->store('imports/formations');

        try {
            $rows = $this->extractFormationRowsFromFile(Storage::path($path), (int) $validated['year']);
            if (count($rows) === 0) {
                return response()->json([
                    'message' => 'Aucune ligne formation détectée dans le fichier.',
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'incomplete' => 0,
                ], 422);
            }

            $existingByNumero = Formation::query()
                ->where(function ($subQuery) use ($validated) {
                    $subQuery
                        ->whereYear('date_debut', (int) $validated['year'])
                        ->orWhere(function ($nested) use ($validated) {
                            $nested
                                ->whereNull('date_debut')
                                ->where('plan_year', (int) $validated['year']);
                        });
                })
                ->whereNotNull('numero')
                ->get()
                ->keyBy(fn (Formation $formation) => (int) $formation->numero);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $incomplete = 0;

            DB::transaction(function () use (&$created, &$updated, &$skipped, &$incomplete, $rows, $existingByNumero, $validated, $targetLabels, $user) {
                foreach ($rows as $row) {
                    if (empty($row['designation'])) {
                        $skipped++;
                        continue;
                    }

                    $incomingNumero = isset($row['numero']) ? (int) $row['numero'] : null;
                    $dateDebut = $row['dateDebut'] ?? null;
                    $dateFin = $row['dateFin'] ?? null;
                    $periodMode = ($row['periodMode'] ?? 'custom') === 'standard' ? 'standard' : 'custom';
                    $frequency = $periodMode === 'standard' ? 'mensuelle' : 'ponctuelle';
                    $periodLabel = $row['periodLabel'] ?? null;

                    if (!$dateDebut || !$dateFin) {
                        $incomplete++;
                        $skipped++;
                        continue;
                    }

                    try {
                        $this->enforcePeriodFrequencyConsistency(
                            $frequency,
                            $periodMode,
                            $dateDebut,
                            $dateFin
                        );
                    } catch (ValidationException) {
                        $skipped++;
                        continue;
                    }

                    $commonPayload = [
                        'designation' => $row['designation'],
                        'target_user_ids' => array_map('intval', $validated['target_user_ids']),
                        'chronogramme' => array_fill(0, 12, false),
                        'formateur' => $row['formateur'] ?? 'Non défini',
                        'date_debut' => $dateDebut,
                        'date_fin' => $dateFin,
                        'plan_year' => (int) $validated['year'],
                        'period_mode' => $periodMode,
                        'period_label' => $periodLabel,
                        'frequency' => $frequency,
                        'observations' => $row['observations'] ?? null,
                        'status' => $dateDebut ? Formation::STATUS_PLANIFIEE : Formation::STATUS_EN_ATTENTE,
                    ];

                    $existingFormation = $incomingNumero ? $existingByNumero->get($incomingNumero) : null;
                    if ($existingFormation) {
                        $existingFormation->update([
                            ...$commonPayload,
                            // Preserve previous behavior: updates recompute cibles from selected collaborators.
                            'cibles' => $targetLabels,
                        ]);
                        $existingFormation->alerts()->delete();
                        $this->createAlerts($existingFormation);
                        FormationHistory::create([
                            'formation_id' => $existingFormation->id,
                            'action' => 'updated',
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                        ]);
                        $updated++;
                        continue;
                    }

                    $createdFormation = Formation::create([
                        ...$commonPayload,
                        'cibles' => !empty($row['cibles']) ? $row['cibles'] : $targetLabels,
                        'created_by' => $user->id,
                    ]);
                    $this->createAlerts($createdFormation);
                    FormationHistory::create([
                        'formation_id' => $createdFormation->id,
                        'action' => 'created',
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                    ]);
                    $created++;
                }
            });

            $siteIds = $targetUsers->pluck('site_id')
                ->filter(fn ($siteId) => $siteId !== null)
                ->map(fn ($siteId) => (int) $siteId)
                ->unique()
                ->values();

            if ($siteIds->isEmpty()) {
                $this->syncTrainingPlanForContext((int) $user->enterprise_id, null, (int) $validated['year']);
            } else {
                foreach ($siteIds as $siteId) {
                    $this->syncTrainingPlanForContext((int) $user->enterprise_id, (int) $siteId, (int) $validated['year']);
                }
            }

            return response()->json([
                'message' => 'Import formations terminé.',
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'incomplete' => $incomplete,
            ]);
        } finally {
            Storage::delete($path);
        }
    }

    public function index(Request $request)
    {
        $query = Formation::query();

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->status) {
            $query->whereIn('status', (array) $request->status);
        }

        if ($request->frequency) {
            $query->whereIn('frequency', (array) $request->frequency);
        }

        if ($request->search) {
            $query->where('designation', 'like', "%{$request->search}%");
        }

        if ($request->year) {
            $year = (int) $request->year;
            $query->where(function ($subQuery) use ($year) {
                $subQuery
                    ->whereYear('date_debut', $year)
                    ->orWhere(function ($nested) use ($year) {
                        $nested->whereNull('date_debut')->where('plan_year', $year);
                    });
            });
        }

        $formations = $query->orderBy('numero')->get();

        return response()->json(
            $formations->map(fn (Formation $formation) => $this->serializeFormation($formation))
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'designation' => 'required|string',
            'target_user_ids' => 'required|array|min:1',
            'target_user_ids.*' => 'exists:users,id',
            'cibles' => 'nullable|array',
            'chronogramme' => 'nullable|array|size:12',
            'formateur' => 'nullable|string',
            'formateur_user_id' => 'nullable|exists:users,id',
            'organizer_user_id' => 'nullable|exists:users,id',
            'process_id' => 'nullable|exists:processes,id|required_if:source,process-review',
            'source' => 'nullable|string',
            'dateDebut' => 'nullable|date|required_with:dateFin',
            'dateFin' => 'nullable|date|after_or_equal:dateDebut|required_with:dateDebut',
            'period_mode' => 'required|in:standard,custom',
            'period_label' => 'nullable|string|max:255',
            'frequency' => 'required|in:ponctuelle,annuelle,semestrielle,trimestrielle,mensuelle,biennale,sur_demande',
            'observations' => 'nullable|string',
            'site_id' => 'nullable|exists:sites,id',
            'plan_year' => 'nullable|integer|min:2020|max:2100',
            'generates_habilitation_type' => 'nullable|string|max:100',
            'habilitation_validity_months' => 'nullable|integer|min:1|max:120',
            'issuing_authority' => 'nullable|string|max:255'
        ]);

        $targetUsers = User::query()
            ->whereIn('id', $validated['target_user_ids'])
            ->get(['id', 'enterprise_id', 'first_name', 'last_name', 'name', 'email']);

        if ($targetUsers->count() !== count($validated['target_user_ids'])) {
            return response()->json([
                'message' => 'Un ou plusieurs collaborateurs sélectionnés sont introuvables'
            ], 422);
        }

        $validated['organizer_user_id'] = $validated['organizer_user_id'] ?? $user->id;

        $organizer = User::query()->find($validated['organizer_user_id']);
        if (!$organizer || ($user->enterprise_id && (int) $organizer->enterprise_id !== (int) $user->enterprise_id)) {
            return response()->json([
                'message' => 'L\'organisateur doit être un collaborateur de votre entreprise'
            ], 422);
        }

        if (!empty($validated['formateur_user_id'])) {
            $formateur = User::query()->find((int) $validated['formateur_user_id']);
            if (!$formateur || ($user->enterprise_id && (int) $formateur->enterprise_id !== (int) $user->enterprise_id)) {
                return response()->json([
                    'message' => 'Le formateur interne doit être un collaborateur de votre entreprise'
                ], 422);
            }
        }

        if ($user->enterprise_id) {
            $invalidUser = $targetUsers->first(fn (User $targetUser) => (int) $targetUser->enterprise_id !== (int) $user->enterprise_id);
            if ($invalidUser) {
                return response()->json([
                    'message' => 'Un ou plusieurs collaborateurs sélectionnés ne sont pas dans votre entreprise'
                ], 422);
            }
        }
        $targetLabels = $targetUsers->map(function (User $user) {
            $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            return $fullName !== '' ? $fullName : ($user->name ?: $user->email);
        })->values()->all();

        $dateDebut = $validated['dateDebut'] ?? null;
        $dateFin = $validated['dateFin'] ?? null;
        $this->enforcePeriodFrequencyConsistency(
            (string) $validated['frequency'],
            (string) $validated['period_mode'],
            $dateDebut,
            $dateFin,
        );
        $planYear = $validated['plan_year']
            ?? ($dateDebut ? Carbon::parse($dateDebut)->year : null);
        $periodLabel = $validated['period_label'] ?? null;
        if (!$dateDebut && !$dateFin && !$periodLabel) {
            $periodLabel = 'À compléter';
        }

        $formation = Formation::create([
            'designation' => $validated['designation'],
            'target_user_ids' => $validated['target_user_ids'],
            'cibles' => $validated['cibles'] ?? $targetLabels,
            'chronogramme' => $validated['chronogramme'] ?? array_fill(0, 12, false),
            'formateur' => $validated['formateur'] ?? 'Non défini',
            'formateur_user_id' => $validated['formateur_user_id'] ?? null,
            'organizer_user_id' => $validated['organizer_user_id'],
            'process_id' => $validated['process_id'] ?? null,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'plan_year' => $planYear,
            'period_mode' => $validated['period_mode'],
            'period_label' => $periodLabel,
            'frequency' => $validated['frequency'],
            'observations' => $validated['observations'] ?? null,
            'site_id' => $validated['site_id'] ?? null,
            'created_by' => $user->id,
            'generates_habilitation_type' => $validated['generates_habilitation_type'] ?? null,
            'habilitation_validity_months' => $validated['habilitation_validity_months'] ?? null,
            'issuing_authority' => $validated['issuing_authority'] ?? null,
            'status' => $dateDebut ? Formation::STATUS_PLANIFIEE : Formation::STATUS_EN_ATTENTE,
        ]);

        FormationHistory::create([
            'formation_id' => $formation->id,
            'action' => 'created',
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        $this->createAlerts($formation);

        $formation->load(['proofs', 'history', 'alerts']);
        $this->syncTrainingPlanForFormation($formation);

        return response()->json($this->serializeFormation($formation), 201);
    }

    public function show(Formation $formation)
    {
        $formation->load(['proofs', 'history', 'alerts']);

        return response()->json($this->serializeFormation($formation));
    }

    public function update(Request $request, Formation $formation)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $previousYear = $formation->plan_year ?? optional($formation->date_debut)?->year;
        $previousSiteId = $formation->site_id;
        $previousEnterpriseId = $formation->enterprise_id;

        $normalized = $request->all();
        if (isset($normalized['dateDebut']) && !isset($normalized['date_debut'])) {
            $normalized['date_debut'] = $normalized['dateDebut'];
        }
        if (isset($normalized['dateFin']) && !isset($normalized['date_fin'])) {
            $normalized['date_fin'] = $normalized['dateFin'];
        }

        $validated = validator($normalized, [
            'designation' => 'string',
            'target_user_ids' => 'array|min:1',
            'target_user_ids.*' => 'exists:users,id',
            'cibles' => 'array',
            'chronogramme' => 'array|size:12',
            'formateur' => 'nullable|string',
            'formateur_user_id' => 'nullable|exists:users,id',
            'organizer_user_id' => 'sometimes|required|exists:users,id',
            'process_id' => 'nullable|exists:processes,id|required_if:source,process-review',
            'source' => 'nullable|string',
            'date_debut' => 'nullable|date|required_with:date_fin',
            'date_fin' => 'nullable|date|after_or_equal:date_debut|required_with:date_debut',
            'period_mode' => 'in:standard,custom',
            'period_label' => 'nullable|string|max:255',
            'frequency' => 'in:ponctuelle,annuelle,semestrielle,trimestrielle,mensuelle,biennale,sur_demande',
            'observations' => 'nullable|string',
            'plan_year' => 'nullable|integer|min:2020|max:2100',
        ])->validate();

        if (array_key_exists('target_user_ids', $validated)) {
            $targetUsers = User::query()
                ->whereIn('id', $validated['target_user_ids'])
                ->get(['id', 'enterprise_id', 'first_name', 'last_name', 'name', 'email']);

            if ($targetUsers->count() !== count($validated['target_user_ids'])) {
                return response()->json([
                    'message' => 'Un ou plusieurs collaborateurs sélectionnés sont introuvables'
                ], 422);
            }

            if ($user->enterprise_id) {
                $invalidUser = $targetUsers->first(fn (User $targetUser) => (int) $targetUser->enterprise_id !== (int) $user->enterprise_id);
                if ($invalidUser) {
                    return response()->json([
                        'message' => 'Un ou plusieurs collaborateurs sélectionnés ne sont pas dans votre entreprise'
                    ], 422);
                }
            }

            $validated['cibles'] = $targetUsers->map(function (User $user) {
                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                return $fullName !== '' ? $fullName : ($user->name ?: $user->email);
            })->values()->all();
        }

        if (array_key_exists('organizer_user_id', $validated)) {
            $organizer = User::query()->find((int) $validated['organizer_user_id']);
            if (!$organizer || ($user->enterprise_id && (int) $organizer->enterprise_id !== (int) $user->enterprise_id)) {
                return response()->json([
                    'message' => 'L\'organisateur doit être un collaborateur de votre entreprise'
                ], 422);
            }
        }

        if (array_key_exists('formateur_user_id', $validated) && !empty($validated['formateur_user_id'])) {
            $formateur = User::query()->find((int) $validated['formateur_user_id']);
            if (!$formateur || ($user->enterprise_id && (int) $formateur->enterprise_id !== (int) $user->enterprise_id)) {
                return response()->json([
                    'message' => 'Le formateur interne doit être un collaborateur de votre entreprise'
                ], 422);
            }
        }

        if (array_key_exists('date_debut', $validated) && !empty($validated['date_debut']) && !array_key_exists('plan_year', $validated)) {
            $validated['plan_year'] = Carbon::parse($validated['date_debut'])->year;
        }

        $effectiveFrequency = (string) ($validated['frequency'] ?? $formation->frequency ?? 'ponctuelle');
        $effectivePeriodMode = (string) ($validated['period_mode'] ?? $formation->period_mode ?? 'custom');
        $effectiveStartDate = array_key_exists('date_debut', $validated)
            ? $validated['date_debut']
            : optional($formation->date_debut)?->format('Y-m-d');
        $effectiveEndDate = array_key_exists('date_fin', $validated)
            ? $validated['date_fin']
            : optional($formation->date_fin)?->format('Y-m-d');
        $this->enforcePeriodFrequencyConsistency(
            $effectiveFrequency,
            $effectivePeriodMode,
            $effectiveStartDate,
            $effectiveEndDate,
        );

        if (array_key_exists('date_debut', $validated) && empty($validated['date_debut'])) {
            if (!array_key_exists('status', $validated)) {
                $validated['status'] = Formation::STATUS_EN_ATTENTE;
            }
        } elseif (array_key_exists('date_debut', $validated) && !empty($validated['date_debut'])) {
            if (!array_key_exists('status', $validated) && $formation->status === Formation::STATUS_EN_ATTENTE) {
                $validated['status'] = Formation::STATUS_PLANIFIEE;
            }
        }

        if (array_key_exists('formateur', $validated) && $validated['formateur'] === null) {
            $validated['formateur'] = $formation->formateur ?: 'Non défini';
        }

        $formation->update($validated);

        if (array_key_exists('date_debut', $validated) || array_key_exists('date_fin', $validated)) {
            $formation->alerts()->delete();
            $this->createAlerts($formation);
        }

        FormationHistory::create([
            'formation_id' => $formation->id,
            'action' => 'updated',
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        $formation = $formation->fresh(['proofs', 'history', 'alerts']);
        if ($previousYear) {
            $this->syncTrainingPlanForContext((int) $previousEnterpriseId, $previousSiteId ? (int) $previousSiteId : null, (int) $previousYear);
        }
        $this->syncTrainingPlanForFormation($formation);

        return response()->json($this->serializeFormation($formation));
    }

    public function destroy(Formation $formation)
    {
        if (in_array($formation->status, [Formation::STATUS_REALISEE, Formation::STATUS_ANNULEE], true)) {
            return response()->json([
                'message' => 'Suppression impossible: la formation est réalisée ou annulée'
            ], 422);
        }

        $previousYear = $formation->plan_year ?? optional($formation->date_debut)?->year;
        $previousSiteId = $formation->site_id;
        $previousEnterpriseId = $formation->enterprise_id;

        $formation->delete();
        if ($previousYear) {
            $this->syncTrainingPlanForContext((int) $previousEnterpriseId, $previousSiteId ? (int) $previousSiteId : null, (int) $previousYear);
        }
        return response()->json(null, 204);
    }

    public function complete(Request $request, Formation $formation)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        if (!$formation->canBeCompleted()) {
            return response()->json([
                'message' => 'Cette formation ne peut pas être clôturée dans son état actuel'
            ], 422);
        }

        $request->validate([
            'proofs' => 'nullable|array',
            'proofs.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:users,id'
        ]);

        DB::transaction(function () use ($request, $formation, $user) {
            $uploadedProofs = $request->file('proofs', []);
            foreach ($uploadedProofs as $file) {
                if (!$file) {
                    continue;
                }

                $path = $file->store('formations/proofs', 'public');
                FormationProof::create([
                    'formation_id' => $formation->id,
                    'filename' => $file->getClientOriginalName(),
                    'url' => Storage::url($path),
                    'uploaded_by' => $user->id,
                ]);
            }

            $formation->update(['status' => Formation::STATUS_REALISEE]);

            // Generate habilitations for participants if formation is qualifying
            $generatedCount = 0;
            $participantIds = $request->participants ?? $formation->target_user_ids ?? [];
            if ($formation->isQualifying() && !empty($participantIds)) {
                foreach ($participantIds as $userId) {
                    $user = User::find($userId);
                    if ($user && $formation->generateHabilitation($user)) {
                        $generatedCount++;
                    }
                }
            }

            FormationHistory::create([
                'formation_id' => $formation->id,
                'action' => 'completed',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'comment' => $generatedCount > 0 ? "{$generatedCount} habilitation(s) générée(s)" : null,
            ]);
        });

        $formation = $formation->fresh(['proofs', 'history', 'alerts']);
        $this->syncTrainingPlanForFormation($formation);

        return response()->json($this->serializeFormation($formation));
    }

    public function reschedule(Request $request, Formation $formation)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        if (!$formation->canBeRescheduled()) {
            return response()->json([
                'message' => 'Cette formation ne peut pas être replanifiée dans son état actuel'
            ], 422);
        }

        $validated = $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'comment' => 'nullable|string',
        ]);

        $previousDate = $formation->date_debut;

        $formation->update([
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'status' => Formation::STATUS_REPLANIFIEE,
            'plan_year' => Carbon::parse($validated['date_debut'])->year,
        ]);

        FormationHistory::create([
            'formation_id' => $formation->id,
            'action' => 'rescheduled',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'comment' => $validated['comment'] ?? null,
            'previous_date' => $previousDate,
            'new_date' => $validated['date_debut'],
        ]);

        $formation->alerts()->delete();
        $this->createAlerts($formation);

        $formation = $formation->fresh(['proofs', 'history', 'alerts']);
        $this->syncTrainingPlanForFormation($formation);

        return response()->json($this->serializeFormation($formation));
    }

    public function cancel(Request $request, Formation $formation)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        if (!$formation->canBeCancelled()) {
            return response()->json([
                'message' => 'Cette formation ne peut pas être annulée dans son état actuel'
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if ($formation->date_debut) {
            $nextYearStart = $formation->date_debut->copy()->addYear()->startOfYear();
            if (now()->lt($nextYearStart)) {
                return response()->json([
                    'message' => "Annulation autorisée à partir du {$nextYearStart->format('d/m/Y')}"
                ], 422);
            }
        }

        $formation->update(['status' => Formation::STATUS_ANNULEE]);

        FormationHistory::create([
            'formation_id' => $formation->id,
            'action' => 'cancelled',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'comment' => $validated['reason'],
        ]);

        $formation = $formation->fresh(['proofs', 'history', 'alerts']);
        $this->syncTrainingPlanForFormation($formation);

        return response()->json($this->serializeFormation($formation));
    }

    public function uploadProof(Request $request, Formation $formation)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'proof' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $file = $request->file('proof');
        $path = $file->store('formations/proofs', 'public');

        $proof = FormationProof::create([
            'formation_id' => $formation->id,
            'filename' => $file->getClientOriginalName(),
            'url' => Storage::url($path),
            'uploaded_by' => $user->id,
        ]);

        return response()->json($proof, 201);
    }

    public function deleteProof(Formation $formation, FormationProof $proof)
    {
        if ($proof->formation_id !== $formation->id) {
            return response()->json(['message' => 'Preuve invalide pour cette formation'], 422);
        }

        Storage::disk('public')->delete(str_replace('/storage/', '', $proof->url));
        $proof->delete();
        return response()->json(null, 204);
    }

    public function stats(Request $request)
    {
        $query = Formation::query();

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        $formations = $query->get();

        return [
            'total' => $formations->count(),
            'planifiees' => $formations->filter(fn (Formation $f) => $f->runtimeStatus() === Formation::STATUS_PLANIFIEE)->count(),
            'realisees' => $formations->where('status', Formation::STATUS_REALISEE)->count(),
            'enRetard' => $formations->filter(fn (Formation $f) => $f->runtimeStatus() === Formation::STATUS_EN_ATTENTE)->count(),
            'annulees' => $formations->where('status', Formation::STATUS_ANNULEE)->count(),
            'tauxRealisation' => $formations->count() > 0 
                ? round(($formations->where('status', Formation::STATUS_REALISEE)->count() / $formations->count()) * 100)
                : 0,
            'budgetTotal' => $formations->sum('cout'),
            'budgetConsomme' => $formations->where('status', Formation::STATUS_REALISEE)->sum('cout'),
        ];
    }

    private function serializeFormation(Formation $formation): array
    {
        $data = $formation->toArray();
        $runtimeStatus = $formation->runtimeStatus();
        $data['status'] = $runtimeStatus;
        $data['alert_state'] = $this->resolveAlertState($formation, $runtimeStatus);
        $targetIds = $formation->target_user_ids ?? [];
        $data['targets'] = User::query()
            ->whereIn('id', $targetIds)
            ->get(['id', 'first_name', 'last_name', 'name', 'email'])
            ->map(function (User $user) {
                $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                return [
                    'id' => $user->id,
                    'name' => $fullName !== '' ? $fullName : ($user->name ?: $user->email),
                    'email' => $user->email,
                ];
            })
            ->values();

        return $data;
    }

    private function resolveAlertState(Formation $formation, string $runtimeStatus): string
    {
        if (in_array($formation->status, [Formation::STATUS_REALISEE, Formation::STATUS_ANNULEE], true)) {
            return 'resolved';
        }

        if ($runtimeStatus === Formation::STATUS_EN_ATTENTE) {
            return 'expired';
        }

        return 'active';
    }

    private function createAlerts(Formation $formation)
    {
        $dateDebut = $formation->date_debut;
        if (!$dateDebut) {
            return;
        }
        $alerts = [
            ['type' => 'J-30', 'days' => 30],
            ['type' => 'J-15', 'days' => 15],
            ['type' => 'J-7', 'days' => 7],
            ['type' => 'J-3', 'days' => 3],
            ['type' => 'J-1', 'days' => 1],
        ];

        foreach ($alerts as $alert) {
            $formation->alerts()->create([
                'type' => $alert['type'],
                'date' => $dateDebut->copy()->subDays($alert['days']),
            ]);
        }
    }

    private function syncTrainingPlanForFormation(Formation $formation): void
    {
        $year = $formation->plan_year ?? optional($formation->date_debut)?->year;
        if (!$year || !$formation->enterprise_id) {
            return;
        }

        $this->syncTrainingPlanForContext(
            (int) $formation->enterprise_id,
            $formation->site_id ? (int) $formation->site_id : null,
            (int) $year
        );
    }

    private function syncTrainingPlanForContext(int $enterpriseId, ?int $siteId, int $year): void
    {
        $plan = TrainingPlan::query()->firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'site_id' => $siteId,
                'year' => $year,
            ],
            [
                'status' => 'active',
                'total_budget' => null,
                'planned_formations' => 0,
                'spent_amount' => 0,
            ]
        );

        $formations = Formation::query()
            ->where('enterprise_id', $enterpriseId)
            ->when($siteId !== null, fn ($query) => $query->where('site_id', $siteId))
            ->where(function ($query) use ($year) {
                $query->whereYear('date_debut', $year)
                    ->orWhere(function ($nested) use ($year) {
                        $nested->whereNull('date_debut')->where('plan_year', $year);
                    });
            })
            ->get(['id', 'status', 'cout']);

        $spentAmount = (float) $formations
            ->where('status', Formation::STATUS_REALISEE)
            ->sum('cout');

        $plan->update([
            'planned_formations' => $formations->count(),
            'spent_amount' => $spentAmount,
        ]);
    }

    private function extractFormationRowsFromFile(string $filePath, int $year): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rawRows = $worksheet->toArray(null, true, true, false);

        $headerIndex = $this->findFormationHeaderRowIndex($rawRows);
        if ($headerIndex < 0) {
            return [];
        }

        $headerRow = $rawRows[$headerIndex] ?? [];
        $monthRow = $rawRows[$headerIndex + 1] ?? [];
        $headerMap = $this->buildFormationHeaderMap($headerRow);
        $dateSuiviIndex = $headerMap['date_suivi'];
        $headerMap['date_debut'] = $this->findMonthRowMarkerIndex($monthRow, 'debut', $dateSuiviIndex);
        $headerMap['date_fin'] = $this->findMonthRowMarkerIndex($monthRow, 'fin', $dateSuiviIndex);
        $monthIndexes = $this->extractMonthIndexes($monthRow);
        $dataStart = count($monthIndexes) > 0 ? $headerIndex + 2 : $headerIndex + 1;
        $dataRows = array_slice($rawRows, $dataStart);

        $results = [];
        $seenNumeros = [];

        foreach ($dataRows as $row) {
            $designation = $this->readStringByIndex($row, $headerMap['designation']);
            if ($designation === '') {
                continue;
            }

            $numeroRaw = $this->readCellByIndex($row, $headerMap['numero']);
            $numero = is_numeric((string) $numeroRaw) ? (int) $numeroRaw : null;
            if ($numero !== null) {
                if (isset($seenNumeros[$numero])) {
                    continue;
                }
                $seenNumeros[$numero] = true;
            }

            $monthsChecked = [];
            foreach ($monthIndexes as $idx => $monthColumn) {
                $value = $this->readCellByIndex($row, $monthColumn);
                if ($value !== null && trim((string) $value) !== '') {
                    $monthsChecked[] = $idx;
                }
            }

            $period = $this->resolvePeriodFromCells(
                $year,
                $this->readCellByIndex($row, $headerMap['date_debut']),
                $this->readCellByIndex($row, $headerMap['date_fin']),
                $monthsChecked,
            );

            $ciblesRaw = $this->readStringByIndex($row, $headerMap['cibles']);
            $cibles = array_values(array_filter(array_map('trim', preg_split('/[;,]/', $ciblesRaw ?: '') ?: [])));

            $coutRaw = $this->readCellByIndex($row, $headerMap['cout']);
            $cout = is_numeric((string) $coutRaw) ? (float) $coutRaw : null;

            $results[] = [
                'numero' => $numero,
                'designation' => $designation,
                'cibles' => $cibles,
                'formateur' => $this->readStringByIndex($row, $headerMap['formateur']) ?: 'Non défini',
                'cout' => $cout,
                'dateDebut' => $period['dateDebut'],
                'dateFin' => $period['dateFin'],
                'periodMode' => $period['periodMode'],
                'periodLabel' => $period['periodLabel'],
                'observations' => $this->readStringByIndex($row, $headerMap['observations']) ?: null,
            ];
        }

        return $results;
    }

    private function enforcePeriodFrequencyConsistency(
        string $frequency,
        string $periodMode,
        ?string $dateDebut,
        ?string $dateFin
    ): void {
        $isPonctuelle = $frequency === 'ponctuelle';
        $isRecurrente = !$isPonctuelle;

        if ($isPonctuelle && $periodMode !== 'custom') {
            throw ValidationException::withMessages([
                'period_mode' => 'Une formation ponctuelle doit utiliser une période personnalisée.',
            ]);
        }

        if ($isRecurrente && $periodMode !== 'standard') {
            throw ValidationException::withMessages([
                'period_mode' => 'Une formation récurrente doit utiliser une période standard.',
            ]);
        }

        if ($isPonctuelle && (!$dateDebut || !$dateFin)) {
            throw ValidationException::withMessages([
                'dateDebut' => 'Une formation ponctuelle nécessite une date de début et une date de fin.',
            ]);
        }

        if ($isRecurrente && !$dateDebut) {
            throw ValidationException::withMessages([
                'dateDebut' => 'Une formation récurrente nécessite au minimum une date de référence.',
            ]);
        }
    }

    private function findFormationHeaderRowIndex(array $rows): int
    {
        foreach ($rows as $index => $row) {
            foreach ($row as $cell) {
                $normalized = $this->normalizeImportText((string) $cell);
                if ($normalized === 'n' || $normalized === 'no' || $normalized === 'numero') {
                    return (int) $index;
                }
            }
        }

        return -1;
    }

    private function buildFormationHeaderMap(array $headerRow): array
    {
        $findByLabels = function (array $labels, ?int $fallback = null) use ($headerRow): ?int {
            foreach ($headerRow as $index => $cell) {
                $normalized = $this->normalizeImportText((string) $cell);
                foreach ($labels as $label) {
                    if ($normalized === $this->normalizeImportText($label)) {
                        return (int) $index;
                    }
                }
            }
            return $fallback;
        };

        return [
            'numero' => $findByLabels(['n°', 'no', 'numero'], 0),
            'designation' => $findByLabels([
                'nom de la formation',
                'designation/theme de la formation',
                'désignation/thème de la formation',
                'designation',
                'désignation',
            ], 1),
            'cibles' => $findByLabels(['cible(s)', 'cibles']),
            'formateur' => $findByLabels(['formateur'], 3),
            'cout' => $findByLabels(['coût', 'cout']),
            'date_suivi' => $findByLabels(['date suivi']),
            'observations' => $findByLabels([
                'obsevations/commentaire',
                'observations/commentaire',
                'observations',
                'commentaire',
            ]),
            'date_debut' => null,
            'date_fin' => null,
        ];
    }

    private function extractMonthIndexes(array $monthRow): array
    {
        $monthNames = ['jan', 'fev', 'mar', 'avr', 'mai', 'juin', 'juil', 'aout', 'sept', 'oct', 'nov', 'dec'];
        $indexes = [];
        foreach ($monthNames as $name) {
            $found = null;
            foreach ($monthRow as $index => $cell) {
                if ($this->normalizeImportText((string) $cell) === $name) {
                    $found = (int) $index;
                    break;
                }
            }
            if ($found !== null) {
                $indexes[] = $found;
            }
        }

        return $indexes;
    }

    private function findMonthRowMarkerIndex(array $monthRow, string $marker, ?int $afterIndex = null): ?int
    {
        $normalizedMarker = $this->normalizeImportText($marker);
        foreach ($monthRow as $index => $cell) {
            if ($afterIndex !== null && $index <= $afterIndex) {
                continue;
            }
            if ($this->normalizeImportText((string) $cell) === $normalizedMarker) {
                return (int) $index;
            }
        }

        return null;
    }

    private function resolvePeriodFromCells(int $year, mixed $startCell, mixed $endCell, array $monthsChecked): array
    {
        $startDate = $this->parseImportDate($startCell);
        $endDate = $this->parseImportDate($endCell);

        if ($startDate && $endDate) {
            return [
                'dateDebut' => $startDate->format('Y-m-d'),
                'dateFin' => $endDate->format('Y-m-d'),
                'periodMode' => 'custom',
                'periodLabel' => $this->formatPeriodLabel($startDate, $endDate),
            ];
        }

        if (count($monthsChecked) > 0) {
            $startMonth = min($monthsChecked);
            $endMonth = max($monthsChecked);
            $start = Carbon::create($year, $startMonth + 1, 1)->startOfDay();
            $end = Carbon::create($year, $endMonth + 1, 1)->endOfMonth()->startOfDay();
            $single = count($monthsChecked) === 1;

            return [
                'dateDebut' => $start->format('Y-m-d'),
                'dateFin' => $end->format('Y-m-d'),
                'periodMode' => $single ? 'standard' : 'custom',
                'periodLabel' => $single
                    ? $this->monthLabel($startMonth) . ' ' . $year
                    : $this->monthLabel($startMonth) . ' - ' . $this->monthLabel($endMonth) . ' ' . $year,
            ];
        }

        return [
            'dateDebut' => null,
            'dateFin' => null,
            'periodMode' => 'custom',
            'periodLabel' => 'À compléter',
        ];
    }

    private function monthLabel(int $monthIndex): string
    {
        $months = ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aout', 'Sept', 'Oct', 'Nov', 'Dec'];
        return $months[$monthIndex] ?? 'Mois';
    }

    private function formatPeriodLabel(Carbon $start, Carbon $end): string
    {
        if ($start->month === $end->month && $start->year === $end->year) {
            return $this->monthLabel($start->month - 1) . ' ' . $start->year;
        }

        return $this->monthLabel($start->month - 1) . ' ' . $start->year . ' - '
            . $this->monthLabel($end->month - 1) . ' ' . $end->year;
    }

    private function parseImportDate(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeImportText(string $value): string
    {
        $ascii = Str::ascii($value);
        $lower = Str::lower(trim($ascii));
        return preg_replace('/[^a-z0-9]/', '', $lower) ?? '';
    }

    private function readCellByIndex(array $row, ?int $index): mixed
    {
        if ($index === null || $index < 0) {
            return null;
        }

        return $row[$index] ?? null;
    }

    private function readStringByIndex(array $row, ?int $index): string
    {
        return trim((string) ($this->readCellByIndex($row, $index) ?? ''));
    }
}
