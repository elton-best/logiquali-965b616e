<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Context\SiteContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnterpriseNormController extends Controller
{
    public function index(Request $request, SiteContextService $siteContextService): JsonResponse
    {
        $site = $siteContextService->resolveForRequest($request, $request->integer('site_id') ?: null);
        if (!$site) {
            return response()->json(['message' => 'Site non autorisé ou introuvable.'], 403);
        }

        $coverage = $site->getNormCoverageStatus();
        $activeNorms = collect($coverage['active_norms'] ?? [])->values();

        return response()->json([
            'data' => $activeNorms,
            'site_id' => $site->id,
            'active_norms' => $activeNorms,
            'expired_norms' => $coverage['expired_norms'] ?? [],
            'can_choose_norm' => $activeNorms->count() >= 2,
            'blocking_reasons' => $coverage['blocking_reasons'] ?? [],
        ]);
    }
}
