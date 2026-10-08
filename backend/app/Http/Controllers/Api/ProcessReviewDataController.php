<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Action;
use App\Models\AspectEnvironnemental;
use App\Models\DuerpDanger;
use App\Models\NonConformity;
use App\Models\Opportunity;
use App\Models\OperationalProject;
use App\Models\Process;
use App\Models\Reclamation;
use App\Models\Risk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Contract C7: read-only B-owned data consumed by the process-review flow. */
class ProcessReviewDataController extends Controller
{
    public function show(Request $request, Process $process): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin() || (int) $process->enterprise_id === (int) $user?->enterprise_id, 403, 'Accès refusé.');

        $from = $request->filled('debut') ? Carbon::parse($request->string('debut'))->startOfDay() : null;
        $to = $request->filled('fin') ? Carbon::parse($request->string('fin'))->endOfDay() : null;

        return response()->json([
            'data' => [
                'risques_opportunites' => [
                    'risques' => $this->query(Risk::query()->where('process_id', $process->id), $from, $to)->get(),
                    'opportunites' => $this->query(Opportunity::query()->where('process_id', $process->id), $from, $to)->get(),
                ],
                'nc_ecarts' => $this->query(NonConformity::query()->where('process_id', $process->id), $from, $to)->get(),
                'reclamations' => $this->query(Reclamation::query()->where(function ($query) use ($process): void {
                    $query->where('process_id', $process->id)->orWhereNull('process_id');
                })->where('site_id', $process->site_id), $from, $to)->get(),
                'actions' => $this->query(Action::query()->where('process_id', $process->id), $from, $to)->get(),
                'duerp' => $this->query(DuerpDanger::query()->whereHas('duerp', fn ($query) => $query->where('enterprise_id', $process->enterprise_id))->where(function ($query) use ($process): void {
                    $query->where('process_id', $process->id)->orWhereNull('process_id');
                }), $from, $to)->with('duerp:id,version,evaluation_date')->get(),
                'aes' => $this->query(AspectEnvironnemental::query()->where('process_id', $process->id), $from, $to)->get(),
                'projets' => OperationalProject::query()
                    ->where('enterprise_id', $process->enterprise_id)
                    ->whereHas('processes', fn ($query) => $query->whereKey($process->id))
                    ->when($from && $to, fn ($query) => $query->whereBetween('created_at', [$from, $to]))
                    ->with('processes:id,title,code')
                    ->get(),
            ],
            'meta' => [
                'process_id' => $process->id,
                'debut' => $from?->toDateString(),
                'fin' => $to?->toDateString(),
                'contract' => 'C7',
            ],
        ]);
    }

    private function query($query, ?Carbon $from, ?Carbon $to)
    {
        return $query
            ->when($from && $to, fn ($builder) => $builder->whereBetween('created_at', [$from, $to]))
            ->latest('id');
    }
}
