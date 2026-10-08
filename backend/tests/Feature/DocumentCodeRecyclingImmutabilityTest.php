<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\ForceSignatureUpload;
use App\Models\Document;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCodeRecyclingImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
            ForcePasswordChange::class,
            ForceSignatureUpload::class,
            \App\Http\Middleware\EnsureMfaStepUp::class,
            'check.subscription',
            'mfa.stepup',
        ]);
    }

    public function test_release_code_is_forbidden_for_approved_document(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'user_type' => 'company',
        ]);

        $document = Document::factory()->create([
            'site_id' => $site->id,
            'enterprise_id' => $site->enterprise_id,
            'author_id' => $user->id,
            'code' => 'IMM-API-001',
            'workflow_status' => 'approved',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/document-codes/release', [
                'code' => 'IMM-API-001',
                'original_document_id' => $document->id,
                'reason' => 'manual',
            ]);

        $response->assertStatus(422);
    }
}

