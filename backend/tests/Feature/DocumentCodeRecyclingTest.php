<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentCodeRelease;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\EnterpriseSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCodeRecyclingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Enterprise $enterprise;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->enterprise = Enterprise::factory()->create([
            'status' => 'active',
            'approval_status' => 'approved',
        ]);
        $this->site = Site::factory()->create(['enterprise_id' => $this->enterprise->id]);
        EnterpriseSubscription::factory()->create([
            'site_id' => $this->site->id,
            'is_active' => true,
            'expiration_date' => now()->addYear(),
        ]);
        $this->user = User::factory()->create([
            'enterprise_id' => $this->enterprise->id,
            'site_id' => $this->site->id,
        ]);
    }

    public function test_check_availability_returns_true_for_unused_code(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware()
            ->getJson('/api/v1/document-codes/check-availability?code=DOC-001');

        $response->assertOk()
            ->assertJson(['available' => true, 'code' => 'DOC-001']);
    }

    public function test_check_availability_returns_false_for_used_code(): void
    {
        Document::factory()->create([
            'site_id' => $this->site->id,
            'code' => 'DOC-001',
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware()
            ->getJson('/api/v1/document-codes/check-availability?code=DOC-001');

        $response->assertOk()
            ->assertJson(['available' => false]);
    }

    public function test_release_code_creates_release_record(): void
    {
        $response = $this->actingAs($this->user)
            ->withoutMiddleware()
            ->postJson('/api/v1/document-codes/release', [
                'code' => 'DOC-001',
                'reason' => 'rejected',
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'release']);

        $this->assertDatabaseHas('document_code_releases', [
            'code' => 'DOC-001',
            'entreprise_id' => $this->enterprise->id,
            'release_reason' => 'rejected',
            'released_by' => $this->user->id,
        ]);
    }

    public function test_available_codes_returns_released_codes(): void
    {
        DocumentCodeRelease::create([
            'entreprise_id' => $this->enterprise->id,
            'code' => 'DOC-001',
            'release_reason' => 'rejected',
            'released_by' => $this->user->id,
            'released_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware()
            ->getJson('/api/v1/document-codes/available');

        $response->assertOk()
            ->assertJsonStructure(['codes', 'count'])
            ->assertJsonFragment(['code' => 'DOC-001']);
    }

    public function test_code_history_returns_release_history(): void
    {
        DocumentCodeRelease::create([
            'entreprise_id' => $this->enterprise->id,
            'code' => 'DOC-001',
            'release_reason' => 'rejected',
            'released_by' => $this->user->id,
            'released_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware()
            ->getJson('/api/v1/document-codes/DOC-001/history');

        $response->assertOk()
            ->assertJsonStructure(['code', 'history']);
    }

    public function test_released_code_becomes_unavailable_when_reused(): void
    {
        $document = Document::factory()->create([
            'site_id' => $this->site->id,
            'code' => 'DOC-002',
        ]);

        $release = DocumentCodeRelease::create([
            'entreprise_id' => $this->enterprise->id,
            'code' => 'DOC-001',
            'release_reason' => 'rejected',
            'released_by' => $this->user->id,
            'released_at' => now(),
        ]);

        $release->update([
            'reused_at' => now(),
            'reused_by_document_id' => $document->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withoutMiddleware()
            ->getJson('/api/v1/document-codes/check-availability?code=DOC-001');

        $response->assertOk()
            ->assertJson(['available' => false]);
    }
}
