<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Duerp;
use App\Models\DuerpDanger;
use App\Models\DuerpPreventionAction;
use App\Models\DuerpRiskFamily;
use App\Models\DuerpRiskType;
use App\Models\DuerpScale;
use App\Models\DuerpWorkUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuerpController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => Duerp::query()->where('enterprise_id', $request->user()->enterprise_id)->with('dangers.preventionActions')->latest('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['site_id' => ['nullable', 'exists:sites,id'], 'version' => ['nullable', 'string', 'max:50'], 'evaluation_date' => ['required', 'date'], 'next_evaluation_date' => ['nullable', 'date', 'after_or_equal:evaluation_date']]);
        $data['enterprise_id'] = $request->user()->enterprise_id;
        $data['created_by'] = $request->user()->id;
        $duerp = Duerp::create($data);
        return response()->json(['data' => $duerp], 201);
    }

    public function show(Request $request, Duerp $duerp): JsonResponse
    {
        $this->assertScope($request, $duerp);
        return response()->json(['data' => $duerp->load(['dangers.workUnit', 'dangers.riskFamily', 'dangers.riskType', 'dangers.preventionActions'])]);
    }

    public function update(Request $request, Duerp $duerp): JsonResponse
    {
        $this->assertScope($request, $duerp);
        $data = $request->validate(['version' => ['sometimes', 'string', 'max:50'], 'is_current' => ['sometimes', 'boolean'], 'evaluation_date' => ['sometimes', 'date'], 'next_evaluation_date' => ['nullable', 'date']]);
        $data['updated_by'] = $request->user()->id;
        $duerp->update($data);
        return response()->json(['data' => $duerp->fresh()->load('dangers.preventionActions')]);
    }

    public function workUnits(Request $request): JsonResponse
    {
        return response()->json(['data' => DuerpWorkUnit::query()->where('enterprise_id', $request->user()->enterprise_id)->with('families')->orderBy('name')->get()]);
    }

    public function storeWorkUnit(Request $request): JsonResponse
    {
        $data = $request->validate(['site_id' => ['nullable', 'exists:sites,id'], 'process_id' => ['nullable', 'exists:processes,id'], 'management_process_id' => ['nullable', 'exists:processes,id'], 'code' => ['nullable', 'string', 'max:80'], 'name' => ['required', 'string', 'max:255'], 'unit_type' => ['required', 'in:process,site,custom'], 'description' => ['nullable', 'string']]);
        $data['enterprise_id'] = $request->user()->enterprise_id;
        $this->assertOptionalRelationScope($request, $data);
        return response()->json(['data' => DuerpWorkUnit::create($data)], 201);
    }

    public function storeFamily(Request $request, DuerpWorkUnit $workUnit): JsonResponse
    {
        $this->assertScope($request, $workUnit);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]);
        return response()->json(['data' => $workUnit->families()->create($data)], 201);
    }

    public function riskTypes(Request $request): JsonResponse
    {
        return response()->json(['data' => DuerpRiskType::query()->where('enterprise_id', $request->user()->enterprise_id)->whereNull('archived_at')->orderBy('label')->get()]);
    }

    public function storeRiskType(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:80'], 'label' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string']]);
        $data['enterprise_id'] = $request->user()->enterprise_id;
        $data['created_by'] = $request->user()->id;
        return response()->json(['data' => DuerpRiskType::create($data)], 201);
    }

    public function archiveRiskType(Request $request, DuerpRiskType $riskType): JsonResponse
    {
        $this->assertScope($request, $riskType);
        $riskType->update(['archived_at' => now()]);
        return response()->json(['data' => $riskType]);
    }

    public function scales(Request $request): JsonResponse
    {
        return response()->json(['data' => DuerpScale::query()->where('enterprise_id', $request->user()->enterprise_id)->where('is_active', true)->orderBy('kind')->orderBy('value')->get()]);
    }

    public function storeScale(Request $request): JsonResponse
    {
        $data = $request->validate(['kind' => ['required', 'in:severity,exposure'], 'label' => ['required', 'string', 'max:255'], 'value' => ['required', 'integer', 'min:1'], 'color' => ['nullable', 'string', 'max:20']]);
        $data['enterprise_id'] = $request->user()->enterprise_id;
        return response()->json(['data' => DuerpScale::updateOrCreate(['enterprise_id' => $data['enterprise_id'], 'kind' => $data['kind'], 'value' => $data['value']], $data)], 201);
    }

    public function storeDanger(Request $request, Duerp $duerp): JsonResponse
    {
        $this->assertScope($request, $duerp);
        $data = $request->validate(['work_unit_id' => ['nullable', 'exists:duerp_work_units,id'], 'risk_family_id' => ['nullable', 'exists:duerp_risk_families,id'], 'risk_type_id' => ['nullable', 'exists:duerp_risk_types,id'], 'process_id' => ['nullable', 'exists:processes,id'], 'organizational_unit' => ['nullable', 'string', 'max:255'], 'activity' => ['nullable', 'string', 'max:255'], 'danger_type' => ['required', 'string', 'max:100'], 'danger_description' => ['required', 'string'], 'exposed_workers' => ['nullable', 'array'], 'probability_score' => ['nullable', 'integer', 'min:0'], 'severity_score' => ['nullable', 'integer', 'min:0'], 'existing_measures' => ['nullable', 'string'], 'applicable_norms' => ['nullable', 'array']]);
        $this->assertOptionalRelationScope($request, $data, (int) $duerp->enterprise_id);
        $this->assertConfiguredScale((int) $duerp->enterprise_id, 'exposure', $data['probability_score'] ?? null);
        $this->assertConfiguredScale((int) $duerp->enterprise_id, 'severity', $data['severity_score'] ?? null);
        return response()->json(['data' => $duerp->dangers()->create($data)->load(['workUnit', 'riskFamily', 'riskType'])], 201);
    }

    public function storePreventionAction(Request $request, DuerpDanger $danger): JsonResponse
    {
        abort_unless((int) $danger->duerp?->enterprise_id === (int) $request->user()->enterprise_id, 403, 'Accès refusé.');
        $data = $request->validate(['category' => ['nullable', 'string', 'max:80'], 'action' => ['required', 'string', 'max:5000'], 'responsible_id' => ['nullable', 'exists:users,id'], 'deadline' => ['nullable', 'date'], 'status' => ['nullable', 'in:a_faire,en_cours,realisee,replanifiee']]);
        return response()->json(['data' => $danger->preventionActions()->create($data)], 201);
    }

    private function assertScope(Request $request, $model): void
    {
        abort_unless($request->user()->isSuperAdmin() || (int) $model->enterprise_id === (int) $request->user()->enterprise_id, 403, 'Accès refusé.');
    }

    private function assertOptionalRelationScope(Request $request, array $data, ?int $enterpriseId = null): void
    {
        $enterpriseId ??= (int) $request->user()->enterprise_id;
        if (!empty($data['work_unit_id'])) {
            abort_unless((int) DB::table('duerp_work_units')->where('id', $data['work_unit_id'])->value('enterprise_id') === $enterpriseId, 403, 'Unité de travail hors périmètre.');
        }
        if (!empty($data['risk_type_id'])) {
            abort_unless((int) DB::table('duerp_risk_types')->where('id', $data['risk_type_id'])->value('enterprise_id') === $enterpriseId, 403, 'Type de risque hors périmètre.');
        }
        if (!empty($data['risk_family_id'])) {
            abort_unless(DB::table('duerp_risk_families')->where('id', $data['risk_family_id'])->whereIn('work_unit_id', DB::table('duerp_work_units')->where('enterprise_id', $enterpriseId)->select('id'))->exists(), 403, 'Famille de risque hors périmètre.');
        }
    }

    private function assertConfiguredScale(int $enterpriseId, string $kind, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $scales = DuerpScale::query()->where('enterprise_id', $enterpriseId)->where('kind', $kind)->where('is_active', true);
        if ($scales->exists()) {
            abort_unless($scales->where('value', (int) $value)->exists(), 422, "Valeur {$kind} absente de l'échelle configurée.");
        }
    }
}
