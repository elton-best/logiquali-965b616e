<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DocumentTypeCatalog;
use App\Models\NomenclatureTemplate;
use App\Models\Process;
use App\Models\Site;
use Illuminate\Http\Request;

class DocumentTypeCatalogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = DocumentTypeCatalog::query()->orderBy('display_order')->orderBy('name');

        if ($user && $user->user_type !== 'super_admin') {
            $query->where('enterprise_id', (int) $user->enterprise_id);
        } elseif ($request->filled('enterprise_id')) {
            $query->where('enterprise_id', (int) $request->integer('enterprise_id'));
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->integer('site_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'nullable|exists:sites,id',
            'name' => 'required|string|max:120',
            'abbreviation' => 'required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:10000',
            'process_ids' => 'nullable|array',
            'process_ids.*' => 'integer|exists:processes,id',
        ]);

        $user = $request->user();
        if ($user && $user->user_type !== 'super_admin') {
            $validated['enterprise_id'] = (int) $user->enterprise_id;
            if (!empty($validated['site_id'])) {
                $site = Site::query()->findOrFail((int) $validated['site_id']);
                if ((int) $site->enterprise_id !== (int) $user->enterprise_id) {
                    abort(403);
                }
            } elseif (!empty($user->site_id)) {
                $validated['site_id'] = (int) $user->site_id;
            }
        } else {
            $validated['enterprise_id'] = (int) ($request->integer('enterprise_id') ?: 0);
            if ($validated['enterprise_id'] <= 0) {
                return response()->json(['message' => 'enterprise_id est requis pour ce profil.'], 422);
            }
        }

        $validated['abbreviation'] = strtoupper(trim((string) $validated['abbreviation']));
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        if ($validated['is_active']) {
            return response()->json([
                'message' => 'Un type documentaire ne peut être activé qu’après publication d’un template.',
                'errors' => ['is_active' => ['Publiez un template actif avant d’activer ce type.']],
            ], 422);
        }

        $duplicate = DocumentTypeCatalog::query()
            ->where('enterprise_id', (int) $validated['enterprise_id'])
            ->where('site_id', $validated['site_id'] ?? null)
            ->whereRaw('UPPER(abbreviation) = ?', [$validated['abbreviation']])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Cette abréviation existe déjà pour cette portée.',
                'errors' => ['abbreviation' => ['Cette abréviation existe déjà pour cette portée.']],
            ], 422);
        }

        $processIds = array_values(array_unique(array_map('intval', $validated['process_ids'] ?? [])));
        unset($validated['process_ids']);

        $item = DocumentTypeCatalog::query()->create($validated);
        if (!empty($processIds)) {
            $this->assertProcessAccess($processIds, (int) $validated['enterprise_id'], $validated['site_id'] ?? null);
            $item->processes()->sync($processIds);
        }

        return response()->json($item, 201);
    }

    public function update(Request $request, DocumentTypeCatalog $documentTypeCatalog)
    {
        $this->assertCatalogAccess($documentTypeCatalog, $request->user());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:120',
            'abbreviation' => 'sometimes|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:10000',
            'process_ids' => 'nullable|array',
            'process_ids.*' => 'integer|exists:processes,id',
        ]);

        if (array_key_exists('abbreviation', $validated)) {
            $abbreviation = strtoupper(trim((string) $validated['abbreviation']));
            $duplicate = DocumentTypeCatalog::query()
                ->where('enterprise_id', (int) $documentTypeCatalog->enterprise_id)
                ->where('site_id', $documentTypeCatalog->site_id)
                ->whereRaw('UPPER(abbreviation) = ?', [$abbreviation])
                ->where('id', '!=', $documentTypeCatalog->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Cette abréviation existe déjà pour cette portée.',
                    'errors' => ['abbreviation' => ['Cette abréviation existe déjà pour cette portée.']],
                ], 422);
            }
            $validated['abbreviation'] = $abbreviation;
        }

        if (array_key_exists('is_active', $validated) && (bool) $validated['is_active']) {
            $activeCount = NomenclatureTemplate::query()
                ->where('enterprise_id', (int) $documentTypeCatalog->enterprise_id)
                ->where('site_id', $documentTypeCatalog->site_id)
                ->where('document_type_catalog_id', $documentTypeCatalog->id)
                ->where('status', 'published')
                ->where('is_active', true)
                ->count();

            if ($activeCount !== 1) {
                return response()->json([
                    'message' => 'Un type actif doit avoir exactement un template publié.',
                    'errors' => ['is_active' => ['Publiez un template actif unique pour ce type avant activation.']],
                ], 422);
            }
        }

        $processIds = null;
        if (array_key_exists('process_ids', $validated)) {
            $processIds = array_values(array_unique(array_map('intval', $validated['process_ids'] ?? [])));
            unset($validated['process_ids']);
        }

        $documentTypeCatalog->update($validated);

        if (is_array($processIds)) {
            $this->assertProcessAccess($processIds, (int) $documentTypeCatalog->enterprise_id, $documentTypeCatalog->site_id);
            $documentTypeCatalog->processes()->sync($processIds);
        }

        return response()->json($documentTypeCatalog);
    }

    public function destroy(Request $request, DocumentTypeCatalog $documentTypeCatalog)
    {
        $this->assertCatalogAccess($documentTypeCatalog, $request->user());
        $documentTypeCatalog->delete();

        return response()->json(['message' => 'Type documentaire supprimé.']);
    }

    private function assertCatalogAccess(DocumentTypeCatalog $item, $user): void
    {
        if (!$user || $user->user_type === 'super_admin') {
            return;
        }

        if ((int) $item->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        if (!empty($user->site_id) && !empty($item->site_id) && (int) $item->site_id !== (int) $user->site_id) {
            abort(403);
        }
    }

    private function assertProcessAccess(array $processIds, int $enterpriseId, ?int $siteId): void
    {
        if (empty($processIds)) {
            return;
        }

        $count = Process::query()
            ->whereIn('id', $processIds)
            ->where('enterprise_id', $enterpriseId)
            ->when($siteId, function ($q) use ($siteId) {
                $q->where(function ($inner) use ($siteId) {
                    $inner->where('site_id', $siteId)->orWhereNull('site_id');
                });
            })
            ->count();

        if ($count !== count($processIds)) {
            abort(422, 'Un ou plusieurs processus ne sont pas éligibles pour ce type documentaire.');
        }
    }
}
