<?php

namespace App\Http\Middleware;

use App\Support\BlockingAccessResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSignatureUpload
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        if (!$user->isCompanyUser() || $user->isSuperAdmin()) {
            return $next($request);
        }

        // Mode "soft" global:
        // aucune route n'est bloquee tant que la liste des actions exigeant
        // une signature n'est pas formellement definie.
        // Le middleware reste en place pour un futur blocage contextuel.
        $requiresSignature = (bool) $request->attributes->get('requires_signature', false);
        if (!$requiresSignature) {
            return $next($request);
        }

        if (empty($user->signature_path)) {
            return BlockingAccessResponse::make(
                'SIGNATURE_UPLOAD_REQUIRED',
                'Une signature est requise pour effectuer cette action.',
                '/company/settings?tab=profil',
            );
        }

        return $next($request);
    }
}
