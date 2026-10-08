<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SSO entrant depuis la plateforme Supervision.
 *
 * GET /api/sso/login?sup_token=<token>
 * → valide le token auprès de Supervision
 * → retrouve l'user BESTQHSE/LOGIQUALI par email
 * → retourne un token Sanctum
 */
class SsoLoginController extends Controller
{
    public function login(Request $request)
    {
        $supToken = $request->query('sup_token');

        if (! $supToken || strlen($supToken) < 32) {
            return response()->json(['message' => 'Token SSO manquant ou invalide.'], 400);
        }

        $supervisionUrl = rtrim(config('services.supervision.url', env('SUPERVISION_ENDPOINT', 'https://supervision.bestexperts.bj')), '/');
        $projectCode    = config('services.supervision.project_code', env('SUPERVISION_PROJECT_CODE', 'BESTQHSE'));

        // ── Valider le token auprès de Supervision ──────────────────────────
        try {
            $response = Http::timeout(8)
                ->post("{$supervisionUrl}/api/v1/sso/validate", [
                    'token'        => $supToken,
                    'project_code' => $projectCode,
                ]);
        } catch (\Throwable $e) {
            Log::error('[SSO-LOGIQUALI] Supervision unreachable: ' . $e->getMessage());
            return response()->json(['message' => 'Service de supervision indisponible.'], 503);
        }

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Token SSO invalide ou expiré.',
                'reason'  => $response->json('reason', 'unknown'),
            ], 401);
        }

        $payload = $response->json();

        if (! ($payload['valid'] ?? false)) {
            return response()->json(['message' => 'Token SSO rejeté.'], 401);
        }

        $supUser   = $payload['user'];
        $sessionId = $payload['work_session_id'] ?? null;

        // ── Retrouver l'utilisateur LOGIQUALI par email ──────────────────────
        $user = User::where('email', $supUser['email'])->first();

        if (! $user) {
            return response()->json([
                'message' => 'Aucun compte LOGIQUALI associé à cet email.',
                'email'   => $supUser['email'],
            ], 403);
        }

        // ── Créer le token Sanctum ─────────────────────────────────────────────
        $token = $user->createToken('sso_session', ['*'], now()->addHours(8))->plainTextToken;

        Log::info('[SSO-LOGIQUALI] Connexion SSO', [
            'user_id'         => $user->id,
            'email'           => $user->email,
            'work_session_id' => $sessionId,
        ]);

        // Stocker le session_id dans la session
        session(['sup_work_session_id' => $sessionId]);

        // ── Rediriger vers le frontend avec le token ────────────────────────
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3003')), '/');
        return redirect()->to("{$frontendUrl}/sso-callback?token={$token}&session_id={$sessionId}");
    }
}
