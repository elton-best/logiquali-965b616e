<?php

namespace App\Modules\Evaluation\Controllers;

use App\Models\Document;
use App\Models\Site;
use App\Services\DocumentSyncService;
use App\Services\StakeholderDocxGenerator;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesGeneratedDocumentContext;
use App\Http\Resources\StakeholderResource;
use App\Http\Resources\DocumentResource;
use App\Models\Stakeholder;
use App\Models\User;
use Illuminate\Http\Request;

class StakeholderController extends Controller
{
    use ResolvesGeneratedDocumentContext;

    public function index(Request $request)
    {
        $query = Stakeholder::with(['site', 'responsible']);

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by relevance_degree
        if ($request->has('relevance_degree')) {
            $query->where('relevance_degree', $request->relevance_degree);
        }

        // Filter by site_id
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $stakeholders = $query->paginate(20);
        return StakeholderResource::collection($stakeholders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'type' => 'required|in:client,supplier,partner,regulator,employee,shareholder,other',
            'relevance_degree' => 'nullable|string',
            'needs_expectations' => 'nullable|string',
            'requirements' => 'nullable|string',
            'actions' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'responsible_id' => 'nullable|exists:users,id',
            'needs' => 'nullable|array',
            'needs.*.description' => 'nullable|string',
            'needs.*.priority' => 'nullable|string',
            'needs.*.requirements' => 'nullable|array',
            'needs.*.requirements.*.description' => 'nullable|string',
            'needs.*.requirements.*.type' => 'nullable|string',
            'needs.*.requirements.*.actions' => 'nullable|array',
            'needs.*.requirements.*.actions.*.description' => 'nullable|string',
            'needs.*.requirements.*.actions.*.responsible_user_id' => 'nullable|integer|exists:users,id',
            'needs.*.requirements.*.actions.*.deadline' => 'nullable|date',
            'name' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('needs', $validated)) {
            $validated['needs'] = $this->normalizeNeedsPayload($validated['needs']);
        }

        $stakeholder = Stakeholder::create($validated);
        return new StakeholderResource($stakeholder->load(['site', 'responsible']));
    }

    public function show(Stakeholder $stakeholder)
    {
        return new StakeholderResource($stakeholder->load(['site', 'responsible']));
    }

    public function update(Request $request, Stakeholder $stakeholder)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'type' => 'sometimes|in:client,supplier,partner,regulator,employee,shareholder,other',
            'relevance_degree' => 'nullable|string',
            'needs_expectations' => 'nullable|string',
            'requirements' => 'nullable|string',
            'actions' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'responsible_id' => 'nullable|exists:users,id',
            'needs' => 'nullable|array',
            'needs.*.description' => 'nullable|string',
            'needs.*.priority' => 'nullable|string',
            'needs.*.requirements' => 'nullable|array',
            'needs.*.requirements.*.description' => 'nullable|string',
            'needs.*.requirements.*.type' => 'nullable|string',
            'needs.*.requirements.*.actions' => 'nullable|array',
            'needs.*.requirements.*.actions.*.description' => 'nullable|string',
            'needs.*.requirements.*.actions.*.responsible_user_id' => 'nullable|integer|exists:users,id',
            'needs.*.requirements.*.actions.*.deadline' => 'nullable|date',
            'name' => 'nullable|string|max:255',
        ]);

        if (array_key_exists('needs', $validated)) {
            $validated['needs'] = $this->normalizeNeedsPayload($validated['needs']);
        }

        $stakeholder->update($validated);
        return new StakeholderResource($stakeholder->load(['site', 'responsible']));
    }

    public function destroy(Stakeholder $stakeholder)
    {
        $stakeholder->delete();
        return response()->json(null, 204);
    }

    /**
     * Export stakeholders as DOCX (Registre des Parties Intéressées)
     */
    public function exportDocx(Request $request)
    {
        try {
            $siteId = $request->get('site_id');
            if (!$siteId) {
                return response()->json(['message' => 'site_id requis'], 400);
            }
            $siteId = (int) $siteId;
            $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);
            
            $query = Stakeholder::query();
            if ($siteId) {
                $query->where('site_id', $siteId);
            }
            
            $stakeholders = $query->get();
            $site = $siteId ? \App\Models\Site::find($siteId) : null;
            $siteName = $site->name ?? 'Tous les sites';
            $enterprise = $site?->enterprise ?? $request->user()?->enterprise;
            
            $generator = new \App\Services\Docx\StakeholderDocxGenerator();
            $filePath = $generator->generate($stakeholders, $siteName, $enterprise);

            $document = null;
            if ($siteId) {
                $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                    'site_id' => $siteId,
                    'process_id' => $generationContext['process_id'],
                    'process_code' => $generationContext['process_code'],
                    'process_name' => $generationContext['process_name'],
                    'document_kind' => 'stakeholders_register',
                    'source_type' => 'stakeholders_register',
                    'source_id' => $siteId,
                    'title' => 'Registre des parties intéressées - ' . $siteName,
                    'description' => 'Registre des parties intéressées généré automatiquement.',
                    'file_source_path' => $filePath,
                    'created_by' => $request->user()?->id,
                    'type' => $generationContext['type'],
                    'force_new' => true,
                    'metadata' => $generationContext['metadata'],
                ]);
            }
            
            $filename = 'Registre_Parties_Interessees_' . now()->format('Y-m-d') . '.docx';

            $response = response()->download($filePath, $filename)->deleteFileAfterSend(true);
            if ($document) {
                $response->headers->set('X-Generated-Document-Id', (string) $document->id);
            }
            return $response;
        } catch (\Illuminate\Http\Exceptions\HttpResponseException|\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate stakeholders register draft (no download)
     */
    public function generateDraftDocx(Request $request)
    {
        $siteId = $request->get('site_id');
        if (!$siteId) {
            return response()->json(['message' => 'site_id requis'], 400);
        }
        $siteId = (int) $siteId;
        $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);

        $query = Stakeholder::query();
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $stakeholders = $query->get();
        $site = $siteId ? \App\Models\Site::find($siteId) : null;
        $siteName = $site->name ?? 'Tous les sites';
        $enterprise = $site?->enterprise ?? $request->user()?->enterprise;

        $generator = new \App\Services\Docx\StakeholderDocxGenerator();
        $filePath = $generator->generate($stakeholders, $siteName, $enterprise);

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'stakeholders_register',
            'source_type' => 'stakeholders_register',
            'source_id' => $siteId,
            'title' => 'Registre des parties intéressées - ' . $siteName,
            'description' => 'Registre des parties intéressées généré automatiquement.',
            'file_source_path' => $filePath,
            'created_by' => $request->user()?->id,
            'type' => $generationContext['type'],
            'force_new' => true,
            'store_file' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        return (new DocumentResource($document->load(['site', 'process', 'author'])))->response();
    }

    private function normalizeNeedsPayload(?array $needs): array
    {
        if (!is_array($needs)) {
            return [];
        }

        $userIds = collect($needs)
            ->flatMap(fn ($need) => is_array($need['requirements'] ?? null) ? $need['requirements'] : [])
            ->flatMap(fn ($requirement) => is_array($requirement['actions'] ?? null) ? $requirement['actions'] : [])
            ->pluck('responsible_user_id')
            ->filter(fn ($id) => is_numeric($id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $usersById = User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'first_name', 'last_name', 'name', 'email'])
            ->keyBy('id');

        return collect($needs)->map(function ($need) use ($usersById) {
            $requirements = collect(is_array($need['requirements'] ?? null) ? $need['requirements'] : [])
                ->map(function ($requirement) use ($usersById) {
                    $actions = collect(is_array($requirement['actions'] ?? null) ? $requirement['actions'] : [])
                        ->map(function ($action) use ($usersById) {
                            $responsibleUserId = isset($action['responsible_user_id']) && is_numeric($action['responsible_user_id'])
                                ? (int) $action['responsible_user_id']
                                : null;
                            $responsibleUser = $responsibleUserId ? $usersById->get($responsibleUserId) : null;
                            $responsibleName = null;

                            if ($responsibleUser) {
                                $responsibleName = trim(
                                    implode(' ', array_filter([
                                        $responsibleUser->last_name,
                                        $responsibleUser->first_name,
                                    ]))
                                );

                                if ($responsibleName === '') {
                                    $responsibleName = $responsibleUser->name ?: $responsibleUser->email;
                                }
                            }

                            return [
                                'description' => trim((string) ($action['description'] ?? '')),
                                'responsible_user_id' => $responsibleUserId,
                                'responsible_name' => $responsibleName,
                                'deadline' => !empty($action['deadline']) ? (string) $action['deadline'] : '',
                            ];
                        })
                        ->values()
                        ->all();

                    return [
                        'description' => trim((string) ($requirement['description'] ?? '')),
                        'type' => (string) ($requirement['type'] ?? 'legal'),
                        'actions' => $actions,
                    ];
                })
                ->values()
                ->all();

            return [
                'description' => trim((string) ($need['description'] ?? '')),
                'priority' => (string) ($need['priority'] ?? 'medium'),
                'requirements' => $requirements,
            ];
        })->values()->all();
    }
}
