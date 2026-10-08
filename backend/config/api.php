<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Legacy Endpoint Hard Deprecation
    |--------------------------------------------------------------------------
    |
    | When enabled, endpoints behind the legacy.deprecated middleware return
    | HTTP 410 Gone instead of serving the request.
    |
    */
    'hard_deprecate_legacy_endpoints' => (bool) env('API_HARD_DEPRECATE_LEGACY_ENDPOINTS', false),
];

