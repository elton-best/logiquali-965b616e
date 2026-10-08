<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormationImportFileTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $admin;
    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            EnsureEnterpriseOwnership::class,
        ]);

        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
        ]);

        $role = Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        foreach (['formations.read', 'formations.create', 'formations.update', 'formations.delete'] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }
        $role->givePermissionTo(['formations.read', 'formations.create', 'formations.update', 'formations.delete']);

        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
        $this->admin->assignRole($role);

        $this->target = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
    }

    public function test_import_file_skips_incomplete_rows_and_creates_valid_rows(): void
    {
        $csv = implode("\n", [
            'N°,Nom de la formation,Jan,Fev,Mar,Avr,Mai,Juin,Juil,Aout,Sept,Oct,Nov,Dec,Formateur,Cible(s),Date Suivi,Observations,,',
            ',,jan,fev,mar,avr,mai,juin,juil,aout,sept,oct,nov,dec,,,,,debut,fin',
            '1,Formation valide,,,,,,,,,,,,,Alice,Personnel,,RAS,2026-04-10,2026-04-12',
            '2,Formation incomplète,,,,,,,,,,,,,Bob,Personnel,,Sans dates,,',
        ]);
        $file = UploadedFile::fake()->createWithContent('formations.csv', $csv);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/formations/import-file', [
                'file' => $file,
                'year' => 2026,
                'target_user_ids' => [$this->target->id],
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'created' => 1,
            'updated' => 0,
            'skipped' => 1,
            'incomplete' => 1,
        ]);

        $this->assertDatabaseHas('formations', [
            'enterprise_id' => $this->enterprise->id,
            'designation' => 'Formation valide',
            'frequency' => 'ponctuelle',
            'period_mode' => 'custom',
            'plan_year' => 2026,
        ]);
        $this->assertDatabaseMissing('formations', [
            'enterprise_id' => $this->enterprise->id,
            'designation' => 'Formation incomplète',
        ]);

        $this->assertDatabaseHas('training_plans', [
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'year' => 2026,
            'planned_formations' => 1,
        ]);
    }
}
