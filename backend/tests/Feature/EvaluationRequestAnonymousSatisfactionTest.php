<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Models\EvaluationRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationRequestAnonymousSatisfactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckSubscriptionStatus::class,
            ForceCompanySetup::class,
        ]);
    }

    public function test_satisfaction_client_request_can_be_created_without_recipient_identity(): void
    {
        $site = Site::factory()->create();
        /** @var User $creator */
        $creator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        $response = $this->actingAs($creator)
            ->postJson('/api/v1/evaluation-requests', [
                'type' => 'satisfaction_client',
                'subject' => 'Evaluation satisfaction client anonyme',
                'message' => 'Merci de partager votre retour.',
                'site_id' => $site->id,
                'send_immediately' => true,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'satisfaction_client')
            ->assertJsonPath('data.recipient_email', null)
            ->assertJsonPath('data.recipient_name', null)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.sent_at', null);
    }

    public function test_send_fails_when_recipient_email_is_missing(): void
    {
        $site = Site::factory()->create();
        /** @var User $creator */
        $creator = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        /** @var EvaluationRequest $evaluationRequest */
        $evaluationRequest = EvaluationRequest::create([
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
            'type' => 'satisfaction_client',
            'recipient_email' => null,
            'recipient_name' => null,
            'subject' => 'Demande sans destinataire',
            'status' => 'draft',
            'created_by' => $creator->id,
        ]);

        $response = $this->actingAs($creator)
            ->postJson("/api/v1/evaluation-requests/{$evaluationRequest->id}/send");

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Erreur de validation')
            ->assertJsonValidationErrors(['recipient_email']);
    }
}
