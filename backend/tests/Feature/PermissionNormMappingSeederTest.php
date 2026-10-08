<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Norm;
use App\Models\PermissionNormMapping;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use Database\Seeders\PermissionNormMappingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PermissionNormMappingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_norm_mapping_seeder_is_idempotent_and_persists_scope_fields(): void
    {
        $norm = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualité',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $moduleContexte = Module::create([
            'code' => 'contexte',
            'name' => 'Contexte',
            'iso_point' => 4,
        ]);

        $moduleLeadership = Module::create([
            'code' => 'leadership',
            'name' => 'Leadership',
            'iso_point' => 5,
        ]);

        $subComprehension = SubModule::create([
            'module_id' => $moduleContexte->id,
            'name' => 'Compréhension organisme',
            'code' => 'comprehension_organisme',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $subRoles = SubModule::create([
            'module_id' => $moduleLeadership->id,
            'name' => 'Rôles et responsabilités',
            'code' => 'roles_responsabilites',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        $sectionOrganigramme = SubModuleSection::create([
            'sub_module_id' => $subRoles->id,
            'name' => 'Organigramme',
            'code' => 'organigramme',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        Permission::create(['name' => 'contexte.manage', 'guard_name' => 'web']);
        Permission::create(['name' => 'leadership.manage', 'guard_name' => 'web']);

        $seeder = new PermissionNormMappingSeeder();
        $seeder->run();
        $countAfterFirstRun = PermissionNormMapping::count();

        $seeder->run();
        $countAfterSecondRun = PermissionNormMapping::count();

        $this->assertGreaterThan(0, $countAfterFirstRun);
        $this->assertSame($countAfterFirstRun, $countAfterSecondRun, 'Le seeder doit être idempotent sur le volume de mappings.');

        $contexteMapping = PermissionNormMapping::query()
            ->whereHas('permission', fn ($q) => $q->where('name', 'contexte.manage'))
            ->where('norm_id', $norm->id)
            ->first();

        $this->assertNotNull($contexteMapping);
        $this->assertSame($moduleContexte->id, (int) $contexteMapping->module_id);
        $this->assertSame($subComprehension->id, (int) $contexteMapping->sub_module_id);
        $this->assertNull($contexteMapping->section_id);

        $leadershipMapping = PermissionNormMapping::query()
            ->whereHas('permission', fn ($q) => $q->where('name', 'leadership.manage'))
            ->where('norm_id', $norm->id)
            ->first();

        $this->assertNotNull($leadershipMapping);
        $this->assertSame($moduleLeadership->id, (int) $leadershipMapping->module_id);
        $this->assertSame($subRoles->id, (int) $leadershipMapping->sub_module_id);
        $this->assertSame($sectionOrganigramme->id, (int) $leadershipMapping->section_id);
    }
}

