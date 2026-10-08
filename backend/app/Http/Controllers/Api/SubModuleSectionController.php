<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubModuleSection;
use App\Services\Access\AccessCatalogService;
use App\Services\Context\SiteContextService;
use Illuminate\Http\Request;

class SubModuleSectionController extends Controller
{
    public function index()
    {
        $sections = SubModuleSection::with('subModule.module')->active()->orderBy('order')->get();
        
        return response()->json([
            'data' => $sections->map(fn($s) => [
                'id' => $s->id,
                'sub_module_id' => $s->sub_module_id,
                'sub_module_name' => $s->subModule->name,
                'sub_module_code' => $s->subModule->code,
                'module_name' => $s->subModule->module->name,
                'module_code' => $s->subModule->module->code,
                'name' => $s->name,
                'code' => $s->code,
                'description' => $s->description,
                'icon' => $s->icon,
                'route' => $s->route,
                'order' => $s->order,
                'is_active' => $s->is_active,
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
            'data' => collect($catalog['sections'])->map(fn ($section) => [
                'id' => $section['id'],
                'sub_module_id' => $section['sub_module_id'],
                'sub_module_name' => $section['sub_module_name'],
                'sub_module_code' => $section['sub_module_code'],
                'module_name' => $section['module_name'],
                'module_code' => $section['module_code'],
                'name' => $section['name'],
                'code' => $section['code'],
                'icon' => $section['icon'],
                'route' => $section['route'],
                'order' => $section['order'],
                'has_permission' => (bool) ($section['permissions']['read'] ?? false),
            ])->values(),
        ]);
    }
}
