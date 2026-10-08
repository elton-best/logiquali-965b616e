<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessCatalog;
use App\Models\Site;
use Illuminate\Http\Request;

class ProcessCatalogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = ProcessCatalog::query()->orderBy('display_order')->orderBy('name');

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
            'name' => 'required|string|max:160',
            'abbreviation' => 'required|string|max:30',
            'internal_code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:10000',
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
        $validated['internal_code'] = isset($validated['internal_code'])
            ? strtoupper(trim((string) $validated['internal_code']))
            : null;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['display_order'] = (int) ($validated['display_order'] ?? 0);

        $duplicate = ProcessCatalog::query()
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

        $item = ProcessCatalog::query()->create($validated);

        return response()->json($item, 201);
    }

    public function update(Request $request, ProcessCatalog $processCatalog)
    {
        $this->assertCatalogAccess($processCatalog, $request->user());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:160',
            'abbreviation' => 'sometimes|string|max:30',
            'internal_code' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer|min:0|max:10000',
        ]);

        if (array_key_exists('abbreviation', $validated)) {
            $abbreviation = strtoupper(trim((string) $validated['abbreviation']));
            $duplicate = ProcessCatalog::query()
                ->where('enterprise_id', (int) $processCatalog->enterprise_id)
                ->where('site_id', $processCatalog->site_id)
                ->whereRaw('UPPER(abbreviation) = ?', [$abbreviation])
                ->where('id', '!=', $processCatalog->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'message' => 'Cette abréviation existe déjà pour cette portée.',
                    'errors' => ['abbreviation' => ['Cette abréviation existe déjà pour cette portée.']],
                ], 422);
            }
            $validated['abbreviation'] = $abbreviation;
        }

        if (array_key_exists('internal_code', $validated) && $validated['internal_code'] !== null) {
            $validated['internal_code'] = strtoupper(trim((string) $validated['internal_code']));
        }

        $processCatalog->update($validated);

        return response()->json($processCatalog);
    }

    public function destroy(Request $request, ProcessCatalog $processCatalog)
    {
        $this->assertCatalogAccess($processCatalog, $request->user());
        $processCatalog->delete();

        return response()->json(['message' => 'Processus supprimé.']);
    }

    private function assertCatalogAccess(ProcessCatalog $item, $user): void
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
}
