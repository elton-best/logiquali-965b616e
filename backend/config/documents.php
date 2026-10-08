<?php

return [
    'superadmin_signed_url_ttl_minutes' => 10,
    'qr_signature_ttl_minutes' => 43200, // 30 days
    'template_registry' => [
        'allowed_layouts' => ['professional', 'minimal'],
        'default' => [
            'layout' => 'professional',
        ],
        'types' => [
            'process' => ['layout' => 'professional'],
            'process_sheet' => ['layout' => 'professional'],
            'risk' => ['layout' => 'professional'],
            'audit' => ['layout' => 'professional'],
            'non_conformity' => ['layout' => 'professional'],
            'qhse_policy' => ['layout' => 'minimal'],
            'policy' => ['layout' => 'minimal'],
            'manual' => ['layout' => 'minimal'],
            'form' => ['layout' => 'minimal'],
            'record' => ['layout' => 'minimal'],
            'application_scope' => ['layout' => 'professional'],
        ],
    ],
];
