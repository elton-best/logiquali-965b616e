<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Module;
use App\Models\Norm;
use App\Models\Offer;
use App\Models\Site;
use App\Models\SubModule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessCatalogNormsTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $siteA;
    private Site $siteB;
    private User $user;

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
        $this->siteA = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_headquarter' => true,
        ]);
        $this->siteB = Site::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'is_headquarter' => false,
        ]);

        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->siteA->id,
            'user_type' => 'company',
            'role' => 'admin_entreprise',
            'is_active' => true,
        ]);
    }

    public function test_access_catalog_returns_norms_of_active_subscriptions_for_current_site(): void
    {
        $normA = Norm::factory()->create(['code' => 'ISO-9001', 'status' => 'published']);
        $normB = Norm::factory()->create(['code' => 'ISO-14001', 'status' => 'published']);

        $offerA = Offer::factory()->create();
        $offerA->norms()->attach($normA->id);
        $offerB = Offer::factory()->create();
        $offerB->norms()->attach($normB->id);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offerA->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(30),
            'status' => 'active',
        ]);
        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteB->id,
            'offer_id' => $offerB->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.site_id', $this->siteA->id);
        $response->assertJsonPath('meta.active_norms_count', 1);
        $response->assertJsonCount(1, 'norms');
        $response->assertJsonPath('norms.0.code', 'ISO-9001');
    }

    public function test_access_catalog_switches_norms_with_site_header(): void
    {
        $normA = Norm::factory()->create(['code' => 'ISO-9001', 'status' => 'published']);
        $normB = Norm::factory()->create(['code' => 'ISO-45001', 'status' => 'published']);

        $offerA = Offer::factory()->create();
        $offerA->norms()->attach($normA->id);
        $offerB = Offer::factory()->create();
        $offerB->norms()->attach($normB->id);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offerA->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(30),
            'status' => 'active',
        ]);
        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteB->id,
            'offer_id' => $offerB->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['X-Site-ID' => (string) $this->siteB->id])
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.site_id', $this->siteB->id);
        $response->assertJsonPath('meta.active_norms_count', 1);
        $response->assertJsonPath('norms.0.code', 'ISO-45001');
    }

    public function test_access_catalog_excludes_expired_subscriptions(): void
    {
        $norm = Norm::factory()->create(['code' => 'ISO-14001', 'status' => 'published']);
        $offer = Offer::factory()->create();
        $offer->norms()->attach($norm->id);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offer->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subMonths(2),
            'expiration_date' => now()->subDay(),
            'status' => 'expired',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.site_id', $this->siteA->id);
        $response->assertJsonPath('meta.active_norms_count', 0);
        $response->assertJsonCount(0, 'norms');
    }

    public function test_access_catalog_includes_norms_when_trial_is_active(): void
    {
        $norm = Norm::factory()->create(['code' => 'ISO-50001', 'status' => 'published']);
        $offer = Offer::factory()->create();
        $offer->norms()->attach($norm->id);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offer->id,
            'is_active' => true,
            'is_trial' => true,
            'trial_ends_at' => now()->addDays(10),
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(1),
            'status' => 'trial',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.site_id', $this->siteA->id);
        $response->assertJsonPath('meta.active_norms_count', 1);
        $response->assertJsonPath('meta.is_trial', true);
        $response->assertJsonPath('norms.0.code', 'ISO-50001');
    }

    public function test_access_catalog_includes_draft_norms_and_excludes_archived_norms(): void
    {
        $draftNorm = Norm::factory()->create(['code' => 'ISO-DRAFT', 'status' => 'draft']);
        $archivedNorm = Norm::factory()->create(['code' => 'ISO-ARCHIVED', 'status' => 'archived']);

        $offer = Offer::factory()->create();
        $offer->norms()->attach([$draftNorm->id, $archivedNorm->id]);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offer->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.active_norms_count', 1);
        $response->assertJsonPath('meta.linked_norms_count', 2);
        $response->assertJsonCount(1, 'norms');
        $response->assertJsonPath('norms.0.code', 'ISO-DRAFT');
        $response->assertJsonPath('norms.0.status', 'draft');
    }

    public function test_access_catalog_hides_common_sub_module_when_its_explicit_norm_is_not_subscribed(): void
    {
        $requiredNorm = Norm::factory()->create(['code' => 'ISO-9001', 'status' => 'published']);
        $subscribedNorm = Norm::factory()->create(['code' => 'ISO-14001', 'status' => 'published']);

        $module = Module::query()->create([
            'name' => 'Module Test Common',
            'code' => 'module-test-common-' . Str::lower(Str::random(8)),
            'description' => 'Module commun de test',
            'icon' => 'layers',
            'iso_point' => 6,
            'order' => 999,
            'is_active' => true,
            'is_common' => true,
        ]);

        $subModule = SubModule::query()->create([
            'module_id' => $module->id,
            'name' => 'Sous-module normé',
            'code' => 'submodule-norme-' . Str::lower(Str::random(8)),
            'description' => 'Sous-module avec norme explicite',
            'icon' => 'file',
            'route' => '/company/test/submodule',
            'order' => 999,
            'is_active' => true,
            'is_common' => true,
        ]);
        $subModule->norms()->sync([$requiredNorm->id]);

        $offer = Offer::factory()->create();
        $offer->norms()->attach($subscribedNorm->id);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offer->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $returnedCodes = collect($response->json('sub_modules'))->pluck('code')->all();
        $this->assertNotContains($subModule->code, $returnedCodes);
    }

    public function test_access_catalog_keeps_common_sub_module_when_its_explicit_norm_is_subscribed(): void
    {
        $requiredNorm = Norm::factory()->create(['code' => 'ISO-9001', 'status' => 'published']);

        $module = Module::query()->create([
            'name' => 'Module Test Common Allowed',
            'code' => 'module-test-allowed-' . Str::lower(Str::random(8)),
            'description' => 'Module commun autorisé',
            'icon' => 'layers',
            'iso_point' => 6,
            'order' => 998,
            'is_active' => true,
            'is_common' => true,
        ]);

        $subModule = SubModule::query()->create([
            'module_id' => $module->id,
            'name' => 'Sous-module autorisé',
            'code' => 'submodule-allowed-' . Str::lower(Str::random(8)),
            'description' => 'Sous-module avec norme explicite autorisée',
            'icon' => 'file',
            'route' => '/company/test/submodule-allowed',
            'order' => 998,
            'is_active' => true,
            'is_common' => true,
        ]);
        $subModule->norms()->sync([$requiredNorm->id]);

        $offer = Offer::factory()->create();
        $offer->norms()->attach($requiredNorm->id);

        EnterpriseSubscription::factory()->create([
            'site_id' => $this->siteA->id,
            'offer_id' => $offer->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/access/catalog');

        $response->assertStatus(200);
        $returnedCodes = collect($response->json('sub_modules'))->pluck('code')->all();
        $this->assertContains($subModule->code, $returnedCodes);
    }
}
