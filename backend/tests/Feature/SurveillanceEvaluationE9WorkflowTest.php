<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckSubscriptionStatus;
use App\Http\Middleware\ForceCompanySetup;
use App\Models\EvaluationRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveillanceEvaluationE9WorkflowTest extends TestCase
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

    public function test_e9_custom_criteria_public_link_and_submission_statistics(): void
    {
        $site = Site::factory()->create();
        /** @var User $user */
        $user = User::factory()->create([
            'user_type' => 'company',
            'enterprise_id' => $site->enterprise_id,
            'site_id' => $site->id,
        ]);

        $criterionResponse = $this->actingAs($user)
            ->postJson('/api/v1/evaluation-criteria', [
                'name' => 'Qualite de la prestation',
                'code' => 'QUAL_PREST',
                'description' => 'Evaluation qualitative',
                'category' => 'qualite',
                'scale_type' => 'numeric',
                'scale_min' => 1,
                'scale_max' => 5,
                'weight' => 100,
                'is_mandatory' => true,
                'is_active' => true,
                'form_type' => 'satisfaction_client',
                'display_order' => 1,
                'site_id' => $site->id,
            ]);

        $criterionResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Qualite de la prestation');

        $criterionId = (int) $criterionResponse->json('data.id');

        $requestResponse = $this->actingAs($user)
            ->postJson('/api/v1/evaluation-requests', [
                'type' => 'satisfaction_client',
                'subject' => 'Evaluation client E9',
                'message' => 'Merci de partager votre avis.',
                'site_id' => $site->id,
                'criteria_ids' => [$criterionId],
            ]);

        $requestResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'satisfaction_client');

        $requestId = (int) $requestResponse->json('data.id');
        $evaluationRequest = EvaluationRequest::query()->findOrFail($requestId);
        $token = (string) $evaluationRequest->token;

        $publicForm = $this->getJson("/api/v1/public/evaluation/{$token}");
        $publicForm->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.request.subject', 'Evaluation client E9')
            ->assertJsonCount(1, 'data.criteria');

        $submitResponse = $this->postJson("/api/v1/public/evaluation/{$token}", [
            'respondent_name' => 'Client A',
            'respondent_email' => 'client-a@example.com',
            'responses' => [
                [
                    'criterion_id' => $criterionId,
                    'score' => 4,
                    'comment' => 'Tres satisfaisant',
                ],
            ],
            'general_comment' => 'Bonne experience',
        ]);

        $submitResponse->assertOk()
            ->assertJsonPath('success', true);

        $statsResponse = $this->actingAs($user)
            ->getJson('/api/v1/evaluation-requests/statistics?type=satisfaction_client');

        $statsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.completed', 1)
            ->assertJsonPath('data.response_rate', 100);
    }
}
