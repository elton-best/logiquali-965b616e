<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\QRCodeService;
use App\Models\QRCodeScan;
use App\Models\QRCodeSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QRCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private QRCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(QRCodeService::class);
    }

    public function test_generates_hash()
    {
        $hash = $this->service->generate('audit_report', 1);

        $this->assertNotEmpty($hash);
        $this->assertEquals(64, strlen($hash));
        $this->assertDatabaseHas('qr_code_signatures', [
            'hash' => $hash,
            'document_type' => 'audit_report',
            'document_id' => 1,
        ]);
    }

    public function test_gets_verification_url()
    {
        $hash = 'test_hash_123';
        $url = $this->service->getVerificationUrl($hash);

        $this->assertStringContainsString('/verify/test_hash_123', $url);
    }

    public function test_gets_stats()
    {
        $stats = $this->service->getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_scans', $stats);
        $this->assertArrayHasKey('unique_ips', $stats);
        $this->assertArrayHasKey('countries', $stats);
        $this->assertArrayHasKey('recent_scans', $stats);
        $this->assertArrayHasKey('scans_today', $stats);
    }

    public function test_detects_abuse()
    {
        $result = $this->service->detectAbuse('audit_report', 1);

        $this->assertIsBool($result);
    }

    public function test_verifies_hash_from_persisted_signature()
    {
        $hash = $this->service->generate('audit_report', 123, [
            'document_title' => 'Rapport audit',
            'enterprise_name' => 'BestQHSE',
        ]);

        $result = $this->service->verify($hash);

        $this->assertIsArray($result);
        $this->assertSame('audit_report', $result['document_type']);
        $this->assertSame(123, $result['document_id']);
        $this->assertSame('Rapport audit', $result['document_title']);
        $this->assertSame('BestQHSE', $result['enterprise_name']);
    }

    public function test_rejects_expired_signature()
    {
        QRCodeSignature::query()->create([
            'document_type' => 'audit_report',
            'document_id' => 22,
            'hash' => hash('sha256', 'expired-signature'),
            'expires_at' => now()->subMinute(),
        ]);

        $result = $this->service->verify(hash('sha256', 'expired-signature'));

        $this->assertNull($result);
    }
}
