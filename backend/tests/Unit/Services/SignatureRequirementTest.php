<?php

namespace Tests\Unit\Services;

use App\Models\DocumentSignature;
use App\Models\DocumentSignatureWorkflow;
use App\Models\User;
use App\Services\DocumentSignatureService;
use App\Services\SignatureWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignatureRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_signature_service_requires_uploaded_signature(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
            'signature_path' => null,
        ]);

        $service = app(DocumentSignatureService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Vous devez importer votre signature avant de signer un document.');

        $service->signDocument('job_description', 1, $user, 'Password@123', null);
    }

    public function test_signature_workflow_service_requires_uploaded_signature(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
            'signature_path' => null,
        ]);

        $workflow = DocumentSignatureWorkflow::create([
            'document_type' => 'job_description',
            'document_id' => 10,
            'workflow_name' => 'Validation JD',
            'total_steps' => 1,
            'current_step' => 1,
            'status' => 'in_progress',
            'initiated_by' => $user->id,
            'initiated_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        DocumentSignature::create([
            'workflow_id' => $workflow->id,
            'document_type' => 'job_description',
            'document_id' => 10,
            'user_id' => $user->id,
            'signature_order' => 1,
            'signed_at' => now(),
            'status' => 'pending',
            'signature_text' => 'Signature requise',
        ]);

        $service = app(SignatureWorkflowService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Vous devez importer votre signature avant de signer un document.');

        $service->sign($workflow->fresh(), $user, 'Password@123');
    }
}
