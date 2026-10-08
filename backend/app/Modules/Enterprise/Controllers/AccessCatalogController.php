<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessCatalogController extends Controller
{
    public function __invoke(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    ): JsonResponse
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        if (!is_array($catalog)) {
            return response()->json(['success' => true, 'data' => $catalog]);
        }

        return response()->json(array_merge(['success' => true, 'data' => $catalog], $catalog));
    }
}
