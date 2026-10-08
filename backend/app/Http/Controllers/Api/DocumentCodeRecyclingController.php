<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityAuditLog;
use App\Services\DocumentCodeRecyclingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DocumentCodeRecyclingController extends Controller
{
    public function __construct(
        private DocumentCodeRecyclingService $recyclingService
    ) {}

    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
        ]);

        $entrepriseId = $request->user()->enterprise_id;

        try {
            $isAvailable = $this->recyclingService->isCodeAvailable(
                $validated['code'],
                $entrepriseId
            );

            return response()->json([
                'available' => $isAvailable,
                'code' => $validated['code'],
            ]);
        } catch (\Exception $e) {
            Log::error('Code availability check failed', [
                'code' => $validated['code'],
                'entreprise_id' => $entrepriseId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la vérification',
            ], 500);
        }
    }

    public function releaseCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255',
            'original_document_id' => 'nullable|exists:documents,id',
            'reason' => 'required|in:rejected,deleted,manual',
        ]);

        $entrepriseId = $request->user()->enterprise_id;
        $userId = $request->user()->id;

        // SECURITY: Vérification des permissions granulaires
        $canReleaseOwn = $request->user()->can('release_own_codes');
        $canReleaseAll = $request->user()->can('release_all_codes');
        $canForceRelease = $request->user()->can('force_release_codes');

        $isLegacyCompanyUser = $this->isEnterpriseScopedUser($request);
        if (
            !$isLegacyCompanyUser
            && !$canReleaseOwn
            && !$canReleaseAll
            && !$canForceRelease
            && !$request->user()->can('documents.update')
            && !$request->user()->can('documents.create')
            && !$request->user()->can('documents.read')
        ) {
            return response()->json(['message' => 'Permission refusée : vous n\'avez pas le droit de libérer des codes'], 403);
        }

        if (isset($validated['original_document_id'])) {
            $document = \App\Models\Document::find($validated['original_document_id']);
            $documentEnterpriseId = (int) ($document->enterprise_id ?? 0);
            if ($documentEnterpriseId <= 0 && $document?->site) {
                $documentEnterpriseId = (int) ($document->site->enterprise_id ?? 0);
            }
            if ($documentEnterpriseId !== (int) $entrepriseId) {
                return response()->json(['message' => 'Accès refusé'], 403);
            }

            $isApproved = ((string) ($document->workflow_status ?? '') === 'approved')
                || ((string) ($document->status ?? '') === 'approved');
            if ($isApproved) {
                return response()->json([
                    'message' => 'Le code d’un document validé est définitif et ne peut pas être libéré.',
                ], 422);
            }

            // Vérifier si l'utilisateur peut libérer ce code spécifique
            if (!$canReleaseAll && !$canForceRelease) {
                // Seul l'auteur peut libérer son propre code
                if ($document->author_id !== $userId) {
                    return response()->json(['message' => 'Permission refusée : vous ne pouvez libérer que vos propres codes'], 403);
                }
            }
        }

        try {
            $release = $this->recyclingService->releaseCode(
                $validated['code'],
                $entrepriseId,
                $validated['original_document_id'] ?? null,
                $validated['reason'],
                $userId
            );

            // SECURITY: Audit trail pour le recyclage de codes
            SecurityAuditLog::logEvent(
                eventType: 'document_code_recycling',
                action: 'release_code',
                resourceType: 'document_code',
                resourceId: null,
                metadata: [
                    'code' => $validated['code'],
                    'reason' => $validated['reason'],
                    'entreprise_id' => $entrepriseId,
                    'user_id' => $userId,
                    'release_id' => $release->id ?? null,
                    'permission_used' => $canForceRelease ? 'force_release_codes' : ($canReleaseAll ? 'release_all_codes' : 'release_own_codes'),
                ],
                riskLevel: 'medium',
            );

            return response()->json([
                'message' => 'Code libéré avec succès',
                'release' => $release,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Code release failed', [
                'code' => $validated['code'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la libération du code',
            ], 500);
        }
    }

    public function availableCodes(Request $request): JsonResponse
    {
        $entrepriseId = $request->user()->enterprise_id;

        try {
            $codes = $this->recyclingService->getAvailableCodes($entrepriseId);

            return response()->json([
                'codes' => $codes,
                'count' => count($codes),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch available codes', [
                'entreprise_id' => $entrepriseId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des codes',
            ], 500);
        }
    }

    public function codeHistory(Request $request, string $code): JsonResponse
    {
        // SECURITY: Vérification permission view_code_history
        if (
            !$this->isEnterpriseScopedUser($request)
            && !$request->user()->can('view_code_history')
            && !$request->user()->can('documents.read')
            && !$request->user()->can('documents.update')
        ) {
            return response()->json(['message' => 'Permission refusée : vous n\'avez pas le droit de consulter l\'historique des codes'], 403);
        }

        $entrepriseId = $request->user()->enterprise_id;

        try {
            $history = $this->recyclingService->getCodeHistory($code, $entrepriseId);

            return response()->json([
                'code' => $code,
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch code history', [
                'code' => $code,
                'entreprise_id' => $entrepriseId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'historique',
            ], 500);
        }
    }

    private function isEnterpriseScopedUser(Request $request): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }

        return ((int) ($user->enterprise_id ?? 0) > 0) || (method_exists($user, 'isCompanyUser') && $user->isCompanyUser());
    }
}
