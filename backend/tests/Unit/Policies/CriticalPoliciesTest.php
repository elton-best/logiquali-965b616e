<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Models\Equipement;
use App\Models\Maintenance;
use App\Models\Habilitation;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\CodificationElement;
use App\Policies\EquipementPolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\HabilitationPolicy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use PHPUnit\Framework\Attributes\Test;

class CriticalPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected $enterprise;
    protected $site;
    protected $superAdmin;
    protected $enterpriseAdmin;
    protected $siteUser;
    protected $equipement;
    protected $maintenance;
    protected $habilitation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->enterprise = Enterprise::factory()->create();
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        // Create roles and permissions
        $this->createRolesAndPermissions();

        // Create users
        $this->superAdmin = User::factory()->create([
            'user_type' => User::TYPE_SUPER_ADMIN,
        ]);

        $this->enterpriseAdmin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id
        ]);
        $this->enterpriseAdmin->assignRole('admin_entreprise');

        $this->siteUser = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id
        ]);
        $this->siteUser->assignRole('site_manager');

        // Create test models with proper required fields
        $categorie = CodificationElement::factory()->categorie()->create();
        $localisation = CodificationElement::factory()->localisation()->create();

        $this->equipement = Equipement::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'categorie_id' => $categorie->id,
            'localisation_id' => $localisation->id
        ]);

        $this->maintenance = Maintenance::create([
            'equipement_id' => $this->equipement->id,
            'type' => 'preventive',
            'date_prevue' => now()->addDays(30),
            'statut' => 'planifie'
        ]);

        $this->habilitation = Habilitation::factory()->create([
            'user_id' => $this->siteUser->id,
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id
        ]);
    }

    private function createRolesAndPermissions()
    {
        // Create roles
        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'site_manager', 'guard_name' => 'web']);

        // Create permissions
        $permissions = [
            'equipements.read',
            'equipements.create',
            'equipements.update',
            'equipements.delete',
            'maintenances.read',
            'maintenances.create',
            'maintenances.update',
            'maintenances.delete',
            'maintenances.manage',
            'habilitations.read',
            'habilitations.create',
            'habilitations.update',
            'habilitations.delete',
            'habilitations.manage'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        Role::findByName('admin_entreprise')->givePermissionTo($permissions);
        Role::findByName('site_manager')->givePermissionTo([
            'equipements.read',
            'maintenances.read',
            'maintenances.update',
            'habilitations.read'
        ]);
    }

    #[Test]
    public function equipement_policy_view_permissions()
    {
        $policy = new EquipementPolicy();

        // Super admin can view all
        $this->assertTrue($policy->view($this->superAdmin, $this->equipement));

        // Enterprise admin can view in their enterprise
        $this->assertTrue($policy->view($this->enterpriseAdmin, $this->equipement));

        // Site user can view in their site
        $this->assertTrue($policy->view($this->siteUser, $this->equipement));
    }

    #[Test]
    public function maintenance_policy_view_permissions()
    {
        $policy = new MaintenancePolicy();

        // Super admin can view all
        $this->assertTrue($policy->view($this->superAdmin, $this->maintenance));

        // Enterprise admin can view in their enterprise
        $this->assertTrue($policy->view($this->enterpriseAdmin, $this->maintenance));

        // Site user can view in their site
        $this->assertTrue($policy->view($this->siteUser, $this->maintenance));
    }

    #[Test]
    public function habilitation_policy_view_permissions()
    {
        $policy = new HabilitationPolicy();

        // Super admin can view all
        $this->assertTrue($policy->view($this->superAdmin, $this->habilitation));

        // Enterprise admin can view in their enterprise
        $this->assertTrue($policy->view($this->enterpriseAdmin, $this->habilitation));

        // User can view their own habilitation
        $this->assertTrue($policy->view($this->siteUser, $this->habilitation));
    }
}
