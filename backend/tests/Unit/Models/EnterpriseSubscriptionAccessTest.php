<?php

namespace Tests\Unit\Models;

use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Module;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\Site;
use App\Models\SubModule;
use App\Models\SubModuleSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EnterpriseSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_sub_modules_by_subscribed_norms_without_losing_common_sub_modules(): void
    {
        $enterprise = Enterprise::factory()->create();
        $site = Site::factory()->create([
            'enterprise_id' => $enterprise->id,
        ]);

        $iso9001 = Norm::create([
            'code' => 'ISO 9001:2015',
            'name' => 'Management de la qualite',
            'description' => 'QMS',
            'domain' => 'quality',
            'status' => 'published',
        ]);

        $iso14001 = Norm::create([
            'code' => 'ISO 14001:2015',
            'name' => 'Management environnemental',
            'description' => 'EMS',
            'domain' => 'environment',
            'status' => 'published',
        ]);

        $iso45001 = Norm::create([
            'code' => 'ISO 45001:2018',
            'name' => 'Sante securite',
            'description' => 'OHSMS',
            'domain' => 'security',
            'status' => 'published',
        ]);

        $planification = Module::create([
            'code' => 'planification',
            'name' => 'Planification',
            'description' => 'Point 6',
            'icon' => 'mdi-calendar-check',
            'iso_point' => 6,
            'order' => 3,
            'is_active' => true,
        ]);

        // Module commun lie a plusieurs normes.
        $planification->norms()->attach([$iso9001->id, $iso14001->id, $iso45001->id]);

        // Sous-module "commun": pas de liaison explicite norm_sub_module.
        $commonSubModule = SubModule::create([
            'module_id' => $planification->id,
            'code' => 'risques_opportunites',
            'name' => 'Risques et opportunites',
            'icon' => 'mdi-alert-octagon',
            'order' => 1,
            'is_active' => true,
            'is_common' => true,
        ]);

        // Sous-module specifique ISO 14001.
        $iso14001SubModule = SubModule::create([
            'module_id' => $planification->id,
            'code' => 'aspects_environnementaux',
            'name' => 'Aspects environnementaux',
            'icon' => 'mdi-leaf',
            'order' => 10,
            'is_active' => true,
        ]);
        DB::table('norm_sub_module')->insert([
            'norm_id' => $iso14001->id,
            'sub_module_id' => $iso14001SubModule->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sous-module specifique ISO 45001 (doit etre exclu).
        $iso45001SubModule = SubModule::create([
            'module_id' => $planification->id,
            'code' => 'duerp',
            'name' => 'DUERP',
            'icon' => 'mdi-shield-alert',
            'order' => 11,
            'is_active' => true,
        ]);
        DB::table('norm_sub_module')->insert([
            'norm_id' => $iso45001->id,
            'sub_module_id' => $iso45001SubModule->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Sections rattachees aux sous-modules specifiques.
        SubModuleSection::create([
            'sub_module_id' => $iso14001SubModule->id,
            'code' => 'obligations_conformite_env',
            'name' => 'Obligations de conformite',
            'icon' => 'mdi-gavel',
            'order' => 1,
            'is_active' => true,
        ]);

        SubModuleSection::create([
            'sub_module_id' => $iso45001SubModule->id,
            'code' => 'exigences_legales_sst',
            'name' => 'Exigences legales SST',
            'icon' => 'mdi-scale-balance',
            'order' => 1,
            'is_active' => true,
        ]);

        $offer = Offer::create([
            'ref' => 'OFF-TEST-ISO9001-14001',
            'name' => 'Offre test',
            'description' => 'Offre multi-normes',
            'is_active' => true,
            'price' => 1000,
            'duration_months' => 12,
        ]);
        $offer->norms()->attach([$iso9001->id, $iso14001->id]);

        $subscription = EnterpriseSubscription::create([
            'ref' => 'SUB-TEST-001',
            'offer_id' => $offer->id,
            'site_id' => $site->id,
            'start_date' => now(),
            'expiration_date' => now()->addMonth(),
            'is_active' => true,
            'is_trial' => false,
            'status' => 'active',
            'payment_status' => 'completed',
            'subscription_type' => 'primary',
        ]);
        $subscription->load('offer.norms');

        $subModuleCodes = $subscription->getAccessibleSubModules()->pluck('code')->all();
        $sectionCodes = $subscription->getAccessibleSections()->pluck('code')->all();

        $this->assertContains('risques_opportunites', $subModuleCodes);
        $this->assertContains('aspects_environnementaux', $subModuleCodes);
        $this->assertNotContains('duerp', $subModuleCodes);

        $this->assertContains('obligations_conformite_env', $sectionCodes);
        $this->assertNotContains('exigences_legales_sst', $sectionCodes);
    }
}
