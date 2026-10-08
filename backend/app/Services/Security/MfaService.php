<?php

namespace App\Services\Security;

use App\Models\MfaChallenge;
use App\Models\User;
use App\Notifications\User\MfaOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MfaService
{
    public function issueChallenge(User $user, string $purpose, ?Request $request = null, array $context = []): MfaChallenge
    {
        $ttlMinutes = $this->getTtlMinutes($purpose);
        $code = $this->generateOtp();

        info("Issuing MFA challenge for user {$user->email} with purpose '{$purpose}'", [
            'user_id' => $user->id,
            'purpose' => $purpose,
            'context' => $context,
            'code' => $code, // Log the OTP for debugging (remove in production)
            'otp_length' => (int) config('mfa.otp_length', 6),
        ]);

        MfaChallenge::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->delete();

        $challenge = MfaChallenge::create([
            'user_id' => $user->id,
            'token' => Str::uuid()->toString(),
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'context' => $context ?: null,
            'expires_at' => now()->addMinutes($ttlMinutes),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        $challenge->setAttribute('plain_code', $code);

        $user->notify(new MfaOtpNotification($code, $ttlMinutes));

        return $challenge;
    }

    public function validateChallenge(MfaChallenge $challenge, string $code): bool
    {
        if ($challenge->isConsumed() || $challenge->isExpired()) {
            return false;
        }

        return Hash::check($code, $challenge->code_hash);
    }

    public function consumeChallenge(MfaChallenge $challenge): void
    {
        $challenge->forceFill(['consumed_at' => now()])->save();
    }

    private function generateOtp(): string
    {
        $length = (int) config('mfa.otp_length', 6);
        $min = 10 ** ($length - 1);
        $max = (10 ** $length) - 1;

        return (string) random_int($min, $max);
    }

    private function getTtlMinutes(string $purpose): int
    {
        return match ($purpose) {
            'step_up' => (int) config('mfa.step_up_ttl_minutes', 10),
            default => (int) config('mfa.login_ttl_minutes', 10),
        };
    }
}
