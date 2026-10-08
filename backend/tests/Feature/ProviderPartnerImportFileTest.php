<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Enterprise;
use App\Models\ProviderPartner;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProviderPartnerImportFileTest extends TestCase
{
    use RefreshDatabase;

    private Enterprise $enterprise;
    private Site $site;
    private User $admin;

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

        $this->admin = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'user_type' => 'company',
            'is_active' => true,
        ]);
    }

    public function test_import_file_creates_provider_partners_from_csv(): void
    {
        $content = implode("\n", [
            'designation,type prestataire,offre,telephone1,telephone2,email,ifu,annee experience,observation',
            'Alpha Services,personne morale,Audit interne,97000000,,alpha@example.com,IFU-ALPHA,5,Conforme',
            'Beta Conseil,personne physique,Formation,97000001,,beta@example.com,IFU-BETA,3,Suivi trimestriel',
        ]);

        $file = UploadedFile::fake()->createWithContent('providers.csv', $content);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/provider-partners/import-file', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'created' => 2,
            'updated' => 0,
        ]);

        $this->assertDatabaseHas('provider_partners', [
            'enterprise_id' => $this->enterprise->id,
            'designation' => 'Alpha Services',
            'ifu' => 'IFU-ALPHA',
            'provider_type' => 'personne_morale',
            'service_offers' => 'Audit interne',
        ]);
    }

    public function test_import_file_updates_existing_provider_when_designation_and_ifu_match(): void
    {
        ProviderPartner::create([
            'enterprise_id' => $this->enterprise->id,
            'reference' => 'PREST-2026-0001',
            'designation' => 'Alpha Services',
            'provider_type' => 'personne_morale',
            'service_offers' => 'Ancienne offre',
            'ifu' => 'IFU-ALPHA',
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $content = implode("\n", [
            'designation,type prestataire,offre,telephone1,telephone2,email,ifu,annee experience,observation',
            'Alpha Services,personne morale,Nouvelle offre importee,97000009,,alpha@example.com,IFU-ALPHA,8,Mise a jour',
        ]);
        $file = UploadedFile::fake()->createWithContent('providers-update.csv', $content);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/provider-partners/import-file', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'created' => 0,
            'updated' => 1,
        ]);

        $this->assertDatabaseHas('provider_partners', [
            'enterprise_id' => $this->enterprise->id,
            'designation' => 'Alpha Services',
            'ifu' => 'IFU-ALPHA',
            'service_offers' => 'Nouvelle offre importee',
            'experience_years' => 8,
            'phone_primary' => '97000009',
        ]);

        $this->assertSame(
            1,
            ProviderPartner::query()
                ->where('enterprise_id', $this->enterprise->id)
                ->where('designation', 'Alpha Services')
                ->where('ifu', 'IFU-ALPHA')
                ->count()
        );
    }
}

