<?php

namespace Tests\Feature;

use App\Models\EvaluationRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EvaluationPublicResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_reset_link_generates_a_new_token(): void
    {
        $site = Site::factory()->create();
        $creator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        $request = EvaluationRequest::create([
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
            'type' => 'satisfaction_client',
            'recipient_email' => 'client@example.com',
            'recipient_name' => 'Client Test',
            'subject' => 'Evaluation client',
            'message' => 'Merci de repondre',
            'created_by' => $creator->id,
            'token' => (string) Str::uuid(),
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson("/api/v1/public/evaluation/{$request->token}/reset-link");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['new_public_url'],
            ]);

        $this->assertDatabaseCount('evaluation_requests', 2);
    }

    public function test_public_reset_link_enforces_cooldown(): void
    {
        $site = Site::factory()->create();
        $creator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        $request = EvaluationRequest::create([
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
            'type' => 'satisfaction_client',
            'recipient_email' => 'client@example.com',
            'subject' => 'Evaluation client',
            'created_by' => $creator->id,
            'token' => (string) Str::uuid(),
            'status' => 'expired',
            'expires_at' => now()->subDay(),
            'metadata' => [
                'last_public_reset_requested_at' => now()->toDateTimeString(),
            ],
        ]);

        $response = $this->postJson("/api/v1/public/evaluation/{$request->token}/reset-link");

        $response->assertStatus(429)
            ->assertJsonPath('success', false);
    }
}
