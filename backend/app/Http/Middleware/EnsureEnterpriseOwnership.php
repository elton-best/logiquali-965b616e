<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnterpriseOwnership
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Super admin bypass
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Vérifier ownership sur routes avec {id}
        $routeParameters = $request->route()->parameters();
        
        foreach ($routeParameters as $paramName => $paramValue) {
            if ($paramName === 'id' || str_ends_with($paramName, '_id')) {
                $this->verifyOwnership($request, $paramName, $paramValue, $user);
            }
        }

        return $next($request);
    }

    private function verifyOwnership(Request $request, string $paramName, $paramValue, $user): void
    {
        $routeName = $request->route()?->getName();

        // Mapper les paramètres vers les modèles
        $modelMap = [
            'id' => $this->guessModelFromRoute($routeName),
            'equipement_id' => \App\Models\Equipement::class,
            'maintenance_id' => \App\Models\Maintenance::class,
            'habilitation_id' => \App\Models\Habilitation::class,
            'formation_id' => \App\Models\Formation::class,
            'risk_id' => \App\Models\Risk::class,
            'action_id' => \App\Models\Action::class,
            'audit_id' => \App\Models\Audit::class,
            'process_id' => \App\Models\Process::class,
        ];

        $modelClass = $modelMap[$paramName] ?? null;
        
        if (!$modelClass || !class_exists($modelClass)) {
            return;
        }

        try {
            $model = $modelClass::withoutGlobalScope('enterprise')->find($paramValue);
            
            if (!$model) {
                abort(404);
            }

            // Vérifier ownership selon le rôle
            if ($user->isEnterpriseAdmin()) {
                // Admin entreprise : vérifier enterprise_id
                if ($model->enterprise_id !== $user->enterprise_id) {
                    $this->logUnauthorizedAccess($user, $modelClass, $paramValue, 'enterprise_mismatch');
                    abort(403, 'Accès refusé : ressource appartient à une autre entreprise');
                }
            } else {
                // Utilisateur normal : vérifier site_id
                if ($model->site_id !== $user->site_id) {
                    $this->logUnauthorizedAccess($user, $modelClass, $paramValue, 'site_mismatch');
                    abort(403, 'Accès refusé : ressource appartient à un autre site');
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur vérification ownership', [
                'user_id' => $user->id,
                'model' => $modelClass,
                'param_value' => $paramValue,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function guessModelFromRoute(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        $routeModelMap = [
            'equipements.' => \App\Models\Equipement::class,
            'maintenances.' => \App\Models\Maintenance::class,
            'habilitations.' => \App\Models\Habilitation::class,
            'formations.' => \App\Models\Formation::class,
            'risks.' => \App\Models\Risk::class,
            'actions.' => \App\Models\Action::class,
            'audits.' => \App\Models\Audit::class,
            'processes.' => \App\Models\Process::class,
        ];

        foreach ($routeModelMap as $prefix => $model) {
            if (str_starts_with($routeName, $prefix)) {
                return $model;
            }
        }

        return null;
    }

    private function logUnauthorizedAccess($user, string $modelClass, $resourceId, string $reason): void
    {
        // Log to Laravel log
        Log::warning('Tentative d\'accès non autorisé', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_enterprise_id' => $user->enterprise_id,
            'user_site_id' => $user->site_id,
            'model' => $modelClass,
            'resource_id' => $resourceId,
            'reason' => $reason,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Log to security audit
        \App\Models\SecurityAuditLog::logEvent(
            eventType: 'unauthorized_access',
            action: 'access_denied',
            resourceType: $modelClass,
            resourceId: $resourceId,
            metadata: [
                'reason' => $reason,
                'target_resource' => $modelClass,
                'target_id' => $resourceId
            ],
            riskLevel: 'high'
        );
    }
}
