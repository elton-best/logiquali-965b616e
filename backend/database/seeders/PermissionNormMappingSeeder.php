<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Norm;
use App\Models\PermissionNormMapping;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionNormMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Get all norms
        $norms = Norm::all()->keyBy('code');
        $allNormIds = $norms->pluck('id')->toArray();

        // Get all modules
        $modules = Module::with('norms:id')->get()->keyBy('code');
        $subModules = SubModule::query()->get()->groupBy('module_id');
        $sections = SubModuleSection::query()->get()->groupBy('sub_module_id');

        // Load permission mappings from config
        $mappings = collect(config('permission_mappings', []));
        $moduleAliases = collect((array) $mappings->get('__module_aliases', []));
        $prefixMappings = collect((array) $mappings->get('__prefix_mappings', []));
        $autoMappingEnabled = (bool) $mappings->get('__enable_auto_mapping', false);
        $mappings = $mappings
            ->reject(fn ($_value, $key) => str_starts_with((string) $key, '__'))
            ->all();

        $this->command?->info("🔄 Mapping " . count($mappings) . " explicit permissions to norms/modules...");

        $createdCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        foreach ($mappings as $permissionName => $mapping) {
            try {
                // Find the permission
                $permission = Permission::where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->first();

                if (!$permission) {
                    $this->command?->warn("⏭️  Permission '{$permissionName}' not found (will skip)");
                    $skippedCount++;
                    continue;
                }

                // Get the module
                $moduleCode = $mapping['module_code'] ?? null;
                $module = $modules->get($moduleCode);

                if (!$module) {
                    $this->command?->warn("⏭️  Module '{$moduleCode}' not found for permission '{$permissionName}'");
                    $skippedCount++;
                    continue;
                }

                // Determine which norms this permission applies to
                $normIds = $mapping['norm_codes'] === null 
                    ? $allNormIds  // Shared: applies to all norms
                    : $this->getNormIdsByCode($mapping['norm_codes'], $norms);

                if (empty($normIds)) {
                    $this->command?->warn("⏭️  No norms found for permission '{$permissionName}'");
                    $skippedCount++;
                    continue;
                }

                $resolvedScope = $this->resolveScopeIds($mapping, $module, $subModules, $sections);
                if (($mapping['sub_module_code'] ?? null) && !$resolvedScope['sub_module_id']) {
                    $this->command?->warn("⏭️  Sub-module '{$mapping['sub_module_code']}' not found for permission '{$permissionName}'");
                    $skippedCount++;
                    continue;
                }

                if (($mapping['section_code'] ?? null) && !$resolvedScope['section_id']) {
                    $this->command?->warn("⏭️  Section '{$mapping['section_code']}' not found for permission '{$permissionName}'");
                    $skippedCount++;
                    continue;
                }

                $isShared = $mapping['is_shared'] ?? false;

                // Create mapping for each norm
                foreach ($normIds as $normId) {
                    // Delete existing mapping if it exists (idempotent)
                    PermissionNormMapping::where('permission_id', $permission->id)
                        ->where('norm_id', $normId)
                        ->delete();

                    // Create new mapping
                    PermissionNormMapping::create([
                        'permission_id' => $permission->id,
                        'norm_id' => $normId,
                        'module_id' => $module->id,
                        'sub_module_id' => $resolvedScope['sub_module_id'],
                        'section_id' => $resolvedScope['section_id'],
                        'is_shared' => $isShared,
                        'description' => $mapping['description'] ?? null,
                    ]);

                    $createdCount++;
                }

                $normCount = count($normIds);
                $this->command?->info("✓ {$permissionName} mapped to {$normCount} norm(s)");

            } catch (\Exception $e) {
                $this->command?->error("✗ Error mapping {$permissionName}: {$e->getMessage()}");
                $errorCount++;
            }
        }

        // Explicit prefix-based mapping (maintainable, deterministic).
        $prefixCreatedCount = $this->applyPrefixMappings($prefixMappings, $modules, $subModules, $sections, $norms, $allNormIds);
        $createdCount += $prefixCreatedCount;

        // Fallback auto-mapping for remaining norm-scoped permissions (disabled by default).
        $autoCreatedCount = 0;
        if ($autoMappingEnabled) {
            $autoCreatedCount = $this->autoMapRemainingPermissions($modules, $allNormIds, $moduleAliases);
        } else {
            $this->command?->info('ℹ️  Auto-mapping is disabled (strict explicit mapping mode).');
        }
        $createdCount += $autoCreatedCount;

        $this->command?->newLine();
        $this->command?->info("✅ Done! Created: {$createdCount} (prefix: {$prefixCreatedCount}, auto: {$autoCreatedCount}) | Skipped: {$skippedCount} | Errors: {$errorCount}");

        // Validate: find orphaned permissions (those not mapped)
        $this->validateOrphanedPermissions();
    }

    /**
     * Get norm IDs by their codes
     */
    private function getNormIdsByCode(array $normCodes, $norms): array
    {
        $ids = [];
        foreach ($normCodes as $code) {
            if ($norm = $norms->get($code)) {
                $ids[] = $norm->id;
            }
        }
        return $ids;
    }

    /**
     * Validate that all permissions are mapped
     */
    private function validateOrphanedPermissions(): void
    {
        $systemPrefixes = User::getSystemPermissionPrefixes()->all();

        $mappedPermissionIds = PermissionNormMapping::distinct()
            ->pluck('permission_id')
            ->toArray();

        $allPermissionIds = Permission::where('guard_name', 'web')
            ->pluck('id')
            ->toArray();

        $orphaned = array_diff($allPermissionIds, $mappedPermissionIds);

        if (!empty($orphaned)) {
            $orphanedPerms = Permission::whereIn('id', $orphaned)->pluck('name')->toArray();
            $criticalOrphans = collect($orphanedPerms)
                ->filter(function (string $name) use ($systemPrefixes) {
                    $prefix = mb_strtolower(trim(explode('.', $name)[0] ?? ''));
                    return $prefix !== '' && !in_array($prefix, $systemPrefixes, true);
                })
                ->values()
                ->all();

            if (!empty($criticalOrphans)) {
                $this->command?->warn('⚠️  Critical orphaned permissions (norm-scoped, not mapped): ' . implode(', ', $criticalOrphans));
            } else {
                $this->command?->info('✅ No critical orphaned norm-scoped permissions. Remaining orphans are system/global.');
            }
        } else {
            $this->command?->info('✅ All permissions are mapped!');
        }
    }

    private function autoMapRemainingPermissions($modules, array $allNormIds, $moduleAliases): int
    {
        $created = 0;
        $systemPrefixes = User::getSystemPermissionPrefixes()->all();

        $mappedPermissionIds = PermissionNormMapping::query()
            ->pluck('permission_id')
            ->unique()
            ->values()
            ->all();

        $remainingPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('id', $mappedPermissionIds)
            ->get(['id', 'name']);

        foreach ($remainingPermissions as $permission) {
            $permissionName = mb_strtolower(trim((string) $permission->name));
            $prefix = mb_strtolower(trim(explode('.', $permissionName)[0] ?? ''));

            if ($prefix === '' || in_array($prefix, $systemPrefixes, true)) {
                continue;
            }

            $resolvedModuleCode = $this->resolveModuleCodeFromPermissionName(
                $permissionName,
                $prefix,
                $moduleAliases,
                $modules
            );

            if (!$resolvedModuleCode) {
                continue;
            }

            $module = $modules->get($resolvedModuleCode);
            if (!$module) {
                continue;
            }

            $moduleNormIds = $module->norms->pluck('id')->map(fn ($id) => (int) $id)->all();
            $normIds = !empty($moduleNormIds) ? $moduleNormIds : $allNormIds;
            $isShared = count($normIds) > 1;

            foreach ($normIds as $normId) {
                PermissionNormMapping::updateOrCreate(
                    [
                        'permission_id' => $permission->id,
                        'norm_id' => (int) $normId,
                    ],
                    [
                        'module_id' => (int) $module->id,
                        'is_shared' => $isShared,
                        'description' => 'Auto-mapped from permission prefix',
                    ]
                );
                $created++;
            }
        }

        return $created;
    }

    private function resolveModuleCodeFromPermissionName(
        string $permissionName,
        string $prefix,
        $moduleAliases,
        $modules
    ): ?string {
        $direct = (string) ($moduleAliases->get($prefix) ?? $prefix);
        if ($modules->has($direct)) {
            return $direct;
        }

        $tokens = collect(preg_split('/[._]/', $permissionName) ?: [])
            ->map(fn ($token) => mb_strtolower(trim((string) $token)))
            ->filter()
            ->values();

        foreach ($tokens as $token) {
            $candidate = (string) ($moduleAliases->get($token) ?? $token);
            if ($modules->has($candidate)) {
                return $candidate;
            }

            // Heuristic singular fallback: documents -> document, activities -> activity, etc.
            if (str_ends_with($token, 's')) {
                $singular = substr($token, 0, -1);
                $candidate = (string) ($moduleAliases->get($singular) ?? $singular);
                if ($candidate !== '' && $modules->has($candidate)) {
                    return $candidate;
                }
            }
        }

        // Heuristic by keyword families for non-standard names (create_documents, view_all_tasks, ...).
        $keywordToModule = [
            'document' => 'support',
            'documents' => 'support',
            'task' => 'planification',
            'tasks' => 'planification',
            'activity' => 'realisation',
            'activities' => 'realisation',
            'action' => 'amelioration',
            'actions' => 'amelioration',
            'nomenclature' => 'support',
            'holiday' => 'support',
            'holidays' => 'support',
        ];

        foreach ($keywordToModule as $keyword => $moduleCode) {
            if (str_contains($permissionName, $keyword) && $modules->has($moduleCode)) {
                return $moduleCode;
            }
        }

        return null;
    }

    private function resolveScopeIds(array $mapping, Module $module, $subModulesByModuleId, $sectionsBySubModuleId): array
    {
        $subModuleId = null;
        $sectionId = null;

        $subModuleCode = trim((string) ($mapping['sub_module_code'] ?? ''));
        $sectionCode = trim((string) ($mapping['section_code'] ?? ''));

        if ($subModuleCode !== '') {
            $moduleSubModules = collect($subModulesByModuleId->get($module->id, collect()));
            $subModule = $moduleSubModules->first(function ($item) use ($subModuleCode) {
                return mb_strtolower((string) $item->code) === mb_strtolower($subModuleCode);
            });

            if ($subModule) {
                $subModuleId = (int) $subModule->id;

                if ($sectionCode !== '') {
                    $subModuleSections = collect($sectionsBySubModuleId->get($subModule->id, collect()));
                    $section = $subModuleSections->first(function ($item) use ($sectionCode) {
                        return mb_strtolower((string) $item->code) === mb_strtolower($sectionCode);
                    });

                    if ($section) {
                        $sectionId = (int) $section->id;
                    }
                }
            }
        }

        return [
            'sub_module_id' => $subModuleId,
            'section_id' => $sectionId,
        ];
    }

    private function applyPrefixMappings($prefixMappings, $modules, $subModules, $sections, $norms, array $allNormIds): int
    {
        if ($prefixMappings->isEmpty()) {
            return 0;
        }

        $created = 0;

        $mappedPermissionIds = PermissionNormMapping::query()
            ->pluck('permission_id')
            ->unique()
            ->values()
            ->all();

        $remainingPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('id', $mappedPermissionIds)
            ->get(['id', 'name']);

        foreach ($remainingPermissions as $permission) {
            $permissionName = mb_strtolower(trim((string) $permission->name));
            $selectedRule = null;

            foreach ($prefixMappings as $rule) {
                $prefix = mb_strtolower(trim((string) ($rule['prefix'] ?? '')));
                if ($prefix === '') {
                    continue;
                }

                if ($permissionName === $prefix || str_starts_with($permissionName, $prefix . '.')) {
                    $selectedRule = $rule;
                    break;
                }
            }

            if (!$selectedRule) {
                continue;
            }

            $moduleCode = (string) ($selectedRule['module_code'] ?? '');
            $module = $modules->get($moduleCode);
            if (!$module) {
                continue;
            }

            $normCodes = $selectedRule['norm_codes'] ?? null;
            $normIds = $normCodes === null
                ? $allNormIds
                : $this->getNormIdsByCode((array) $normCodes, $norms);

            if (empty($normIds)) {
                continue;
            }

            $resolvedScope = $this->resolveScopeIds($selectedRule, $module, $subModules, $sections);
            $isShared = (bool) ($selectedRule['is_shared'] ?? (count($normIds) > 1));

            foreach ($normIds as $normId) {
                PermissionNormMapping::updateOrCreate(
                    [
                        'permission_id' => (int) $permission->id,
                        'norm_id' => (int) $normId,
                    ],
                    [
                        'module_id' => (int) $module->id,
                        'sub_module_id' => $resolvedScope['sub_module_id'],
                        'section_id' => $resolvedScope['section_id'],
                        'is_shared' => $isShared,
                        'description' => (string) ($selectedRule['description'] ?? 'Prefix explicit mapping'),
                    ]
                );
                $created++;
            }
        }

        return $created;
    }
}
