<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\CommunicationHistory;
use App\Models\CommunicationPlan;
use App\Models\CommunicationProof;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CommunicationsExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CommunicationController extends Controller
{
    public function importFile(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $path = $validated['file']->store('imports/communications');

        try {
            $rows = $this->extractCommunicationRowsFromFile(Storage::path($path), (int) $validated['year']);
            if (count($rows) === 0) {
                return response()->json([
                    'message' => 'Aucune ligne communication détectée dans le fichier.',
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'incomplete' => 0,
                ], 422);
            }

            $existingByNumero = Communication::query()
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
                ->keyBy(fn (Communication $communication) => (int) $communication->numero);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $incomplete = 0;

            DB::transaction(function () use (&$created, &$updated, &$skipped, &$incomplete, $rows, $existingByNumero, $validated, $user) {
                foreach ($rows as $row) {
                    if (empty($row['designation'])) {
                        $skipped++;
                        continue;
                    }

                    $incomingNumero = isset($row['numero']) ? (int) $row['numero'] : null;
                    $dateDebut = $row['dateDebut'] ?? null;
                    $dateFin = $row['dateFin'] ?? null;

                    if (!$dateDebut && !$dateFin) {
                        $incomplete++;
                    }

                    $commonPayload = [
                        'type' => in_array($row['type'] ?? 'communication', ['communication', 'sensibilisation'], true)
                            ? $row['type']
                            : 'communication',
                        'designation' => $row['designation'],
                        'cibles' => $row['cibles'] ?? ['Personnel'],
                        'moyens' => $row['moyens'] ?? ['Réunion'],
                        'chronogramme' => $row['chronogramme'] ?? array_fill(0, 12, false),
                        'responsable' => $row['responsable'] ?? 'Non défini',
                        'cout' => $row['cout'] ?? null,
                        'date_debut' => $dateDebut,
                        'date_fin' => $dateFin,
                        'period_mode' => $dateDebut && $dateFin ? 'custom' : 'standard',
                        'plan_year' => (int) $validated['year'],
                        'frequency' => 'ponctuelle',
                        'observations' => $row['observations'] ?? null,
                        'status' => $dateDebut ? 'planifiee' : 'en_attente',
                        'organizer_user_id' => $user->id,
                    ];

                    $existingCommunication = $incomingNumero ? $existingByNumero->get($incomingNumero) : null;
                    if ($existingCommunication) {
                        $this->enforcePeriodFrequencyConsistency(
                            (string) $commonPayload['frequency'],
                            (string) $commonPayload['period_mode'],
                            $dateDebut,
                            $dateFin
                        );
                        $existingCommunication->update($commonPayload);
                        $existingCommunication->alerts()->delete();
                        $this->createAlerts($existingCommunication);
                        CommunicationHistory::create([
                            'communication_id' => $existingCommunication->id,
                            'action' => 'updated',
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                        ]);
                        $updated++;
                        continue;
                    }

                    $this->enforcePeriodFrequencyConsistency(
                        (string) $commonPayload['frequency'],
                        (string) $commonPayload['period_mode'],
                        $dateDebut,
                        $dateFin
                    );
                    $createdCommunication = Communication::create([
                        ...$commonPayload,
                        'enterprise_id' => $user->enterprise_id,
                        'created_by' => $user->id,
                    ]);
                    $this->createAlerts($createdCommunication);
                    CommunicationHistory::create([
                        'communication_id' => $createdCommunication->id,
                        'action' => 'created',
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                    ]);
                    $created++;
                }
            });

            $this->syncCommunicationPlanForContext(
                (int) $user->enterprise_id,
                $user->site_id ? (int) $user->site_id : null,
                (int) $validated['year']
            );

            return response()->json([
                'message' => 'Import communications terminé.',
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
        $query = Communication::query();

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->status) {
            $query->whereIn('status', (array) $request->status);
        }

        if ($request->type) {
            $query->whereIn('type', (array) $request->type);
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

        return $query->orderBy('numero')->get();
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'type' => 'required|in:communication,sensibilisation',
            'designation' => 'required|string',
            'cibles' => 'required|array',
            'moyens' => 'required|array',
            'chronogramme' => 'required|array|size:12',
            'responsable' => 'nullable|string',
            'organizer_user_id' => 'required|exists:users,id',
            'responsible_user_id' => 'nullable|exists:users,id',
            'participant_user_ids' => 'nullable|array',
            'participant_user_ids.*' => 'exists:users,id',
            'process_id' => 'nullable|exists:processes,id|required_if:source,process-review',
            'source' => 'nullable|string',
            'cout' => 'nullable|numeric',
            'dateDebut' => 'nullable|date|required_with:dateFin',
            'dateFin' => 'nullable|date|after_or_equal:dateDebut|required_with:dateDebut',
            'period_mode' => 'required|in:standard,custom',
            'frequency' => 'required|in:ponctuelle,annuelle,semestrielle,trimestrielle,mensuelle,biennale,sur_demande',
            'observations' => 'nullable|string',
            'site_id' => 'nullable|exists:sites,id',
            'plan_year' => 'nullable|integer|min:2020|max:2100',
        ]);

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

        $organizer = User::query()->find((int) $validated['organizer_user_id']);
        if (!$organizer || ($user->enterprise_id && (int) $organizer->enterprise_id !== (int) $user->enterprise_id)) {
            return response()->json([
                'message' => 'L\'organisateur doit être un collaborateur de votre entreprise'
            ], 422);
        }

        if (!empty($validated['responsible_user_id'])) {
            $responsible = User::query()->find((int) $validated['responsible_user_id']);
            if (!$responsible || ($user->enterprise_id && (int) $responsible->enterprise_id !== (int) $user->enterprise_id)) {
                return response()->json([
                    'message' => 'Le responsable interne doit être un collaborateur de votre entreprise'
                ], 422);
            }
        }

        if (!empty($validated['participant_user_ids'])) {
            $participantCount = User::query()
                ->whereIn('id', $validated['participant_user_ids'])
                ->when($user->enterprise_id, fn ($q) => $q->where('enterprise_id', $user->enterprise_id))
                ->count();
            if ($participantCount !== count($validated['participant_user_ids'])) {
                return response()->json([
                    'message' => 'Tous les participants doivent être des collaborateurs de votre entreprise'
                ], 422);
            }
        }

        $communication = Communication::create([
            'enterprise_id' => $user->enterprise_id,
            'type' => $validated['type'],
            'designation' => $validated['designation'],
            'cibles' => $validated['cibles'],
            'moyens' => $validated['moyens'],
            'chronogramme' => $validated['chronogramme'],
            'responsable' => $validated['responsable'] ?? 'Non défini',
            'organizer_user_id' => $validated['organizer_user_id'],
            'responsible_user_id' => $validated['responsible_user_id'] ?? null,
            'participant_user_ids' => $validated['participant_user_ids'] ?? null,
            'process_id' => $validated['process_id'] ?? null,
            'cout' => $validated['cout'] ?? null,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'period_mode' => $validated['period_mode'],
            'plan_year' => $planYear,
            'frequency' => $validated['frequency'],
            'observations' => $validated['observations'] ?? null,
            'site_id' => $validated['site_id'] ?? null,
            'created_by' => $user->id,
            'status' => $dateDebut ? 'planifiee' : 'en_attente',
        ]);

        CommunicationHistory::create([
            'communication_id' => $communication->id,
            'action' => 'created',
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        $this->createAlerts($communication);

        $communication = $communication->load(['proofs', 'history', 'alerts']);
        $this->syncCommunicationPlanForCommunication($communication);
        return response()->json($communication, 201);
    }

    public function show(Communication $communication)
    {
        return $communication->load(['proofs', 'history', 'alerts']);
    }

    public function update(Request $request, Communication $communication)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'type' => 'in:communication,sensibilisation',
            'designation' => 'string',
            'cibles' => 'array',
            'moyens' => 'array',
            'chronogramme' => 'array|size:12',
            'responsable' => 'nullable|string',
            'organizer_user_id' => 'sometimes|required|exists:users,id',
            'responsible_user_id' => 'nullable|exists:users,id',
            'participant_user_ids' => 'nullable|array',
            'participant_user_ids.*' => 'exists:users,id',
            'process_id' => 'nullable|exists:processes,id|required_if:source,process-review',
            'source' => 'nullable|string',
            'cout' => 'nullable|numeric',
            'dateDebut' => 'nullable|date|required_with:dateFin',
            'dateFin' => 'nullable|date|after_or_equal:dateDebut|required_with:dateDebut',
            'period_mode' => 'in:standard,custom',
            'frequency' => 'in:ponctuelle,annuelle,semestrielle,trimestrielle,mensuelle,biennale,sur_demande',
            'observations' => 'nullable|string',
            'plan_year' => 'nullable|integer|min:2020|max:2100',
        ]);

        if (array_key_exists('organizer_user_id', $validated)) {
            $organizer = User::query()->find((int) $validated['organizer_user_id']);
            if (!$organizer || ($user->enterprise_id && (int) $organizer->enterprise_id !== (int) $user->enterprise_id)) {
                return response()->json([
                    'message' => 'L\'organisateur doit être un collaborateur de votre entreprise'
                ], 422);
            }
        }

        if (array_key_exists('responsible_user_id', $validated) && !empty($validated['responsible_user_id'])) {
            $responsible = User::query()->find((int) $validated['responsible_user_id']);
            if (!$responsible || ($user->enterprise_id && (int) $responsible->enterprise_id !== (int) $user->enterprise_id)) {
                return response()->json([
                    'message' => 'Le responsable interne doit être un collaborateur de votre entreprise'
                ], 422);
            }
        }

        if (array_key_exists('participant_user_ids', $validated) && !empty($validated['participant_user_ids'])) {
            $participantCount = User::query()
                ->whereIn('id', $validated['participant_user_ids'])
                ->when($user->enterprise_id, fn ($q) => $q->where('enterprise_id', $user->enterprise_id))
                ->count();
            if ($participantCount !== count($validated['participant_user_ids'])) {
                return response()->json([
                    'message' => 'Tous les participants doivent être des collaborateurs de votre entreprise'
                ], 422);
            }
        }

        $updateData = [];
        if (isset($validated['type'])) $updateData['type'] = $validated['type'];
        if (isset($validated['designation'])) $updateData['designation'] = $validated['designation'];
        if (isset($validated['cibles'])) $updateData['cibles'] = $validated['cibles'];
        if (isset($validated['moyens'])) $updateData['moyens'] = $validated['moyens'];
        if (isset($validated['chronogramme'])) $updateData['chronogramme'] = $validated['chronogramme'];
        if (isset($validated['responsable'])) $updateData['responsable'] = $validated['responsable'];
        if (array_key_exists('responsable', $validated) && $validated['responsable'] === null) {
            $updateData['responsable'] = $communication->responsable ?: 'Non défini';
        }
        if (array_key_exists('organizer_user_id', $validated)) $updateData['organizer_user_id'] = $validated['organizer_user_id'];
        if (array_key_exists('responsible_user_id', $validated)) $updateData['responsible_user_id'] = $validated['responsible_user_id'];
        if (array_key_exists('participant_user_ids', $validated)) $updateData['participant_user_ids'] = $validated['participant_user_ids'];
        if (array_key_exists('process_id', $validated)) $updateData['process_id'] = $validated['process_id'];
        if (isset($validated['cout'])) $updateData['cout'] = $validated['cout'];
        if (isset($validated['dateDebut'])) $updateData['date_debut'] = $validated['dateDebut'];
        if (isset($validated['dateFin'])) $updateData['date_fin'] = $validated['dateFin'];
        if (isset($validated['period_mode'])) $updateData['period_mode'] = $validated['period_mode'];
        if (isset($validated['frequency'])) $updateData['frequency'] = $validated['frequency'];
        if (isset($validated['observations'])) $updateData['observations'] = $validated['observations'];
        if (isset($validated['plan_year'])) $updateData['plan_year'] = $validated['plan_year'];

        if (array_key_exists('date_debut', $updateData) && !empty($updateData['date_debut']) && !array_key_exists('plan_year', $updateData)) {
            $updateData['plan_year'] = Carbon::parse($updateData['date_debut'])->year;
        }
        $effectiveFrequency = (string) ($updateData['frequency'] ?? $communication->frequency ?? 'ponctuelle');
        $effectivePeriodMode = (string) ($updateData['period_mode'] ?? $communication->period_mode ?? 'custom');
        $effectiveStartDate = array_key_exists('date_debut', $updateData)
            ? $updateData['date_debut']
            : optional($communication->date_debut)?->format('Y-m-d');
        $effectiveEndDate = array_key_exists('date_fin', $updateData)
            ? $updateData['date_fin']
            : optional($communication->date_fin)?->format('Y-m-d');
        $this->enforcePeriodFrequencyConsistency(
            $effectiveFrequency,
            $effectivePeriodMode,
            $effectiveStartDate,
            $effectiveEndDate,
        );

        if (array_key_exists('date_debut', $updateData) && empty($updateData['date_debut'])) {
            $updateData['status'] = 'en_attente';
        } elseif (array_key_exists('date_debut', $updateData) && !empty($updateData['date_debut']) && $communication->status === 'en_attente') {
            $updateData['status'] = 'planifiee';
        }

        $communication->update($updateData);

        CommunicationHistory::create([
            'communication_id' => $communication->id,
            'action' => 'updated',
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        $communication = $communication->fresh(['proofs', 'history', 'alerts']);
        $this->syncCommunicationPlanForCommunication($communication);
        return $communication;
    }

    public function destroy(Communication $communication)
    {
        $previousYear = $communication->plan_year ?? optional($communication->date_debut)?->year;
        $previousSiteId = $communication->site_id;
        $previousEnterpriseId = $communication->enterprise_id;
        $communication->delete();
        if ($previousYear) {
            $this->syncCommunicationPlanForContext((int) $previousEnterpriseId, $previousSiteId ? (int) $previousSiteId : null, (int) $previousYear);
        }
        return response()->json(null, 204);
    }

    public function complete(Request $request, Communication $communication)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'proofs' => 'required|array|min:1',
            'proofs.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        foreach ($request->file('proofs') as $file) {
            $path = $file->store('communications/proofs', 'public');
            
            CommunicationProof::create([
                'communication_id' => $communication->id,
                'filename' => $file->getClientOriginalName(),
                'url' => Storage::url($path),
                'uploaded_by' => $user->id,
            ]);
        }

        $communication->update(['status' => 'realisee']);

        CommunicationHistory::create([
            'communication_id' => $communication->id,
            'action' => 'completed',
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        $communication = $communication->fresh(['proofs', 'history', 'alerts']);
        $this->syncCommunicationPlanForCommunication($communication);
        return $communication;
    }

    public function reschedule(Request $request, Communication $communication)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'comment' => 'nullable|string',
        ]);

        $previousDate = $communication->date_debut;

        $communication->update([
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'status' => 'replanifiee',
            'plan_year' => Carbon::parse($validated['date_debut'])->year,
        ]);

        CommunicationHistory::create([
            'communication_id' => $communication->id,
            'action' => 'rescheduled',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'comment' => $validated['comment'] ?? null,
            'previous_date' => $previousDate,
            'new_date' => $validated['date_debut'],
        ]);

        $communication->alerts()->delete();
        $this->createAlerts($communication);

        $communication = $communication->fresh(['proofs', 'history', 'alerts']);
        $this->syncCommunicationPlanForCommunication($communication);
        return $communication;
    }

    public function cancel(Request $request, Communication $communication)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        if ($communication->date_debut) {
            $year = $communication->date_debut->year;
            $endOfYear = now()->setYear($year)->endOfYear();

            if (now()->gt($endOfYear)) {
                return response()->json([
                    'message' => 'Impossible d\'annuler une communication après le 31/12 de l\'année planifiée'
                ], 422);
            }
        }

        $communication->update(['status' => 'annulee']);

        CommunicationHistory::create([
            'communication_id' => $communication->id,
            'action' => 'cancelled',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'comment' => $validated['reason'],
        ]);

        $communication = $communication->fresh(['proofs', 'history', 'alerts']);
        $this->syncCommunicationPlanForCommunication($communication);
        return $communication;
    }

    public function uploadProof(Request $request, Communication $communication)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'proof' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $file = $request->file('proof');
        $path = $file->store('communications/proofs', 'public');

        $proof = CommunicationProof::create([
            'communication_id' => $communication->id,
            'filename' => $file->getClientOriginalName(),
            'url' => Storage::url($path),
            'uploaded_by' => $user->id,
        ]);

        return response()->json($proof, 201);
    }

    public function deleteProof(Communication $communication, CommunicationProof $proof)
    {
        Storage::disk('public')->delete(str_replace('/storage/', '', $proof->url));
        $proof->delete();
        return response()->json(null, 204);
    }

    public function stats(Request $request)
    {
        $query = Communication::query();

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        $communications = $query->get();

        return [
            'total' => $communications->count(),
            'planifiees' => $communications->where('status', 'planifiee')->count(),
            'realisees' => $communications->where('status', 'realisee')->count(),
            'enRetard' => $communications->filter(function ($c) {
                return $c->date_debut && $c->date_debut->lt(now()) && $c->status === 'en_attente';
            })->count(),
            'annulees' => $communications->where('status', 'annulee')->count(),
            'tauxRealisation' => $communications->count() > 0 
                ? round(($communications->where('status', 'realisee')->count() / $communications->count()) * 100)
                : 0,
            'budgetTotal' => $communications->sum('cout'),
            'budgetConsomme' => $communications->where('status', 'realisee')->sum('cout'),
        ];
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $query = Communication::query();

        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->status) {
            $query->whereIn('status', (array) $request->status);
        }

        if ($request->type) {
            $query->whereIn('type', (array) $request->type);
        }

        $filename = 'communications_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $relativePath = 'exports/' . $filename;
        Excel::store(new CommunicationsExport($query), $relativePath, 'local');
        $tempPath = Storage::disk('local')->path($relativePath);

        $siteId = (int) ($request->integer('site_id') ?: ($user?->site_id ?? 0));
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'communication_plan_export_xlsx',
                'title' => 'Export plan de communication',
                'description' => 'Export XLSX du plan de communication et de sensibilisation.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => 'ENR',
            ]);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    public function template(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plan communication');

        $sheet->setCellValue('A2', 'PLAN DE COMMUNICATION ET DE SENSIBILISATION');
        $sheet->mergeCells('A2:T2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A4', 'Code :');
        $sheet->setCellValue('E4', 'Version :');
        $sheet->setCellValue('I4', 'Date :');

        $headerRow = 6;
        $subHeaderRow = 7;

        $sheet->setCellValue('A' . $headerRow, 'N°');
        $sheet->setCellValue('B' . $headerRow, 'Thème de la communication ou de la sensibilisation');
        $sheet->setCellValue('C' . $headerRow, 'Chronogramme - année');
        $sheet->setCellValue('O' . $headerRow, 'Responsable ou animation');
        $sheet->setCellValue('P' . $headerRow, 'Cibles');
        $sheet->setCellValue('Q' . $headerRow, 'Moyen de communication ou de sensibilisation');
        $sheet->setCellValue('R' . $headerRow, 'Date Suivi');

        $sheet->mergeCells("A{$headerRow}:A{$subHeaderRow}");
        $sheet->mergeCells("B{$headerRow}:B{$subHeaderRow}");
        $sheet->mergeCells("C{$headerRow}:N{$headerRow}");
        $sheet->mergeCells("O{$headerRow}:O{$subHeaderRow}");
        $sheet->mergeCells("P{$headerRow}:P{$subHeaderRow}");
        $sheet->mergeCells("Q{$headerRow}:Q{$subHeaderRow}");
        $sheet->mergeCells("R{$headerRow}:S{$headerRow}");

        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
        foreach ($months as $index => $label) {
            $column = chr(ord('C') + $index);
            $sheet->setCellValue($column . $subHeaderRow, $label);
        }
        $sheet->setCellValue('R' . $subHeaderRow, 'Début');
        $sheet->setCellValue('S' . $subHeaderRow, 'Fin');

        $sheet->getStyle("A{$headerRow}:S{$subHeaderRow}")->getFont()->setBold(true);
        $sheet->freezePane('A8');

        foreach (range('A', 'S') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = sprintf('template_plan_communication_%s.xlsx', now()->format('Ymd_His'));
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $user = $request->user();
        $siteId = (int) ($user?->site_id ?? 0);
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'communication_plan_template_xlsx',
                'title' => 'Modèle plan de communication',
                'description' => 'Modèle XLSX du plan de communication et de sensibilisation.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $user?->id,
                'type' => 'FOR',
            ]);
        }

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function createAlerts(Communication $communication)
    {
        $dateDebut = $communication->date_debut;
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
            $communication->alerts()->create([
                'type' => $alert['type'],
                'date' => $dateDebut->copy()->subDays($alert['days']),
            ]);
        }
    }

    private function syncCommunicationPlanForCommunication(Communication $communication): void
    {
        $year = $communication->plan_year ?? optional($communication->date_debut)?->year;
        if (!$year || !$communication->enterprise_id) {
            return;
        }

        $this->syncCommunicationPlanForContext(
            (int) $communication->enterprise_id,
            $communication->site_id ? (int) $communication->site_id : null,
            (int) $year
        );
    }

    private function syncCommunicationPlanForContext(int $enterpriseId, ?int $siteId, int $year): void
    {
        $plan = CommunicationPlan::query()->firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'site_id' => $siteId,
                'year' => $year,
            ],
            [
                'status' => 'active',
                'planned_budget' => null,
                'planned_actions' => 0,
                'spent_amount' => 0,
            ]
        );

        $communications = Communication::query()
            ->where('enterprise_id', $enterpriseId)
            ->when($siteId !== null, fn ($query) => $query->where('site_id', $siteId))
            ->where(function ($query) use ($year) {
                $query->whereYear('date_debut', $year)
                    ->orWhere(function ($nested) use ($year) {
                        $nested->whereNull('date_debut')->where('plan_year', $year);
                    });
            })
            ->get(['status', 'cout']);

        $spentAmount = (float) $communications
            ->where('status', 'realisee')
            ->sum('cout');

        $plan->update([
            'planned_actions' => $communications->count(),
            'spent_amount' => $spentAmount,
        ]);
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
                'period_mode' => 'Une communication ponctuelle doit utiliser une période personnalisée.',
            ]);
        }

        if ($isRecurrente && $periodMode !== 'standard') {
            throw ValidationException::withMessages([
                'period_mode' => 'Une communication récurrente doit utiliser une période standard.',
            ]);
        }

        if ($isPonctuelle && (!$dateDebut || !$dateFin)) {
            throw ValidationException::withMessages([
                'dateDebut' => 'Une communication ponctuelle nécessite une date de début et une date de fin.',
            ]);
        }

        if ($isRecurrente && !$dateDebut) {
            throw ValidationException::withMessages([
                'dateDebut' => 'Une communication récurrente nécessite au minimum une date de référence.',
            ]);
        }
    }

    private function extractCommunicationRowsFromFile(string $filePath, int $year): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rawRows = $worksheet->toArray(null, true, true, false);

        $headerIndex = $this->findCommunicationHeaderRowIndex($rawRows);
        if ($headerIndex < 0) {
            return [];
        }

        $headerRow = $rawRows[$headerIndex] ?? [];
        $monthRow = $rawRows[$headerIndex + 1] ?? [];
        $headerMap = $this->buildCommunicationHeaderMap($headerRow);
        $dateSuiviIndex = $headerMap['date_suivi'];
        $headerMap['date_debut'] = $this->findMonthRowMarkerIndex($monthRow, 'debut', $dateSuiviIndex);
        $headerMap['date_fin'] = $this->findMonthRowMarkerIndex($monthRow, 'fin', $dateSuiviIndex);
        $monthPositions = $this->extractMonthPositions($monthRow);
        $dataStart = count($monthPositions) > 0 ? $headerIndex + 2 : $headerIndex + 1;
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

            [$days, $monthsActive] = $this->extractMonthDays($row, $monthPositions, $year);
            $period = $this->resolveCommunicationPeriod(
                $year,
                $this->readCellByIndex($row, $headerMap['date_debut']),
                $this->readCellByIndex($row, $headerMap['date_fin']),
                $days,
                $monthsActive,
            );

            $coutRaw = $this->readCellByIndex($row, $headerMap['cout']);
            $cout = is_numeric((string) $coutRaw) ? (float) $coutRaw : null;
            $cibles = $this->normalizeTargets($this->readCellByIndex($row, $headerMap['cibles']));
            $moyens = $this->normalizeMoyens($this->readCellByIndex($row, $headerMap['moyens']));

            $results[] = [
                'numero' => $numero,
                'type' => $this->inferCommunicationType($designation),
                'designation' => $designation,
                'cibles' => $cibles,
                'moyens' => $moyens,
                'chronogramme' => $this->buildChronogrammeFromMonths($days, $monthsActive),
                'responsable' => $this->readStringByIndex($row, $headerMap['responsable']) ?: 'Non défini',
                'cout' => $cout,
                'dateDebut' => $period['dateDebut'],
                'dateFin' => $period['dateFin'],
                'observations' => $this->readStringByIndex($row, $headerMap['observations']) ?: null,
            ];
        }

        return $results;
    }

    private function findCommunicationHeaderRowIndex(array $rows): int
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

    private function buildCommunicationHeaderMap(array $headerRow): array
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
                'theme de la communication ou de la sensibilisation',
                'thème de la communication ou de la sensibilisation',
                'theme de la communication',
                'thème de la communication',
                'theme',
                'thème',
            ], 1),
            'responsable' => $findByLabels(['responsable ou animation', 'responsable'], 14),
            'cibles' => $findByLabels(['cibles', 'cible(s)'], 15),
            'moyens' => $findByLabels([
                'moyen de communication ou de sensibilisation',
                'moyen de communication',
                'moyens',
            ], 16),
            'date_suivi' => $findByLabels(['date suivi'], 17),
            'date_debut' => null,
            'date_fin' => null,
            'observations' => $findByLabels(['observations/commentaire', 'observations', 'commentaire']),
            'cout' => null,
        ];
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

    private function extractMonthPositions(array $monthRow): array
    {
        $monthNames = ['jan', 'fev', 'mar', 'avr', 'mai', 'juin', 'juil', 'aout', 'sept', 'oct', 'nov', 'dec'];
        $positions = [];

        foreach ($monthNames as $month => $name) {
            $found = null;
            foreach ($monthRow as $index => $cell) {
                if ($this->normalizeImportText((string) $cell) === $name) {
                    $found = (int) $index;
                    break;
                }
            }
            if ($found !== null) {
                $positions[] = ['month' => $month, 'index' => $found];
            }
        }

        return $positions;
    }

    private function extractMonthDays(array $row, array $monthPositions, int $year): array
    {
        $days = [];
        $monthsActive = [];

        foreach ($monthPositions as $position) {
            $month = (int) $position['month'];
            $index = (int) $position['index'];
            $value = $row[$index] ?? null;
            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $dayNumbers = $this->extractDayNumbers($value);
            if (count($dayNumbers) > 0) {
                foreach ($dayNumbers as $day) {
                    $date = Carbon::create($year, $month + 1, $day);
                    if ($date->month === $month + 1 && $date->day === $day) {
                        $days[] = $date->copy()->startOfDay();
                    }
                }
                $monthsActive[] = $month;
                continue;
            }

            $monthsActive[] = $month;
        }

        return [$days, $monthsActive];
    }

    private function extractDayNumbers(mixed $value): array
    {
        if (is_numeric($value)) {
            $day = (int) round((float) $value);
            return ($day >= 1 && $day <= 31) ? [$day] : [];
        }

        preg_match_all('/\d{1,2}/', (string) $value, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);
        return array_values(array_filter($numbers, fn ($item) => $item >= 1 && $item <= 31));
    }

    private function resolveCommunicationPeriod(
        int $year,
        mixed $startCell,
        mixed $endCell,
        array $dayEntries,
        array $monthsActive
    ): array {
        $startDate = $this->parseImportDate($startCell);
        $endDate = $this->parseImportDate($endCell);

        if ($startDate && $endDate) {
            return ['dateDebut' => $startDate->format('Y-m-d'), 'dateFin' => $endDate->format('Y-m-d')];
        }
        if ($startDate && !$endDate) {
            return ['dateDebut' => $startDate->format('Y-m-d'), 'dateFin' => $startDate->format('Y-m-d')];
        }
        if (!$startDate && $endDate) {
            return ['dateDebut' => $endDate->format('Y-m-d'), 'dateFin' => $endDate->format('Y-m-d')];
        }

        if (count($dayEntries) > 0) {
            usort($dayEntries, fn (Carbon $a, Carbon $b) => $a->getTimestamp() <=> $b->getTimestamp());
            return [
                'dateDebut' => $dayEntries[0]->format('Y-m-d'),
                'dateFin' => $dayEntries[count($dayEntries) - 1]->format('Y-m-d'),
            ];
        }

        if (count($monthsActive) > 0) {
            $startMonth = min($monthsActive);
            $endMonth = max($monthsActive);
            $start = Carbon::create($year, $startMonth + 1, 1)->startOfDay();
            $end = Carbon::create($year, $endMonth + 1, 1)->endOfMonth()->startOfDay();
            return ['dateDebut' => $start->format('Y-m-d'), 'dateFin' => $end->format('Y-m-d')];
        }

        return ['dateDebut' => null, 'dateFin' => null];
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

    private function inferCommunicationType(string $designation): string
    {
        return str_contains(Str::lower($designation), 'sensibilisation') ? 'sensibilisation' : 'communication';
    }

    private function normalizeTargets(mixed $raw): array
    {
        $allowed = ['Personnel', 'Clients', 'Fournisseurs', 'Direction', 'Parties intéressées', 'Autre'];
        $values = $raw
            ? array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string) $raw) ?: [])))
            : [];
        $normalized = array_map(fn ($item) => Str::lower($item), $values);
        $mappedByKeywords = [];
        foreach ($normalized as $value) {
            if (str_contains($value, 'personnel')) $mappedByKeywords[] = 'Personnel';
            if (str_contains($value, 'client')) $mappedByKeywords[] = 'Clients';
            if (str_contains($value, 'fournisseur')) $mappedByKeywords[] = 'Fournisseurs';
            if (str_contains($value, 'direction')) $mappedByKeywords[] = 'Direction';
            if (str_contains($value, 'partie')) $mappedByKeywords[] = 'Parties intéressées';
        }
        $mapped = array_values(array_filter($values, fn ($item) => in_array($item, $allowed, true)));
        $unique = array_values(array_unique(array_merge($mapped, $mappedByKeywords)));
        return count($unique) > 0 ? $unique : ['Personnel'];
    }

    private function normalizeMoyens(mixed $raw): array
    {
        $allowed = ['Réunion', 'Email', 'Affichage', 'Intranet/Site web', 'Newsletter', 'Formation', 'Atelier', 'Vidéo', 'Autre'];
        $values = $raw
            ? array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string) $raw) ?: [])))
            : [];
        $normalized = array_map(fn ($item) => Str::lower($item), $values);
        $mappedByKeywords = [];
        foreach ($normalized as $value) {
            if (str_contains($value, 'reunion') || str_contains($value, 'réunion') || str_contains($value, 'presentiel') || str_contains($value, 'présentiel')) $mappedByKeywords[] = 'Réunion';
            if (str_contains($value, 'email') || str_contains($value, 'mail')) $mappedByKeywords[] = 'Email';
            if (str_contains($value, 'affichage')) $mappedByKeywords[] = 'Affichage';
            if (str_contains($value, 'intranet') || str_contains($value, 'site')) $mappedByKeywords[] = 'Intranet/Site web';
            if (str_contains($value, 'newsletter')) $mappedByKeywords[] = 'Newsletter';
            if (str_contains($value, 'formation')) $mappedByKeywords[] = 'Formation';
            if (str_contains($value, 'atelier')) $mappedByKeywords[] = 'Atelier';
            if (str_contains($value, 'video') || str_contains($value, 'vidéo')) $mappedByKeywords[] = 'Vidéo';
        }
        $mapped = array_values(array_filter($values, fn ($item) => in_array($item, $allowed, true)));
        $unique = array_values(array_unique(array_merge($mapped, $mappedByKeywords)));
        return count($unique) > 0 ? $unique : ['Réunion'];
    }

    private function buildChronogrammeFromMonths(array $dayEntries, array $monthsActive): array
    {
        $months = array_fill(0, 12, false);
        foreach ($dayEntries as $date) {
            if ($date instanceof Carbon) {
                $months[$date->month - 1] = true;
            }
        }
        foreach ($monthsActive as $monthIndex) {
            if ($monthIndex >= 0 && $monthIndex <= 11) {
                $months[$monthIndex] = true;
            }
        }
        return $months;
    }
}
