<?php

namespace App\Http\Middleware;

use App\Models\SubModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubModuleAccess
{
    public function handle(Request $request, Closure $next, string $subModuleCode, string $action = 'read'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier si le sub-module existe
        $subModule = SubModule::where('code', $subModuleCode)->first();
        if (!$subModule) {
            return response()->json(['error' => 'Sous-module non trouvé'], 404);
        }

        // Admin entreprise: vérifier uniquement la souscription
        if ($user->isEnterpriseAdmin()) {
            $subscriptions = $user->site?->getActiveSubscriptions();
            
            if (!$subscriptions || $subscriptions->isEmpty()) {
                return response()->json(['error' => 'Aucune souscription active'], 403);
            }

            $hasAccess = false;
            foreach ($subscriptions as $subscription) {
                if ($subscription->canAccessSubModule($subModule->id)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Sous-module non disponible dans votre offre'], 403);
            }

            return $next($request);
        }

        // Collaborateur: vérifier souscription ET permission
        if (!$user->canAccessSubModule($subModuleCode, $action)) {
            $subscriptions = $user->site?->getActiveSubscriptions();
            
            if (!$subscriptions || $subscriptions->isEmpty()) {
                return response()->json(['error' => 'Aucune souscription active'], 403);
            }

            $hasAccess = false;
            foreach ($subscriptions as $subscription) {
                if ($subscription->canAccessSubModule($subModule->id)) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                return response()->json(['error' => 'Sous-module non disponible dans votre offre'], 403);
            }

            return response()->json(['error' => 'Permission refusée'], 403);
        }

        return $next($request);
    }
}
