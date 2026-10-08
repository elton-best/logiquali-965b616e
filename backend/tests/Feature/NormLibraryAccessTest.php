<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\EnterpriseSubscription;
use App\Models\Norm;
use App\Models\NormSection;
use App\Models\NormVersion;
use App\Models\Offer;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tests\TestCase;

class NormLibraryAccessTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $siteA;
    private Site $siteB;
    private User $user;
    private Norm $normA;
    private Norm $normB;

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
        $this->siteA = Site::factory()->create(['enterprise_id' => $this->enterprise->id, 'is_headquarter' => true]);
        $this->siteB = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);

        $role = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'dashboard.read', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'norm_library.read', 'guard_name' => 'web']);
        $role->syncPermissions(['dashboard.read', 'norm_library.read']);

        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->siteA->id,
            'user_type' => 'company',
            'role' => 'operator',
            'is_active' => true,
        ]);
        $this->user->assignRole($role);

        $this->normA = Norm::factory()->create([
            'code' => 'ISO-9001',
            'name' => 'ISO 9001',
            'status' => 'published',
        ]);
        $this->normB = Norm::factory()->create([
            'code' => 'ISO-14001',
            'name' => 'ISO 14001',
            'status' => 'published',
        ]);

        $offerA = Offer::factory()->create();
        $offerA->norms()->attach($this->normA->id);
        $offerB = Offer::factory()->create();
        $offerB->norms()->attach($this->normB->id);

        EnterpriseSubscription::factory()->create([
            'offer_id' => $offerA->id,
            'site_id' => $this->siteA->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
            'status' => 'active',
        ]);
        EnterpriseSubscription::factory()->create([
            'offer_id' => $offerB->id,
            'site_id' => $this->siteB->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $versionA = NormVersion::query()->create([
            'norm_id' => $this->normA->id,
            'version_code' => '2015',
            'full_code' => 'ISO 9001:2015',
            'published_at' => now()->subYear(),
            'is_current' => true,
        ]);
        $this->normA->update(['current_version_id' => $versionA->id]);

        $chapter = NormSection::query()->create([
            'norm_version_id' => $versionA->id,
            'parent_id' => null,
            'path' => '4',
            'level' => 1,
            'type' => 'chapter',
            'number' => '4',
            'title' => 'Contexte de l organisme',
            'content' => null,
            'order_index' => 1,
        ]);
        NormSection::query()->create([
            'norm_version_id' => $versionA->id,
            'parent_id' => $chapter->id,
            'path' => '4.1',
            'level' => 2,
            'type' => 'subchapter',
            'number' => '4.1',
            'title' => 'Comprendre l organisme et son contexte',
            'content' => null,
            'order_index' => 2,
        ]);
    }

    public function test_index_returns_norms_for_current_site_context_only(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'ISO-9001');
    }

    public function test_index_switches_norms_when_site_header_changes(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeaders(['X-Site-ID' => (string) $this->siteB->id])
            ->getJson('/api/v1/norms');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.code', 'ISO-14001');
    }

    public function test_show_returns_404_for_norm_not_covered_by_site_subscription(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms/' . $this->normB->id);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Norme non accessible');
        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_chapters_returns_404_for_norm_not_covered_by_site_subscription(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms/' . $this->normB->id . '/chapters');

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Norme non accessible');
        $response->assertJsonStructure(['correlation_id']);
    }

    public function test_chapters_returns_norm_structure_for_accessible_norm(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms/' . $this->normA->id . '/chapters');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.number', '4');
        $response->assertJsonPath('data.0.subchapters.0.number', '4.1');
    }

    public function test_show_exposes_uploaded_pdf_when_norm_has_one(): void
    {
        Storage::fake('public');
        $pdf = UploadedFile::fake()->create('iso-9001.pdf', 120, 'application/pdf');
        $path = $pdf->store('norms/pdfs', 'public');

        $this->normA->update([
            'pdf_file_path' => $path,
            'pdf_original_name' => 'iso-9001.pdf',
            'pdf_uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms/' . $this->normA->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.has_pdf_document', true);
        $response->assertJsonPath('data.pdf_original_name', 'iso-9001.pdf');
        $response->assertJsonPath('data.pdf_file_url', Storage::disk('public')->url($path));
    }

    public function test_norm_library_read_permission_allows_access_without_dashboard_read(): void
    {
        $role = Role::firstOrCreate(['name' => 'norm_library_reader', 'guard_name' => 'web']);
        $role->syncPermissions(['norm_library.read']);

        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->siteA->id,
            'user_type' => 'company',
            'role' => 'operator',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/norms');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_access_is_denied_when_user_has_neither_norm_library_nor_dashboard_permission(): void
    {
        $role = Role::firstOrCreate(['name' => 'limited_user', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sites.read', 'guard_name' => 'web']);
        $role->syncPermissions(['sites.read']);

        $user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->siteA->id,
            'user_type' => 'company',
            'role' => 'operator',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/norms')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/norms/' . $this->normA->id)
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/norms/' . $this->normA->id . '/chapters')
            ->assertStatus(403);
    }

    public function test_company_norm_routes_are_read_only(): void
    {
        $postResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/norms', [
                'code' => 'ISO-TEST',
                'name' => 'Norm test',
            ]);
        $this->assertReadOnlyRouteResponse($postResponse);

        $putResponse = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/norms/' . $this->normA->id, [
                'name' => 'Renommee',
            ]);
        $this->assertReadOnlyRouteResponse($putResponse);

        $deleteResponse = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/norms/' . $this->normA->id);
        $this->assertReadOnlyRouteResponse($deleteResponse);
    }

    public function test_show_and_chapters_return_normalized_404_when_norm_does_not_exist(): void
    {
        $unknownNormId = 999999;

        $show = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms/' . $unknownNormId);

        $show->assertStatus(404);
        $show->assertJsonPath('success', false);
        $show->assertJsonPath('message', 'Norme non accessible');
        $show->assertJsonStructure(['correlation_id']);

        $chapters = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms/' . $unknownNormId . '/chapters');

        $chapters->assertStatus(404);
        $chapters->assertJsonPath('success', false);
        $chapters->assertJsonPath('message', 'Norme non accessible');
        $chapters->assertJsonStructure(['correlation_id']);
    }

    public function test_index_includes_draft_norms_but_excludes_archived_norms_for_current_site(): void
    {
        $draftNorm = Norm::factory()->create([
            'code' => 'ISO-DRAFT-ACCESS',
            'name' => 'ISO Draft Access',
            'status' => 'draft',
        ]);
        $archivedNorm = Norm::factory()->create([
            'code' => 'ISO-ARCHIVED-ACCESS',
            'name' => 'ISO Archived Access',
            'status' => 'archived',
        ]);

        $offer = Offer::factory()->create();
        $offer->norms()->attach([$draftNorm->id, $archivedNorm->id]);

        EnterpriseSubscription::factory()->create([
            'offer_id' => $offer->id,
            'site_id' => $this->siteA->id,
            'is_active' => true,
            'is_trial' => false,
            'start_date' => now()->subDay(),
            'expiration_date' => now()->addMonth(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/norms');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertTrue(
            collect($response->json('data'))->contains(fn ($norm) => ($norm['code'] ?? null) === 'ISO-DRAFT-ACCESS')
        );
        $this->assertFalse(
            collect($response->json('data'))->contains(fn ($norm) => ($norm['code'] ?? null) === 'ISO-ARCHIVED-ACCESS')
        );
    }

    private function assertReadOnlyRouteResponse($response): void
    {
        $status = $response->getStatusCode();
        $this->assertContains($status, [405, 500]);

        if ($status === 500) {
            $this->assertInstanceOf(MethodNotAllowedHttpException::class, $response->exception);
        }
    }
}
