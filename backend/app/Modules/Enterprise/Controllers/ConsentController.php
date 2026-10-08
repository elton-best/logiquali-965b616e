<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ConsentController extends Controller
{
    /**
     * Enregistrer un consentement utilisateur (RGPD)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'consent_type' => 'required|string|in:cookies,data_processing,marketing,analytics',
            'consent_given' => 'required|boolean',
            'consent_text' => 'nullable|string',
            'consent_version' => 'nullable|string|max:20',
            'expiry_days' => 'nullable|integer|min:1|max:365',
        ]);

        $consent = ConsentLog::create([
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'consent_type' => $validated['consent_type'],
            'consent_given' => $validated['consent_given'],
            'consent_date' => now(),
            'expiry_date' => isset($validated['expiry_days']) 
                ? now()->addDays($validated['expiry_days']) 
                : null,
            'consent_text' => $validated['consent_text'] ?? null,
            'consent_version' => $validated['consent_version'] ?? '1.0',
            'metadata' => [
                'browser' => $request->header('sec-ch-ua'),
                'platform' => $request->header('sec-ch-ua-platform'),
                'mobile' => $request->header('sec-ch-ua-mobile') === '?1',
            ],
        ]);

        return response()->json([
            'message' => 'Consentement enregistré avec succès',
            'consent' => $consent,
        ], 201);
    }

    /**
     * Récupérer les consentements de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        $consents = ConsentLog::where('user_id', $request->user()->id)
            ->orderBy('consent_date', 'desc')
            ->get()
            ->groupBy('consent_type')
            ->map(function ($group) {
                return $group->first(); // Dernier consentement par type
            });

        return response()->json($consents);
    }

    /**
     * Vérifier si un consentement spécifique est valide
     */
    public function check(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:cookies,data_processing,marketing,analytics',
        ]);

        $consent = ConsentLog::where('user_id', $request->user()?->id)
            ->orWhere('ip_address', $request->ip())
            ->ofType($type)
            ->valid()
            ->latest('consent_date')
            ->first();

        return response()->json([
            'type' => $type,
            'is_valid' => $consent !== null,
            'consent' => $consent,
        ]);
    }

    /**
     * Révoquer tous les consentements de l'utilisateur
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $count = ConsentLog::where('user_id', $request->user()->id)
            ->update(['consent_given' => false]);

        return response()->json([
            'message' => 'Tous les consentements ont été révoqués',
            'count' => $count,
        ]);
    }
}
