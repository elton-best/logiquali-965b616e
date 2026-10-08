<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\DocumentTypeCatalog;
use App\Models\Process;
use App\Models\Site;
use App\Services\ProcessService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

trait ResolvesGeneratedDocumentContext
{
    private function resolveGeneratedDocumentContext(
        Request $request,
        int $siteId,
        ?int $defaultProcessId = null,
        ?string $preferredTypeAbbr = null,
        ?string $preferredTypeName = null
    ): array
    {
        $user = $request->user();
        abort_unless($user, 401, 'Non authentifié.');

        $targetProcess = $defaultProcessId ? Process::withoutEnterpriseScope()->find($defaultProcessId) : null;
        if ($targetProcess && $siteId <= 0) {
            $siteId = (int) ($targetProcess->site_id ?? 0);
        }

        $targetSite = $siteId > 0 ? Site::find($siteId) : null;
        if (!$targetSite && $user->site_id) {
            $targetSite = Site::find($user->site_id);
            if ($targetSite && $siteId <= 0) {
                $siteId = (int) $targetSite->id;
            }
        }

        $isAuthorized = $user->user_type === 'super_admin'
            || ($user->site_id && $siteId > 0 && (int) $user->site_id === (int) $siteId)
            || ($user->enterprise_id && $targetSite && (int) $targetSite->enterprise_id === (int) $user->enterprise_id)
            || ($user->enterprise_id && $targetProcess && (int) $targetProcess->enterprise_id === (int) $user->enterprise_id)
            || ($user->enterprise_id && $targetProcess && $targetProcess->site && (int) $targetProcess->site->enterprise_id === (int) $user->enterprise_id)
            || ($user->isEnterpriseAdmin());

        abort_unless($isAuthorized, 403, 'Accès non autorisé au document de ce site.');

        $rules = [
            'document_type_catalog_id' => 'nullable|integer',
            'process_id' => 'nullable|integer',
            'process_name' => 'nullable|string|max:255',
            'process_type' => 'nullable|string|max:50',
            'process_abbreviation' => 'nullable|string|max:20',
        ];

        $validated = $request->validate($rules);
        $processId = (int) ($validated['process_id'] ?? $defaultProcessId);

        $documentType = null;
        if (!empty($validated['document_type_catalog_id'])) {
            $documentType = DocumentTypeCatalog::query()
                ->where(function ($query) use ($siteId) {
                    if ($siteId > 0) {
                        $query->where('site_id', $siteId)->orWhereNull('site_id');
                    } else {
                        $query->whereNull('site_id');
                    }
                })
                ->where('is_active', true)
                ->find((int) $validated['document_type_catalog_id']);
        }

        if (!$documentType) {
            $enterpriseId = $targetSite?->enterprise_id ?: ($targetProcess?->enterprise_id ?: $request->user()?->enterprise_id);
            $targetAbbr = $preferredTypeAbbr ? strtoupper(trim($preferredTypeAbbr)) : null;

            // Recherche prioritaire du type demandé ou d'un type Fiche Processus existant
            $documentType = DocumentTypeCatalog::query()
                ->where(function ($query) use ($siteId, $enterpriseId) {
                    if ($enterpriseId) {
                        $query->where('enterprise_id', $enterpriseId);
                    }
                    if ($siteId > 0) {
                        $query->where('site_id', $siteId)->orWhereNull('site_id');
                    }
                })
                ->where('is_active', true)
                ->where(function ($q) use ($targetAbbr, $preferredTypeName) {
                    if ($targetAbbr) {
                        $q->where('abbreviation', $targetAbbr);
                        if ($preferredTypeName) {
                            $q->orWhere('name', 'ilike', '%' . $preferredTypeName . '%');
                        }
                    } else {
                        $q->where('abbreviation', 'FICHE')
                          ->orWhere('abbreviation', 'FP')
                          ->orWhere('name', 'ilike', '%fiche%');
                    }
                })
                ->first();

            // Sinon prendre le premier type actif
            if (!$documentType) {
                $documentType = DocumentTypeCatalog::query()
                    ->where(function ($query) use ($siteId, $enterpriseId) {
                        if ($enterpriseId) {
                            $query->where('enterprise_id', $enterpriseId);
                        }
                        if ($siteId > 0) {
                            $query->where('site_id', $siteId)->orWhereNull('site_id');
                        }
                    })
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->first();
            }

            if (!$documentType && $enterpriseId) {
                $defaultAbbr = $preferredTypeAbbr ?: 'FP';
                $defaultName = $preferredTypeName ?: 'Fiche Processus';
                $documentType = DocumentTypeCatalog::firstOrCreate(
                    [
                        'enterprise_id' => $enterpriseId,
                        'site_id' => $siteId > 0 ? $siteId : null,
                        'abbreviation' => $defaultAbbr,
                    ],
                    [
                        'name' => $defaultName,
                        'description' => "Document {$defaultName} généré automatiquement",
                        'is_active' => true,
                        'display_order' => 1,
                    ]
                );
            }
        }

        if (!$documentType) {
            throw new HttpResponseException(response()->json([
                'message' => 'Le type documentaire sélectionné est indisponible pour ce site.',
                'errors' => ['document_type_catalog_id' => ['Type documentaire invalide.']],
            ], 422));
        }

        $process = $targetProcess;

        if (!$process && $processId > 0) {
            $processQuery = Process::withoutEnterpriseScope();
            if ($siteId > 0) {
                $processQuery->where(function ($q) use ($siteId) {
                    $q->where('site_id', $siteId)->orWhereNull('site_id');
                });
            }
            $process = $processQuery->find($processId);
        }

        if (!$process && !empty($validated['process_name'])) {
            $process = $this->resolveGeneratedDocumentProcessByName($validated, $siteId, $request);
        }

        if (!$process && $processId > 0) {
            $process = Process::withoutEnterpriseScope()->find($processId);
        }

        if (!$process && $siteId > 0) {
            $process = Process::withoutEnterpriseScope()
                ->where('site_id', $siteId)
                ->orderByRaw("CASE WHEN category IN ('pilotage', 'management') THEN 0 ELSE 1 END")
                ->first();
        }

        if (!$process) {
            $enterpriseId = $targetSite?->enterprise_id ?: $request->user()?->enterprise_id;
            $process = Process::withoutEnterpriseScope()
                ->where('enterprise_id', $enterpriseId)
                ->first();
        }

        if (!$process) {
            $enterpriseId = $targetSite?->enterprise_id ?: $request->user()?->enterprise_id;
            $process = Process::withoutEnterpriseScope()->create([
                'site_id' => $siteId > 0 ? $siteId : ($user->site_id ?: 1),
                'enterprise_id' => $enterpriseId,
                'code' => 'SMQ',
                'title' => 'Système de Management de la Qualité',
                'category' => 'pilotage',
                'status' => 'active',
                'pilot_id' => $user->id,
            ]);
        }

        return [
            'document_type' => $documentType,
            'process' => $process,
            'type' => $documentType->abbreviation,
            'process_id' => (int) $process->id,
            'process_code' => $process->abbreviation ?: $process->code,
            'process_name' => $process->title,
            'metadata' => [
                'document_type_catalog_id' => (int) $documentType->id,
                'document_type_abbreviation' => $documentType->abbreviation,
            ],
        ];
    }

    private function resolveGeneratedDocumentProcessByName(array $validated, int $siteId, Request $request): ?Process
    {
        $processName = trim((string) ($validated['process_name'] ?? ''));
        if ($processName === '') {
            return null;
        }

        $normalizedName = mb_strtolower(preg_replace('/\s+/', ' ', $processName) ?: $processName);

        $existingProcess = Process::withoutEnterpriseScope()
            ->where('site_id', $siteId)
            ->get()
            ->first(function (Process $process) use ($normalizedName) {
                $title = mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $process->title)) ?: (string) $process->title);
                return $title === $normalizedName;
            });

        if ($existingProcess) {
            return $existingProcess;
        }

        $userId = $request->user()?->id;
        if (!$userId) {
            return null;
        }

        $category = match (mb_strtolower((string) ($validated['process_type'] ?? ''))) {
            'management', 'pilotage', 'direction' => 'pilotage',
            'support', 'soutien' => 'support',
            default => 'operationnel',
        };

        return app(ProcessService::class)->createProcess([
            'enterprise_id' => Site::where('id', $siteId)->value('enterprise_id'),
            'site_id' => $siteId,
            'title' => $processName,
            'abbreviation' => $validated['process_abbreviation'] ?? null,
            'category' => $category,
            'pilot_id' => $userId,
            'status' => 'draft',
            'purpose' => 'Processus créé depuis la génération documentaire',
        ], $userId);
    }
}
