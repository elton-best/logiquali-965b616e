<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubModule;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use Illuminate\Http\Request;

class SubModuleController extends Controller
{
    public function index()
    {
        $subModules = SubModule::with('module')->active()->orderBy('order')->get();
        
        return response()->json([
            'data' => $subModules->map(fn($sm) => [
                'id' => $sm->id,
                'module_id' => $sm->module_id,
                'module_name' => $sm->module->name,
                'module_code' => $sm->module->code,
                'name' => $sm->name,
                'code' => $sm->code,
                'description' => $sm->description,
                'icon' => $sm->icon,
                'route' => $sm->route,
                'order' => $sm->order,
                'is_active' => $sm->is_active,
            ])
        ]);
    }

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

        return response()->json([
            'data' => collect($catalog['sub_modules'])->map(fn ($subModule) => [
                'id' => $subModule['id'],
                'module_id' => $subModule['module_id'],
                'module_name' => $subModule['module_name'],
                'module_code' => $subModule['module_code'],
                'name' => $subModule['name'],
                'code' => $subModule['code'],
                'icon' => $subModule['icon'],
                'route' => $subModule['route'],
                'order' => $subModule['order'],
                'has_permission' => (bool) ($subModule['permissions']['read'] ?? false),
            ])->values(),
        ]);
    }
}
