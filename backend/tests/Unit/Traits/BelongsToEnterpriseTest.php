<?php

namespace Tests\Unit\Traits;

use App\Models\User;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\Equipement;
use App\Models\CodificationElement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class BelongsToEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles nécessaires
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin_entreprise', 'guard_name' => 'web']);
    }

    #[Test]
    public function it_auto_fills_enterprise_id_on_creation()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $user = User::factory()->createOne([
            'enterprise_id' => $enterprise->id,
            'site_id' => $site->id,
            'user_type' => 'company',
        ]);

        // Données codification minimales requises par Equipement.
        $categorie = CodificationElement::create([
            'type' => 'categorie',
            'code' => 'CAT1',
            'libelle' => 'Categorie 1',
            'actif' => true,
        ]);
        $localisation = CodificationElement::create([
            'type' => 'localisation',
            'code' => 'LOC1',
            'libelle' => 'Localisation 1',
            'actif' => true,
        ]);

        $this->actingAs($user);

        $equipement = Equipement::create([
            'enterprise_id' => null,
            'site_id' => null,
            'code_complet' => 'EQ-AUTO-001',
            'categorie_id' => $categorie->id,
            'localisation_id' => $localisation->id,
            'nom_commun' => 'Machine test',
            'nom_commun_abrege' => 'MAC',
            'indice' => '001',
            'annee_acquisition' => 2026,
            'etat' => 'bon',
            'necessite_maintenance' => false,
            'actif' => true,
        ]);

        $this->assertEquals($enterprise->id, $equipement->enterprise_id);
        $this->assertEquals($site->id, $equipement->site_id);
    }

    #[Test]
    public function it_filters_by_site_for_normal_users()
    {
        $enterprise = Enterprise::factory()->create();
        $site1 = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $site2 = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $user1 = User::factory()->createOne(['enterprise_id' => $enterprise->id, 'site_id' => $site1->id]);
        $user2 = User::factory()->createOne(['enterprise_id' => $enterprise->id, 'site_id' => $site2->id]);

        // Créer équipements pour chaque site
        Equipement::factory()->create(['enterprise_id' => $enterprise->id, 'site_id' => $site1->id]);
        Equipement::factory()->create(['enterprise_id' => $enterprise->id, 'site_id' => $site2->id]);

        // User1 ne voit que son site
        $this->actingAs($user1);
        $this->assertEquals(1, Equipement::count());

        // User2 ne voit que son site
        $this->actingAs($user2);
        $this->assertEquals(1, Equipement::count());
    }

    #[Test]
    public function admin_entreprise_sees_all_sites_in_enterprise()
    {
        $enterprise = Enterprise::factory()->create();
        $site1 = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        $site2 = Site::factory()->create(['enterprise_id' => $enterprise->id]);

        $adminEnterprise = User::factory()->createOne(['enterprise_id' => $enterprise->id]);
        $adminEnterprise->assignRole('admin_entreprise');

        // Créer équipements pour chaque site
        Equipement::factory()->create(['enterprise_id' => $enterprise->id, 'site_id' => $site1->id]);
        Equipement::factory()->create(['enterprise_id' => $enterprise->id, 'site_id' => $site2->id]);

        // Admin entreprise voit tous les sites de son entreprise
        $this->actingAs($adminEnterprise);
        $this->assertEquals(2, Equipement::count());
    }

    #[Test]
    public function super_admin_sees_all_records()
    {
        $enterprise1 = Enterprise::factory()->create();
        $enterprise2 = Enterprise::factory()->create();

        $superAdmin = User::factory()->createOne();
        $superAdmin->assignRole('super_admin');

        // Créer équipements pour chaque entreprise
        $cat1 = CodificationElement::create(['type' => 'categorie', 'code' => 'C1', 'libelle' => 'Cat1']);
        $loc1 = CodificationElement::create(['type' => 'localisation', 'code' => 'L1', 'libelle' => 'Loc1']);
        $cat2 = CodificationElement::create(['type' => 'categorie', 'code' => 'C2', 'libelle' => 'Cat2']);
        $loc2 = CodificationElement::create(['type' => 'localisation', 'code' => 'L2', 'libelle' => 'Loc2']);

        $site1 = Site::factory()->create(['enterprise_id' => $enterprise1->id]);
        $site2 = Site::factory()->create(['enterprise_id' => $enterprise2->id]);

        Equipement::factory()->create([
            'enterprise_id' => $enterprise1->id,
            'site_id' => $site1->id,
            'categorie_id' => $cat1->id,
            'localisation_id' => $loc1->id
        ]);
        Equipement::factory()->create([
            'enterprise_id' => $enterprise2->id,
            'site_id' => $site2->id,
            'categorie_id' => $cat2->id,
            'localisation_id' => $loc2->id
        ]);

        // Super admin voit tout
        $this->actingAs($superAdmin);
        $this->assertEquals(2, Equipement::count());
    }
}
