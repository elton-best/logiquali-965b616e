<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthMfaExposeOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_exposes_mfa_code_when_feature_flag_is_enabled(): void
    {
        Notification::fake();
        config(['mfa.expose_otp_for_e2e' => true]);

        $password = 'Secret123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
            'user_type' => User::TYPE_CLIENT_B,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJson([
                'mfa_required' => true,
            ])
            ->assertJsonStructure([
                'mfa_token',
                'mfa_expires_at',
                'mfa_code',
            ]);

        $this->assertSame(6, strlen((string) $response->json('mfa_code')));
    }

    public function test_login_does_not_expose_mfa_code_when_feature_flag_is_disabled(): void
    {
        Notification::fake();
        config(['mfa.expose_otp_for_e2e' => false]);

        $password = 'Secret123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
            'user_type' => User::TYPE_CLIENT_B,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJson([
                'mfa_required' => true,
            ])
            ->assertJsonMissingPath('mfa_code');
    }
}
