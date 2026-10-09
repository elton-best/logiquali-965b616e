<?php

namespace App\Modules\Planning\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Planning\Services\MethodologyGuideService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MethodologyGuideController extends Controller
{
    public function __construct(
        private readonly MethodologyGuideService $guideService
    ) {}

    /**
     * GET /api/v1/methodology-guides
     * Retourne l'ensemble des guides d'utilisation et matrices de cotation méthodologiques.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->guideService->getAllGuides(),
        ]);
    }

    /**
     * GET /api/v1/methodology-guides/{section}
     * Retourne le guide spécifique à une section : risks, duerp, aes, process_reviews.
     */
    public function show(string $section): JsonResponse
    {
        $normalized = strtolower(str_replace('-', '_', $section));

        $guide = match ($normalized) {
            'risks', 'risques', 'risks_opportunities', 'risques_opportunites' => $this->guideService->getRisksOpportunitiesGuide(),
            'duerp', 'sst', 'sante_securite' => $this->guideService->getDuerpGuide(),
            'aes', 'environnement', 'aspects_environnementaux' => $this->guideService->getAesGuide(),
            'reviews', 'process_reviews', 'revue_processus' => $this->guideService->getProcessReviewGuide(),
            default => null,
        };

        if ($guide === null) {
            return response()->json([
                'success' => false,
                'message' => "Guide méthodologique non trouvé pour la section : {$section}. Sections disponibles : risks, duerp, aes, process_reviews.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $guide,
        ]);
    }
}
