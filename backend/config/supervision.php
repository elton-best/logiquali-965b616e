<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoint Supervision
    |--------------------------------------------------------------------------
    | URL de base de la plateforme Supervision (sans slash final).
    */
    'endpoint' => env('SUPERVISION_ENDPOINT', 'https://supervision.bestexperts.bj'),

    /*
    |--------------------------------------------------------------------------
    | Identifiants projet
    |--------------------------------------------------------------------------
    | `project_code` doit correspondre au code défini côté Supervision.
    | `secret` est le webhook_secret généré dans ProjectCredentials.
    */
    'project_code' => env('SUPERVISION_PROJECT_CODE'),
    'secret' => env('SUPERVISION_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Activation globale
    |--------------------------------------------------------------------------
    | Désactive complètement les envois (utile en dev / tests).
    */
    'enabled' => env('SUPERVISION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Logs
    |--------------------------------------------------------------------------
    */
    'logs' => [
        'enabled' => env('SUPERVISION_LOGS_ENABLED', true),
        // Niveau minimum à pousser. Inférieur => ignoré.
        // debug|info|notice|warning|error|critical|alert|emergency
        'min_level' => env('SUPERVISION_LOGS_MIN_LEVEL', 'info'),
        // Taille de batch déclenchant un flush automatique.
        'batch_size' => env('SUPERVISION_LOGS_BATCH_SIZE', 50),
        // Canaux Monolog à exclure (ex: ['queue', 'horizon']).
        'exclude_channels' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Erreurs (exceptions)
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'enabled' => env('SUPERVISION_ERRORS_ENABLED', true),
        // Exceptions à ignorer (utiles pour bruit comme ValidationException).
        'ignore' => [
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Paiements
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'enabled' => env('SUPERVISION_PAYMENTS_ENABLED', true),
        // Currency par défaut si non précisée à l'envoi.
        'default_currency' => env('SUPERVISION_PAYMENTS_DEFAULT_CURRENCY', 'XOF'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP
    |--------------------------------------------------------------------------
    */
    'http' => [
        'timeout' => env('SUPERVISION_HTTP_TIMEOUT', 5),
        'connect_timeout' => env('SUPERVISION_HTTP_CONNECT_TIMEOUT', 2),
        // Si l'envoi échoue, ne PAS bloquer l'app appelante.
        'silent_failures' => env('SUPERVISION_HTTP_SILENT_FAILURES', true),
        // Disjoncteur : après N échecs consécutifs, on coupe pendant X secondes.
        'circuit_breaker' => [
            'enabled' => env('SUPERVISION_CB_ENABLED', true),
            'failure_threshold' => env('SUPERVISION_CB_THRESHOLD', 5),
            'cooldown_seconds' => env('SUPERVISION_CB_COOLDOWN', 60),
        ],
        'headers' => [
            'signature' => 'X-Supervision-Signature',
            'timestamp' => 'X-Supervision-Timestamp',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environnement détecté envoyé avec chaque log
    |--------------------------------------------------------------------------
    */
    'environment' => env('SUPERVISION_ENV', env('APP_ENV', 'production')),

];
