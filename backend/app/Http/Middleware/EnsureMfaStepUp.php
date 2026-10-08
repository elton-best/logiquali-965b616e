<?php

namespace App\Http\Middleware;

use App\Services\Security\MfaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaStepUp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('mfa.step_up_enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Non authentifie'], 401);
        }

        $token = $user->currentAccessToken();
        $verifiedAt = $token?->mfa_verified_at
            ? \Illuminate\Support\Carbon::parse($token->mfa_verified_at)
            : null;
        $ttlMinutes = (int) config('mfa.step_up_ttl_minutes', 10);

        if (!$verifiedAt || $verifiedAt->lt(now()->subMinutes($ttlMinutes))) {
            $challenge = app(MfaService::class)->issueChallenge($user, 'step_up', $request);

            return response()->json([
                'success' => false,
                'code' => 'MFA_REQUIRED',
                'message' => 'Pour proteger votre compte, veuillez confirmer cette action avec un code envoye par email. Cela ne prendra que quelques secondes.',
                'mfa_token' => $challenge->token,
                'mfa_expires_at' => $challenge->expires_at?->toISOString(),
            ], 423);
        }

        return $next($request);
    }
}
