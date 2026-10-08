<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\EpiStock;
use App\Models\EpiCatalogue;
use App\Models\Enterprise;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EpiStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_epi_stock_belongs_to_catalogue()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        $catalogue = EpiCatalogue::create([
            'enterprise_id' => $enterprise->id,
            'reference' => 'EPI-001',
            'designation' => 'Casque',
            'categorie' => 'tete',
            'norme' => 'EN 397',
            'prix_unitaire' => 25.00
        ]);

        $stock = EpiStock::create([
            'enterprise_id' => $enterprise->id,
            'epi_catalogue_id' => $catalogue->id,
            'site_id' => $site->id,
            'quantite_stock' => 50,
            'seuil_alerte' => 10
        ]);

        $this->assertInstanceOf(EpiCatalogue::class, $stock->catalogue);
        $this->assertEquals('Casque', $stock->catalogue->designation);
    }

    public function test_is_alerte_seuil()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        $catalogue = EpiCatalogue::create([
            'enterprise_id' => $enterprise->id,
            'reference' => 'EPI-002',
            'designation' => 'Gants',
            'categorie' => 'mains_bras',
            'norme' => 'EN 388',
            'prix_unitaire' => 5.00
        ]);

        $stock = EpiStock::create([
            'enterprise_id' => $enterprise->id,
            'epi_catalogue_id' => $catalogue->id,
            'site_id' => $site->id,
            'quantite_stock' => 8,
            'seuil_alerte' => 10
        ]);

        $this->assertTrue($stock->isAlerteSeuil());
    }

    public function test_alerte_seuil_scope()
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create(['enterprise_id' => $enterprise->id]);
        
        $catalogue1 = EpiCatalogue::create([
            'enterprise_id' => $enterprise->id,
            'reference' => 'EPI-003',
            'designation' => 'Lunettes',
            'categorie' => 'yeux_visage',
            'norme' => 'EN 166',
            'prix_unitaire' => 15.00
        ]);

        $catalogue2 = EpiCatalogue::create([
            'enterprise_id' => $enterprise->id,
            'reference' => 'EPI-004',
            'designation' => 'Chaussures',
            'categorie' => 'pieds_jambes',
            'norme' => 'EN 345',
            'prix_unitaire' => 80.00
        ]);

        EpiStock::create([
            'enterprise_id' => $enterprise->id,
            'epi_catalogue_id' => $catalogue1->id,
            'site_id' => $site->id,
            'quantite_stock' => 5,
            'seuil_alerte' => 10
        ]);

        EpiStock::create([
            'enterprise_id' => $enterprise->id,
            'epi_catalogue_id' => $catalogue2->id,
            'site_id' => $site->id,
            'quantite_stock' => 50,
            'seuil_alerte' => 10
        ]);

        $alertes = EpiStock::alerteSeuil()->get();
        $this->assertCount(1, $alertes);
    }
}
