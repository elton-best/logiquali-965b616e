<?php

namespace App\Services\Access;

use App\Models\Site;
use App\Models\User;
use App\Services\Context\SiteContextService;

class AccessCatalogService
{
    public function __construct(
        private readonly SiteContextService $siteContextService,
        private readonly AccessDecisionService $accessDecisionService
    ) {}

    public function buildCatalog(User $user, ?int $siteId = null): array
    {
        $site = $this->siteContextService->resolveForUser($user, $siteId);
        if (!$site && $siteId !== null) {
            $site = $this->siteContextService->resolveForUser($user, null);
        }

        if (!$site) {
            return $this->emptyCatalog(null);
        }

        $subscriptions = $site->getActiveSubscriptions()->loadMissing('offer.norms');
        if ($subscriptions->isEmpty()) {
            return $this->emptyCatalog($site);
        }

        $linkedNorms = $subscriptions
            ->flatMap(fn ($subscription) => $subscription->offer?->norms ?? collect())
            ->unique('id')
            ->sortBy('code')
            ->values();

        // Business rule: imported norms must be immediately visible in company library.
        // Keep archived norms hidden from company users.
        $visibleNorms = $linkedNorms
            ->filter(fn ($norm) => (string) ($norm->status ?? '') !== 'archived')
            ->values();

        $normCodes = $visibleNorms
            ->map(fn ($norm) => mb_strtoupper(trim((string) ($norm->code ?? ''))))
            ->filter()
            ->values();

        $allModules = collect();
        $allSubModules = collect();
        $allSections = collect();

        foreach ($subscriptions as $subscription) {
            $allModules = $allModules->merge($subscription->getAccessibleModules());
            $allSubModules = $allSubModules->merge($subscription->getAccessibleSubModules());
            $allSections = $allSections->merge($subscription->getAccessibleSections());
        }

        // Défense en profondeur: déduplication avec normalisation stricte des codes.
        $modules = $allModules
            ->unique(fn ($module) => $this->normalizeCode($module->code))
            ->sortBy('order')
            ->values();
        $subModules = $allSubModules
            ->unique(fn ($subModule) => $this->normalizeCode($subModule->code))
            ->sortBy('order')
            ->values();
        $sections = $allSections
            ->unique(fn ($section) => $this->normalizeCode($section->code))
            ->sortBy('order')
            ->values();

        $moduleRows = collect();
        foreach ($modules as $module) {
            $permissions = $this->modulePermissions($user, $module->code);
            $moduleRows->push([
                'id' => $module->id,
                'code' => $module->code,
                'name' => $module->name,
                'description' => $module->description,
                'icon' => $module->icon,
                'order' => $module->order,
                'is_active' => (bool) $module->is_active,
                'is_common' => (bool) ($module->is_common ?? false),
                'permissions' => $permissions,
            ]);
        }

        $moduleRows = $moduleRows
            ->unique(fn (array $module) => $this->normalizeCode($module['code'] ?? null))
            ->sortBy('order')
            ->values();

        $moduleIds = $moduleRows->pluck('id')->all();

        $subModuleRows = collect();
        foreach ($subModules as $subModule) {
            $subModule->loadMissing('module', 'norms:id,code');

            if (!in_array($subModule->module_id, $moduleIds, true)) {
                continue;
            }

            $permissions = $this->subModulePermissions($user, $subModule->code);

            $subModuleRows->push([
                'id' => $subModule->id,
                'module_id' => $subModule->module_id,
                'module_code' => $subModule->module->code,
                'module_name' => $subModule->module->name,
                'code' => $subModule->code,
                'name' => $subModule->name,
                'description' => $subModule->description,
                'icon' => $subModule->icon,
                'route' => $this->normalizeRoute(
                    $subModule->route,
                    "/company/iso/{$subModule->module->code}/{$subModule->code}"
                ),
                'order' => $subModule->order,
                'is_active' => (bool) $subModule->is_active,
                'is_common' => (bool) ($subModule->is_common ?? false),
                'norm_codes' => $subModule->norms
                    ->pluck('code')
                    ->map(fn ($code) => mb_strtoupper(trim((string) $code)))
                    ->filter()
                    ->values()
                    ->all(),
                'permissions' => $permissions,
            ]);
        }

        $subModuleRows = $subModuleRows
            ->unique(fn (array $subModule) => sprintf(
                '%s::%s',
                $this->normalizeCode($subModule['module_code'] ?? null),
                $this->normalizeCode($subModule['code'] ?? null),
            ))
            ->filter(fn (array $subModule) => $this->isSubModuleAllowedForNorms($subModule, $normCodes))
            ->sortBy('order')
            ->map(function (array $subModule) {
                unset($subModule['norm_codes']);

                return $subModule;
            })
            ->values();

        $subModuleIds = $subModuleRows->pluck('id')->all();

        $sectionRows = collect();
        foreach ($sections as $section) {
            if (!in_array($section->sub_module_id, $subModuleIds, true)) {
                continue;
            }

            $permissions = $this->sectionPermissions($user, $section->code);

            $sectionRows->push([
                'id' => $section->id,
                'sub_module_id' => $section->sub_module_id,
                'sub_module_code' => $section->subModule->code,
                'sub_module_name' => $section->subModule->name,
                'module_code' => $section->subModule->module->code,
                'module_name' => $section->subModule->module->name,
                'code' => $section->code,
                'name' => $section->name,
                'description' => $section->description,
                'icon' => $section->icon,
                'route' => $this->normalizeRoute(
                    $section->route,
                    "/company/iso/{$section->subModule->module->code}/{$section->code}"
                ),
                'order' => $section->order,
                'is_active' => (bool) $section->is_active,
                'is_common' => (bool) ($section->is_common ?? false),
                'permissions' => $permissions,
            ]);
        }

        $sectionRows = $sectionRows
            ->unique(fn (array $section) => sprintf(
                '%s::%s::%s',
                $this->normalizeCode($section['module_code'] ?? null),
                $this->normalizeCode($section['sub_module_code'] ?? null),
                $this->normalizeCode($section['code'] ?? null),
            ))
            ->sortBy('order')
            ->values();

        $moduleIdsWithSubModules = $subModuleRows->pluck('module_id')->unique()->all();
        $moduleRows = $moduleRows
            ->filter(fn ($module) => in_array($module['id'], $moduleIdsWithSubModules, true))
            ->values();

        return [
            'norms' => $visibleNorms->map(fn ($norm) => [
                'id' => $norm->id,
                'code' => $norm->code,
                'name' => $norm->name,
                'domain' => $norm->domain,
                'status' => $norm->status,
            ])->values()->all(),
            'modules' => $moduleRows->all(),
            'sub_modules' => $subModuleRows->values()->all(),
            'sections' => $sectionRows->values()->all(),
            'meta' => [
                'site_id' => $site->id,
                'site_name' => $site->name,
                'enterprise_id' => $site->enterprise_id,
                'active_subscriptions_count' => $subscriptions->count(),
                'linked_norms_count' => $linkedNorms->count(),
                'active_norms_count' => $visibleNorms->count(),
                'is_trial' => (bool) $subscriptions->contains(fn ($s) => $s->isInTrial()),
                'days_remaining' => $subscriptions->min(fn ($s) => $s->daysRemaining()),
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    private function emptyCatalog(?Site $site): array
    {
        return [
            'norms' => [],
            'modules' => [],
            'sub_modules' => [],
            'sections' => [],
            'meta' => [
                'site_id' => $site?->id,
                'site_name' => $site?->name,
                'enterprise_id' => $site?->enterprise_id,
                'active_subscriptions_count' => 0,
                'linked_norms_count' => 0,
                'active_norms_count' => 0,
                'is_trial' => false,
                'days_remaining' => 0,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    private function modulePermissions(User $user, string $moduleCode): array
    {
        return $this->accessDecisionService->modulePermissions($user, $moduleCode);
    }

    private function subModulePermissions(User $user, string $subModuleCode): array
    {
        return $this->accessDecisionService->subModulePermissions($user, $subModuleCode);
    }

    private function sectionPermissions(User $user, string $sectionCode): array
    {
        return $this->accessDecisionService->sectionPermissions($user, $sectionCode);
    }

    private function normalizeCode(?string $code): string
    {
        return mb_strtolower(trim((string) $code));
    }

    private function normalizeRoute(?string $route, string $fallback): string
    {
        $normalized = trim((string) $route);
        if ($normalized === '') {
            $normalized = $fallback;
        }

        if (str_starts_with($normalized, '/clienta')) {
            $normalized = preg_replace('#^/clienta#', '/company', $normalized) ?? $normalized;
        }

        $routeMap = [
            '/company/iso/leadership/policy' => '/company/leadership/policy',
            '/company/iso/leadership/politique' => '/company/leadership/policy',
            '/company/leadership/politique' => '/company/leadership/policy',
            '/company/leadership/job-description' => '/company/leadership/fiche_poste',
            '/company/leadership/job-descriptions' => '/company/leadership/fiche_poste',
            '/company/leadership/roles/fiche-poste' => '/company/leadership/fiche_poste',
            '/company/leadership/roles/fiches' => '/company/leadership/fiche_responsabilite',
            '/company/responsibilities' => '/company/leadership/fiche_responsabilite',
            '/company/contexte/profil' => '/company/iso/context/management-system',
            '/company/contexte/parties-interessees' => '/company/iso/context/stakeholders',
            '/company/contexte/domaine-application' => '/company/iso/context/application-scope',
            '/company/contexte/systeme-management' => '/company/iso/context/management-system',
            '/company/planification/risques' => '/company/iso/planning/risks-opportunities',
            '/company/planification/objectifs' => '/company/iso/planning/objectives',
            '/company/planification/plans-action' => '/company/iso/planning/action-plans',
            '/company/planification/indicateurs' => '/company/indicators',
            '/company/iso/support/resources' => '/company/iso/support/equipment',
            '/company/iso/support/competencies' => '/company/iso/support/training',
            '/company/iso/support/documents' => '/company/iso/support/document-inventory',
            '/company/support/ressources' => '/company/iso/support/equipment',
            '/company/support/competences' => '/company/iso/support/training',
            '/company/support/sensibilisation' => '/company/iso/support/communication',
            '/company/support/communication' => '/company/iso/support/communication',
            '/company/non-conformities' => '/company/nonconformities',
            '/company/iso/performance/indicators' => '/company/indicators',
            '/company/iso/performance/management-review' => '/company/performance/revue-direction',
            '/company/performance/revue-processus' => '/company/performance/surveillance/process-review',
            '/company/iso/operations/procedures' => '/company/processes',
            '/company/leadership/consultation' => '/company/leadership/consultation',
            '/company/support/satisfaction' => '/company/performance/surveillance',
            '/company/audits' => '/company/performance/audits',
            '/company/management-reviews' => '/company/performance/revue-direction',
            '/company/reclamations' => '/company/performance/surveillance',
            '/company/performance/process-review' => '/company/performance/surveillance/process-review',

            // ISO planning advanced routes -> planning pivot pages
            '/company/iso/planning/duerp' => '/company/planning/duerp',
            '/company/iso/planning/hazard-identification' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/legal-requirements' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/information-security-risks' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/risk-treatment' => '/company/iso/planning/action-plans',
            '/company/iso/planning/statement-applicability' => '/company/iso/planning/action-plans',
            '/company/iso/planning/haccp-analysis' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/critical-control-points' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/prerequisite-programs' => '/company/iso/planning/action-plans',
            '/company/iso/planning/haccp-plan' => '/company/iso/planning/action-plans',
            '/company/iso/planning/revue' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/energy' => '/company/iso/planning/risks-opportunities',
            '/company/iso/planning/analysts' => '/company/iso/planning/action-plans',
            '/company/iso/planning/energy-indicators' => '/company/indicators',
            '/company/iso/planning/energy-objectives' => '/company/iso/planning/objectives',

            // ISO operations advanced routes -> process pivot page
            '/company/iso/operations/environmental-control' => '/company/processes',
            '/company/iso/operations/emergency-preparedness' => '/company/processes',
            '/company/iso/operations/operational-planning-control' => '/company/iso/operations/operational-planning-control',
            '/company/iso/operations/product-service-requirements' => '/company/iso/operations/product-service-requirements',
            '/company/iso/operations/design-development-products-services' => '/company/iso/operations/design-development-products-services',
            '/company/iso/operations/provider-management' => '/company/iso/operations/provider-management',
            '/company/iso/operations/production-service-provision' => '/company/iso/operations/production-service-provision',
            '/company/iso/operations/release-products-services' => '/company/iso/operations/release-products-services',
            '/company/iso/operations/control-nonconforming-outputs' => '/company/iso/operations/control-nonconforming-outputs',
            '/company/iso/operations/risk-elimination' => '/company/processes',
            '/company/iso/operations/change-management' => '/company/processes',
            '/company/iso/operations/procurement' => '/company/processes',
            '/company/iso/operations/asset-inventory' => '/company/processes',
            '/company/iso/operations/access-controls' => '/company/processes',
            '/company/iso/operations/cryptography' => '/company/processes',
            '/company/iso/operations/physical-security' => '/company/processes',
            '/company/iso/operations/business-continuity' => '/company/processes',
            '/company/iso/operations/food-hazard-control' => '/company/processes',
            '/company/iso/operations/food-traceability' => '/company/processes',
            '/company/iso/operations/allergen-management' => '/company/processes',
            '/company/iso/operations/supplier-control' => '/company/processes',
            '/company/iso/operations/energy-design' => '/company/processes',
            '/company/iso/operations/energy-procurement' => '/company/processes',
            '/company/iso/operations/equipment-procurement' => '/company/processes',

            // ISO performance advanced routes -> indicators / management reviews pivots
            '/company/iso/performance/accident-monitoring' => '/company/indicators',
            '/company/iso/performance/incident-investigation' => '/company/performance/revue-direction',
            '/company/iso/performance/security-incidents' => '/company/performance/revue-direction',
            '/company/iso/performance/gdpr-compliance' => '/company/performance/revue-direction',
            '/company/iso/performance/ccp-monitoring' => '/company/indicators',
            '/company/iso/performance/haccp-verification' => '/company/performance/revue-direction',
            '/company/iso/performance/microbiological-analysis' => '/company/indicators',
            '/company/iso/performance/energy-monitoring' => '/company/indicators',
            '/company/iso/performance/energy-compliance' => '/company/performance/revue-direction',
            '/company/iso/performance/energy-audit' => '/company/performance/audits',
        ];

        return $routeMap[$normalized] ?? $normalized;
    }

    private function isSubModuleAllowedForNorms(array $subModule, \Illuminate\Support\Collection $normCodes): bool
    {
        $subModuleNormCodes = collect($subModule['norm_codes'] ?? [])
            ->map(fn ($code) => mb_strtoupper(trim((string) $code)))
            ->filter()
            ->unique()
            ->values();

        if ($subModuleNormCodes->isNotEmpty()) {
            return $subModuleNormCodes->intersect($normCodes)->isNotEmpty();
        }

        $key = sprintf(
            '%s::%s',
            $this->normalizeCode($subModule['module_code'] ?? null),
            $this->normalizeCode($subModule['code'] ?? null),
        );

        // Guardrails legacy: keep historical routes but only when the corresponding norm is subscribed.
        // This fallback protects old catalog entries without explicit norm linkage.
        $restrictedMatrix = [
            'planification::duerp' => 'ISO-45001',
            'planification::hazard-identification' => 'ISO-45001',
            'planification::haccp-analysis' => 'ISO-22000',
            'planification::energy' => 'ISO-50001',
            'realisation::provider-management' => 'ISO-9001',
            'realisation::food-hazard-control' => 'ISO-22000',
            'realisation::energy-design' => 'ISO-50001',
            'surveillance::security-incidents' => 'ISO-27001',
        ];
        $requiredNorm = $restrictedMatrix[$key] ?? null;
        if (!$requiredNorm) {
            return true;
        }

        $requiredNormCode = mb_strtoupper(trim((string) $requiredNorm));
        if ($requiredNormCode === '') {
            return true;
        }

        return $normCodes->contains($requiredNormCode);
    }
}
