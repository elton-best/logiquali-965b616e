<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SuperAdminSessionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Acces non autorise'], 403);
        }

        $tokens = $user->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($token) use ($request) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at?->toISOString(),
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'mfa_verified_at' => $token->mfa_verified_at ? \Illuminate\Support\Carbon::parse($token->mfa_verified_at)->toISOString() : null,
                    'is_current' => (int) $token->id === (int) $request->user()?->currentAccessToken()?->id,
                ];
            });

        return response()->json(['success' => true, 'data' => $tokens]);
    }

    public function destroy(Request $request, int $tokenId)
    {
        $user = $request->user();
        if (!$user || !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Acces non autorise'], 403);
        }

        $token = $user->tokens()->where('id', $tokenId)->first();
        if (!$token) {
            return response()->json(['message' => 'Session introuvable'], 404);
        }

        $token->delete();

        return response()->json(['success' => true]);
    }
}
