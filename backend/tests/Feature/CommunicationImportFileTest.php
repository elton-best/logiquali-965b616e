<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\EnsureEnterpriseOwnership;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Communication;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CommunicationImportFileTest extends TestCase
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

    public function test_import_file_creates_communications_from_csv(): void
    {
        $csv = implode("\n", [
            'N°,Theme de la communication ou de la sensibilisation,Jan,Fev,Mar,Avr,Mai,Juin,Juil,Aout,Sept,Oct,Nov,Dec,Responsable ou animation,Cibles,Moyen de communication ou de sensibilisation,Date Suivi,,',
            ',,jan,fev,mar,avr,mai,juin,juil,aout,sept,oct,nov,dec,,,,,debut,fin',
            '1,Sensibilisation securite,,,,,,,,,,,,,Alice,Personnel;Direction,Reunion,,2026-04-10,2026-04-12',
        ]);
        $file = UploadedFile::fake()->createWithContent('communications.csv', $csv);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/communications/import-file', [
                'file' => $file,
                'year' => 2026,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'created' => 1,
            'updated' => 0,
        ]);

        $this->assertDatabaseHas('communications', [
            'enterprise_id' => $this->enterprise->id,
            'designation' => 'Sensibilisation securite',
            'type' => 'sensibilisation',
            'responsable' => 'Alice',
            'plan_year' => 2026,
        ]);
    }

    public function test_import_file_updates_existing_communication_when_numero_matches_same_year(): void
    {
        $existing = Communication::create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
            'numero' => 2,
            'type' => 'communication',
            'designation' => 'Ancien theme',
            'cibles' => ['Personnel'],
            'moyens' => ['Email'],
            'chronogramme' => array_fill(0, 12, false),
            'responsable' => 'Old',
            'plan_year' => 2026,
            'status' => 'en_attente',
            'frequency' => 'ponctuelle',
            'created_by' => $this->admin->id,
        ]);

        $csv = implode("\n", [
            'N°,Theme de la communication ou de la sensibilisation,Jan,Fev,Mar,Avr,Mai,Juin,Juil,Aout,Sept,Oct,Nov,Dec,Responsable ou animation,Cibles,Moyen de communication ou de sensibilisation,Date Suivi,,',
            ',,jan,fev,mar,avr,mai,juin,juil,aout,sept,oct,nov,dec,,,,,debut,fin',
            '2,Communication qualite,,,,,,,,,,,,,Bob,Clients;Personnel,Email,,2026-05-01,2026-05-03',
        ]);
        $file = UploadedFile::fake()->createWithContent('communications_update.csv', $csv);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->post('/api/v1/communications/import-file', [
                'file' => $file,
                'year' => 2026,
            ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'created' => 0,
            'updated' => 1,
        ]);

        $this->assertDatabaseHas('communications', [
            'id' => $existing->id,
            'designation' => 'Communication qualite',
            'responsable' => 'Bob',
            'plan_year' => 2026,
            'status' => 'planifiee',
        ]);
    }
}
