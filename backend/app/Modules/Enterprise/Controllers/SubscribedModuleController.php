<?php

namespace App\Modules\Enterprise\Controllers;

use App\Models\Context;
use App\Models\Enterprise;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscribedModuleController extends Controller
{
    public function modules(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    ): JsonResponse
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        return response()->json([
            'data' => $catalog['modules'],
            'meta' => [
                ...$catalog['meta'],
                'active_norms' => collect($catalog['norms'])->pluck('name')->values()->all(),
            ],
        ]);
    }

    public function subModules(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    ): JsonResponse
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        return response()->json(['data' => $catalog['sub_modules']]);
    }

    public function sections(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    ): JsonResponse
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        return response()->json(['data' => $catalog['sections']]);
    }
}
