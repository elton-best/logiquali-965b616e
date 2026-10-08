<?php

require_once __DIR__ . '/autoload_modules.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as SpatieUnauthorizedException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'superadmin'                   => \App\Http\Middleware\EnsureSuperAdmin::class,
            'cache.response'               => \App\Http\Middleware\CacheResponse::class,
            'compress'                     => \App\Http\Middleware\CompressResponse::class,
            'force.json'                   => \App\Http\Middleware\ForceJsonResponse::class,
            'force.password'               => \App\Http\Middleware\ForcePasswordChange::class,
            'force.signature'              => \App\Http\Middleware\ForceSignatureUpload::class,
            'force.company'                => \App\Http\Middleware\ForceCompanySetup::class,
            'check.subscription'           => \App\Http\Middleware\CheckSubscriptionStatus::class,
            'check.module'                 => \App\Http\Middleware\CheckSubModuleAccess::class,
            'ensure.ownership'             => \App\Http\Middleware\EnsureEnterpriseOwnership::class,
            'mfa.stepup'                   => \App\Http\Middleware\EnsureMfaStepUp::class,
            'security.audit'               => \App\Http\Middleware\SecurityAuditMiddleware::class,
            'advanced.rate.limit'          => \App\Http\Middleware\AdvancedRateLimit::class,
            'document.workflow.rate.limit' => \App\Http\Middleware\DocumentWorkflowRateLimit::class,
            'ip.blocking'                  => \App\Http\Middleware\IpBlockingMiddleware::class,
            'legacy.deprecated'            => \App\Http\Middleware\LegacyEndpointDeprecation::class,
            'permission'                   => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role'                         => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission'           => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'validate.permission.assignment' => \App\Http\Middleware\ValidatePermissionAssignment::class,
        ]);

        // Apply compression and force JSON to API responses
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
            \App\Http\Middleware\ResolveAuthTokenFromCookie::class,
            \App\Http\Middleware\IpBlockingMiddleware::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\CompressResponse::class,
            \App\Http\Middleware\SecurityAuditMiddleware::class,
            \App\Http\Middleware\ForceSessionTimeout::class,
        ]);

        $middleware->throttleApi('api');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Supervision : remonter les exceptions à la plateforme de supervision
        $exceptions->report(function (\Throwable $e) {
            if (class_exists(\Beg\SupervisionClient\Exceptions\ReportsToSupervisionHelper::class)) {
                \Beg\SupervisionClient\Exceptions\ReportsToSupervisionHelper::report($e);
            }
        });

        // Handle validation exceptions for API (must stay 422 in all envs, including testing)
        $exceptions->renderable(function (ValidationException $e, $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $message = $e->getMessage();
            $errors  = $e->errors();

            // Détecter les erreurs de configuration documentaire
            $isDocumentConfigError = str_contains($message, 'configuration documentaire')
                || str_contains($message, 'Aucune configuration')
                || isset($errors['document_type_configuration_id']);

            if ($isDocumentConfigError) {
                Log::info('Configuration documentaire manquante', [
                    'message'       => $message,
                    'request_path'  => $request->path(),
                    'user_id'       => $request->user()?->id,
                    'site_id'       => $request->user()?->site_id,
                    'enterprise_id' => $request->user()?->enterprise_id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'La configuration documentaire n\'est pas encore paramétrée pour ce site. '
                        . 'Rendez-vous dans le module Information documentée pour configurer les types de documents.',
                    'errors'  => [
                        'document_type_configuration_id' => [
                            'Configuration documentaire manquante pour ce site.',
                        ],
                    ],
                ], 422);
            }

            // Comportement standard pour les autres ValidationException
            return response()->json([
                'message' => $message,
                'errors'  => $errors,
            ], 422);
        });

        // ─── ErrorException : erreurs PHP (variables non définies, etc.) ─────
        // Intercepte les ErrorException comme "Undefined variable $filePath".
        $exceptions->renderable(function (\ErrorException $e, $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $context = [
                'exception'      => get_class($e),
                'message'        => $e->getMessage(),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
                'request_path'   => $request->path(),
                'request_method' => $request->method(),
                'user_id'        => $request->user()?->id,
                'ip'             => $request->ip(),
            ];

            if (app()->environment('local')) {
                Log::warning('ErrorException (local — non masquée)', $context);
                return null;
            }

            Log::error('ErrorException interceptée', $context);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur technique est survenue. Veuillez réessayer ou contacter le support.',
            ], 500);
        });

        // ─── AuthenticationException ──────────────────────────────────────────
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié. Veuillez vous connecter.',
                ], 401);
            }
        });

        // ─── AuthorizationException ───────────────────────────────────────────
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée. Vous n\'avez pas les permissions nécessaires.',
                ], 403);
            }
        });

        // ─── Spatie UnauthorizedException ─────────────────────────────────────
        $exceptions->renderable(function (SpatieUnauthorizedException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée. Vous n\'avez pas les permissions nécessaires.',
                ], 403);
            }
        });

        // ─── AccessDeniedHttpException ────────────────────────────────────────
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Action non autorisée. Vous n\'avez pas les permissions nécessaires.',
                ], 403);
            }
        });

        // ─── Throwable générique (dernier recours) ────────────────────────────
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($e instanceof HttpExceptionInterface) {
                return null;
            }

            if (!$request->is('api/*')) {
                return null;
            }

            $context = [
                'exception'      => get_class($e),
                'message'        => $e->getMessage(),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
                'trace'          => $e->getTraceAsString(),
                'request_path'   => $request->path(),
                'request_method' => $request->method(),
                'user_id'        => $request->user()?->id,
                'ip'             => $request->ip(),
            ];

            Log::error('API Error non géré: ' . $e->getMessage(), $context);

            // En local : laisser Laravel afficher l'erreur complète
            if (app()->environment('local')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue. Veuillez réessayer.',
            ], 500);
        });
    })->create();
