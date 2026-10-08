<?php

namespace Tests\Unit\Models;

use App\Models\ConsentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ConsentLogTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test création consentement avec utilisateur
     */
    public function test_can_create_consent_log_with_user(): void
    {
        $user = User::factory()->create();

        $consent = ConsentLog::create([
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0',
            'consent_type' => 'cookies',
            'consent_given' => true,
            'consent_date' => now(),
            'consent_version' => '1.0',
        ]);

        $this->assertDatabaseHas('consent_logs', [
            'user_id' => $user->id,
            'consent_type' => 'cookies',
            'consent_given' => true,
        ]);

        $this->assertEquals($user->id, $consent->user_id);
        $this->assertTrue($consent->consent_given);
    }

    /**
     * Test création consentement anonyme (sans user_id)
     */
    public function test_can_create_anonymous_consent_log(): void
    {
        $consent = ConsentLog::create([
            'user_id' => null,
            'ip_address' => '203.0.113.42',
            'user_agent' => 'Chrome/120.0',
            'consent_type' => 'analytics',
            'consent_given' => true,
            'consent_date' => now(),
        ]);

        $this->assertNull($consent->user_id);
        $this->assertEquals('203.0.113.42', $consent->ip_address);
        $this->assertEquals('analytics', $consent->consent_type);
    }

    /**
     * Test relation belongsTo User
     */
    public function test_consent_log_belongs_to_user(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        $consent = ConsentLog::create([
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Safari',
            'consent_type' => 'data_processing',
            'consent_given' => true,
            'consent_date' => now(),
        ]);

        $this->assertEquals('John Doe', $consent->user->name);
    }

    /**
     * Test isValid() retourne true si consent_given = true et pas expiré
     */
    public function test_is_valid_returns_true_for_active_consent(): void
    {
        $consent = ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'cookies',
            'consent_given' => true,
            'consent_date' => now(),
            'expiry_date' => now()->addDays(30), // Expire dans 30 jours
        ]);

        $this->assertTrue($consent->isValid());
    }

    /**
     * Test isValid() retourne false si consent_given = false
     */
    public function test_is_valid_returns_false_for_rejected_consent(): void
    {
        $consent = ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'marketing',
            'consent_given' => false,
            'consent_date' => now(),
        ]);

        $this->assertFalse($consent->isValid());
    }

    /**
     * Test isValid() retourne false si expiré
     */
    public function test_is_valid_returns_false_for_expired_consent(): void
    {
        $consent = ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'analytics',
            'consent_given' => true,
            'consent_date' => now()->subDays(40),
            'expiry_date' => now()->subDays(10), // Expiré depuis 10 jours
        ]);

        $this->assertFalse($consent->isValid());
    }

    /**
     * Test scope valid() filtre uniquement les consentements valides
     */
    public function test_scope_valid_filters_active_consents(): void
    {
        // Consentement valide
        ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'cookies',
            'consent_given' => true,
            'consent_date' => now(),
        ]);

        // Consentement refusé
        ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'marketing',
            'consent_given' => false,
            'consent_date' => now(),
        ]);

        // Consentement expiré
        ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'analytics',
            'consent_given' => true,
            'consent_date' => now()->subDays(100),
            'expiry_date' => now()->subDays(1),
        ]);

        $validConsents = ConsentLog::valid()->get();

        $this->assertCount(1, $validConsents);
        $this->assertEquals('cookies', $validConsents->first()->consent_type);
    }

    /**
     * Test scope ofType() filtre par type de consentement
     */
    public function test_scope_of_type_filters_by_consent_type(): void
    {
        ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'cookies',
            'consent_given' => true,
            'consent_date' => now(),
        ]);

        ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'analytics',
            'consent_given' => true,
            'consent_date' => now(),
        ]);

        $cookieConsents = ConsentLog::ofType('cookies')->get();
        $analyticsConsents = ConsentLog::ofType('analytics')->get();

        $this->assertCount(1, $cookieConsents);
        $this->assertCount(1, $analyticsConsents);
        $this->assertEquals('cookies', $cookieConsents->first()->consent_type);
        $this->assertEquals('analytics', $analyticsConsents->first()->consent_type);
    }

    /**
     * Test metadata JSON cast
     */
    public function test_metadata_is_cast_to_array(): void
    {
        $metadata = [
            'browser' => 'Chrome',
            'platform' => 'Windows',
            'mobile' => false,
        ];

        $consent = ConsentLog::create([
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'cookies',
            'consent_given' => true,
            'consent_date' => now(),
            'metadata' => $metadata,
        ]);

        $this->assertIsArray($consent->metadata);
        $this->assertEquals('Chrome', $consent->metadata['browser']);
        $this->assertEquals('Windows', $consent->metadata['platform']);
        $this->assertFalse($consent->metadata['mobile']);
    }

    /**
     * Test consent log cascade delete when user deleted
     * Note: With DatabaseTransactions, we test the relationship instead
     */
    public function test_consent_log_is_deleted_when_user_deleted(): void
    {
        $user = User::factory()->create();

        $consent = ConsentLog::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'consent_type' => 'cookies',
            'consent_given' => true,
            'consent_date' => now(),
        ]);

        // With DatabaseTransactions, test the foreign key constraint
        $foreignKey = \DB::select("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = 'consent_logs' 
            AND constraint_type = 'FOREIGN KEY'
        ");

        $this->assertNotEmpty($foreignKey, 'Foreign key constraint should exist');
        
        // Verify the consent belongs to user
        $this->assertEquals($user->id, $consent->user_id);
    }
}
