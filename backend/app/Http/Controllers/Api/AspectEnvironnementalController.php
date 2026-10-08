<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AspectEnvironnemental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\SystemSetting;
use App\Helpers\PermissionHelper;

class AspectEnvironnementalController extends Controller
{
    public function index(Request $request)
    {
        $query = AspectEnvironnemental::with(['equipement', 'process', 'site'])
            ->where('enterprise_id', $request->user()->enterprise_id);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('significatif')) {
            $query->where('aspect_significatif', $request->significatif);
        }

        return response()->json($query->orderBy('criticite', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'equipement_id' => 'nullable|exists:equipements,id',
            'process_id' => 'nullable|exists:processes,id',
            'type' => 'required|in:emission_air,rejet_eau,dechet,bruit,odeur,consommation_ressource,pollution_sol,autre',
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string',
            'condition' => 'required|in:normale,anormale,urgence',
            'gravite' => 'required|integer|min:1|max:5',
            'frequence' => 'required|integer|min:1|max:5',
            'detectabilite' => 'required|integer|min:1|max:5',
            'mesures_maitrise' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['enterprise_id'] = $request->user()->enterprise_id;
        
        $criticite = $data['gravite'] * $data['frequence'] * $data['detectabilite'];
        $threshold = $this->threshold((int) $request->input('site_id'));
        $data['aspect_significatif'] = $criticite >= $threshold;

        $aspect = AspectEnvironnemental::create($data);

        return response()->json($aspect->load(['equipement', 'process', 'site']), 201);
    }

    public function show($id)
    {
        $aspect = AspectEnvironnemental::with(['equipement', 'process', 'site'])->findOrFail($id);
        return response()->json($aspect);
    }

    public function update(Request $request, $id)
    {
        $aspect = AspectEnvironnemental::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'gravite' => 'sometimes|integer|min:1|max:5',
            'frequence' => 'sometimes|integer|min:1|max:5',
            'detectabilite' => 'sometimes|integer|min:1|max:5',
            'mesures_maitrise' => 'nullable|string',
            'objectifs_amelioration' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        
        if (isset($data['gravite']) || isset($data['frequence']) || isset($data['detectabilite'])) {
            $gravite = $data['gravite'] ?? $aspect->gravite;
            $frequence = $data['frequence'] ?? $aspect->frequence;
            $detectabilite = $data['detectabilite'] ?? $aspect->detectabilite;
            $criticite = $gravite * $frequence * $detectabilite;
            $data['aspect_significatif'] = $criticite >= $this->threshold((int) $aspect->site_id);
        }

        $aspect->update($data);

        return response()->json($aspect->load(['equipement', 'process', 'site']));
    }

    public function destroy($id)
    {
        $aspect = AspectEnvironnemental::findOrFail($id);
        $aspect->delete();

        return response()->json(['message' => 'Aspect environnemental supprimé']);
    }

    public function stats(Request $request)
    {
        $enterpriseId = $request->user()->enterprise_id;

        $total = AspectEnvironnemental::where('enterprise_id', $enterpriseId)->count();
        $significatifs = AspectEnvironnemental::where('enterprise_id', $enterpriseId)->significatifs()->count();
        $critiques = AspectEnvironnemental::where('enterprise_id', $enterpriseId)->critiques(75)->count();

        return response()->json([
            'total' => $total,
            'significatifs' => $significatifs,
            'critiques' => $critiques,
        ]);
    }

    public function matrice(Request $request)
    {
        $aspects = AspectEnvironnemental::where('enterprise_id', $request->user()->enterprise_id)
            ->get()
            ->map(function ($aspect) {
                return [
                    'id' => $aspect->id,
                    'designation' => $aspect->designation,
                    'type' => $aspect->type,
                    'gravite' => $aspect->gravite,
                    'frequence' => $aspect->frequence,
                    'criticite' => $aspect->criticite,
                    'significatif' => $aspect->aspect_significatif,
                ];
            });

        return response()->json($aspects);
    }

    public function settings(Request $request)
    {
        $siteId = (int) ($request->input('site_id') ?: $request->user()->site_id);

        return response()->json([
            'site_id' => $siteId ?: null,
            'significance_threshold' => $this->threshold($siteId),
            'formula' => 'gravite x frequence x detectabilite >= seuil',
            'default_threshold' => 50,
        ]);
    }

    public function updateSettings(Request $request)
    {
        abort_unless(
            PermissionHelper::isAdmin($request->user()) || PermissionHelper::can($request->user(), 'aspect_environnementaux.manage'),
            403,
            'Permission de paramétrage AES manquante.'
        );

        $data = $request->validate([
            'site_id' => ['nullable', 'integer', 'exists:sites,id'],
            'significance_threshold' => ['required', 'integer', 'min:1', 'max:125'],
        ]);
        $siteId = (int) ($data['site_id'] ?? $request->user()->site_id);
        SystemSetting::set('aes_significance_threshold', $data['significance_threshold'], $siteId ?: null, 'integer');

        return $this->settings($request);
    }

    private function threshold(int $siteId): int
    {
        return max(1, (int) SystemSetting::get('aes_significance_threshold', $siteId ?: null, 50));
    }
}
