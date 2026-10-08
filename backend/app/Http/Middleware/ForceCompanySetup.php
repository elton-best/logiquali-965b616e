<?php

namespace App\Http\Middleware;

use App\Support\BlockingAccessResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCompanySetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || !$user->enterprise) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $path = $request->path();
        $allow = [
            'api/v1/auth/me',
            'api/v1/auth/logout',
            'api/v1/subscription/status',
            'api/v1/subscription/current',
            'api/v1/offers',
            'api/v1/available-roles',
            'api/v1/available-permissions',
            'api/v1/available-permissions/active',
        ];

        $enterprise = $user->enterprise;
        $hasFieldValue = filled($enterprise->field ?? null);

        // Auto-heal: si le domaine est déjà renseigné, on synchronise le flag.
        if (!$enterprise->domaine_activite_set && $hasFieldValue) {
            $enterprise->forceFill(['domaine_activite_set' => true])->save();
            return $next($request);
        }

        if (
            !$enterprise->domaine_activite_set
            && !($user->site && $user->site->hasActiveSubscription())
            && !in_array($path, $allow, true)
            && !str_starts_with($path, 'api/v1/users')
            && !str_starts_with($path, 'api/v1/enterprises')
            && !str_starts_with($path, 'api/v1/sites')
            && !str_starts_with($path, 'api/v1/subscription')
            && !str_starts_with($path, 'api/v1/offers')
            && !str_starts_with($path, 'api/v1/notifications')
        ) {
            return BlockingAccessResponse::make(
                'COMPANY_SETUP_REQUIRED',
                'Veuillez compléter le domaine d\'activité de votre entreprise.',
                '/company/settings?tab=entreprise',
            );
        }

        return $next($request);
    }
}
