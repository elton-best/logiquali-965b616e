<?php

namespace Tests\Feature;

use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QRCodeVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_returns_public_payload_for_valid_signature(): void
    {
        $hash = app(QRCodeService::class)->generate('process', 99, [
            'document_title' => 'Fiche Processus',
            'enterprise_name' => 'Client A',
        ]);

        $response = $this->getJson('/api/verify/' . $hash);

        $response
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'document_id' => 99,
                'document_type' => 'process',
                'document_title' => 'Fiche Processus',
                'enterprise_name' => 'Client A',
            ]);
    }

    public function test_verify_rejects_unknown_hash(): void
    {
        $response = $this->getJson('/api/verify/' . hash('sha256', 'unknown-hash'));

        $response
            ->assertStatus(404)
            ->assertJson([
                'valid' => false,
            ]);
    }
}

