<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * Liste tous les modules (filtrés par souscription)
     */
    public function index(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    )
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        return response()->json(['modules' => $catalog['modules']]);
    }

    /**
     * Modules accessibles par l'utilisateur (avec permissions)
     */
    public function accessible(
        Request $request,
        AccessCatalogService $accessCatalogService,
        SiteContextService $siteContextService
    )
    {
        $catalog = $accessCatalogService->buildCatalog(
            $request->user(),
            $siteContextService->extractSiteId($request)
        );

        $modulesWithPermissions = collect($catalog['modules'])
            ->map(function ($module) {
                return [
                    'id' => $module['id'],
                    'identifier' => $module['code'],
                    'name' => $module['name'],
                    'route' => $module['route'] ?? null,
                    'icon' => $module['icon'],
                    'order' => $module['order'],
                    'permissions' => $module['permissions'],
                ];
            })
            ->sortBy('order')
            ->values();

        return response()->json(['modules' => $modulesWithPermissions]);
    }
}
