<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthVerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_unverified_email_returns_action_required_and_sends_verification(): void
    {
        Notification::fake();

        $password = 'Secret123!';
        $user = User::factory()->unverified()->create([
            'user_type' => 'clientb',
            'password' => bcrypt($password),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment([
                'email_verified' => false,
                'action_required' => 'verify_email',
            ]);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_verify_email_redirects_to_success_status(): void
    {
        $user = User::factory()->unverified()->create([
            'user_type' => 'clientb',
            'is_active' => true,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($url);

        $response->assertRedirectContains('/auth/email-verified?status=success');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verify_email_redirects_to_already_verified_status_when_user_is_already_verified(): void
    {
        $user = User::factory()->create([
            'user_type' => 'clientb',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->get($url);

        $response->assertRedirectContains('/auth/email-verified?status=already_verified');
    }

    public function test_public_resend_verification_sends_email_for_unverified_user_without_authentication(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'user_type' => 'clientb',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/resend-verification/public', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'verification_sent' => true,
            ]);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_public_resend_verification_returns_success_for_unknown_email_to_prevent_enumeration(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/resend-verification/public', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'verification_sent' => true,
            ]);

        Notification::assertNothingSent();
    }
}
