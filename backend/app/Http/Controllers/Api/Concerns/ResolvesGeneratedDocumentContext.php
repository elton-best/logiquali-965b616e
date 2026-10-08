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
    private function resolveGeneratedDocumentContext(Request $request, int $siteId, ?int $defaultProcessId = null): array
    {
        abort_unless($request->user() && ($request->user()->site_id === $siteId || $request->user()->user_type === 'super_admin'), 403, 'Accès non autorisé au site.');

        $rules = [
            'document_type_catalog_id' => 'required|integer|exists:document_type_catalogs,id',
            'process_id' => $defaultProcessId ? 'nullable|integer|exists:processes,id' : 'nullable|integer|exists:processes,id',
            'process_name' => $defaultProcessId ? 'nullable|string|max:255' : 'required_without:process_id|string|max:255',
            'process_type' => 'nullable|string|max:50',
            'process_abbreviation' => 'nullable|string|max:20',
        ];

        $validated = $request->validate($rules);
        $processId = (int) ($validated['process_id'] ?? $defaultProcessId);

        $documentType = DocumentTypeCatalog::query()
            ->where(function ($query) use ($siteId) {
                $query->where('site_id', $siteId)
                      ->orWhereNull('site_id');
            })
            ->where('is_active', true)
            ->find((int) $validated['document_type_catalog_id']);

        if (!$documentType) {
            throw new HttpResponseException(response()->json([
                'message' => 'Le type documentaire sélectionné est indisponible pour ce site.',
                'errors' => ['document_type_catalog_id' => ['Type documentaire invalide.']],
            ], 422));
        }

        $process = null;

        if ($processId > 0) {
            $process = Process::withoutEnterpriseScope()
                ->where('site_id', $siteId)
                ->find($processId);
        }

        if (!$process && !empty($validated['process_name'])) {
            $process = $this->resolveGeneratedDocumentProcessByName($validated, $siteId, $request);
        }

        if (!$process) {
            throw new HttpResponseException(response()->json([
                'message' => 'Le processus sélectionné est indisponible pour ce site.',
                'errors' => ['process_id' => ['Processus invalide.']],
            ], 422));
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
