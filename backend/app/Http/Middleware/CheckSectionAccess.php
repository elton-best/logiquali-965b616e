<?php

namespace App\Http\Middleware;

use App\Models\SubModuleSection;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSectionAccess
{
    public function handle(Request $request, Closure $next, string $sectionCode, string $action = 'read'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        // Vérifier si la section existe
        $section = SubModuleSection::where('code', $sectionCode)->with('subModule')->first();
        if (!$section) {
            return response()->json(['error' => 'Section non trouvée'], 404);
        }

        // Admin entreprise: vérifier uniquement la souscription
        if ($user->isEnterpriseAdmin()) {
            $subscriptions = $user->site?->getActiveSubscriptions();
            
            if (!$subscriptions || $subscriptions->isEmpty()) {
                return response()->json(['error' => 'Aucune souscription active'], 403);
            }

            $hasAccess = false;
            foreach ($subscriptions as $subscription) {
                if ($subscription->canAccessSection($section->id)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Section non disponible dans votre offre'], 403);
            }

            return $next($request);
        }

        // Collaborateur: vérifier souscription + permission sous-module + permission section
        if (!$user->canAccessSection($sectionCode, $action)) {
            $subscriptions = $user->site?->getActiveSubscriptions();
            
            if (!$subscriptions || $subscriptions->isEmpty()) {
                return response()->json(['error' => 'Aucune souscription active'], 403);
            }

            $hasAccess = false;
            foreach ($subscriptions as $subscription) {
                if ($subscription->canAccessSection($section->id)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Section non disponible dans votre offre'], 403);
            }

            return response()->json(['error' => 'Permission refusée'], 403);
        }

        return $next($request);
    }
}
