<?php

return [
    'super_admin_only_permissions' => [
        'permissions.read',
        'permissions.create',
        'permissions.update',
        'permissions.delete',
        'offers.create',
        'offers.update',
        'offers.delete',
        'enterprises.delete',
        'norms.read',
        'norms.manage',
        'sessions.read',
        'sessions.revoke',
        'search.read',
        'settings.update',
        'subscriptions.delete',
    ],

    'iso_extension_permissions' => [
        // ISO 45001 - Sécurité
        'securite.habilitations.read',
        'securite.habilitations.create',
        'securite.habilitations.update',
        'securite.habilitations.delete',
        'securite.epi.read',
        'securite.epi.create',
        'securite.epi.update',
        'securite.epi.delete',
        'securite.epi.manage_stock',
        'securite.epi.attribuer',
        'securite.vgp.read',
        'securite.vgp.create',
        'securite.vgp.update',
        'securite.vgp.delete',
        'securite.vgp.validate',

        // ISO 14001 - Environnement
        'environnement.aspects.read',
        'environnement.aspects.create',
        'environnement.aspects.update',
        'environnement.aspects.delete',
        'environnement.aspects.evaluate',
        'environnement.obligations.read',
        'environnement.obligations.create',
        'environnement.obligations.update',
        'environnement.obligations.delete',
        'environnement.obligations.validate',

        // ISO 50001 - Énergie
        'energie.consommations.read',
        'energie.consommations.create',
        'energie.consommations.update',
        'energie.consommations.delete',
        'energie.consommations.import',
        'energie.ipe.read',
        'energie.ipe.create',
        'energie.ipe.update',
        'energie.ipe.delete',
        'energie.ipe.calculate',
    ],

    'roles' => [
        'super_admin' => [
            'strategy' => 'all',
        ],
        'super_admin_readonly' => [
            'permissions' => [
                'dashboard.read',
                'settings.read',
                'enterprises.read',
                'offers.read',
                'subscriptions.read',
                'users.read',
                'norms.read',
                'search.read',
                'sessions.read',
            ],
        ],
        'super_admin_kyc' => [
            'permissions' => [
                'dashboard.read',
                'enterprises.read',
                'enterprises.update',
                'search.read',
            ],
        ],
        'super_admin_offers' => [
            'permissions' => [
                'offers.read',
                'offers.create',
                'offers.update',
                'offers.delete',
                'norms.read',
            ],
        ],
        'super_admin_norms' => [
            'permissions' => [
                'norms.read',
                'norms.manage',
            ],
        ],
        'super_admin_subscriptions' => [
            'permissions' => [
                'subscriptions.read',
                'subscriptions.update',
                'subscriptions.delete',
                'search.read',
            ],
        ],
        'super_admin_users' => [
            'permissions' => [
                'users.read',
                'users.create',
                'users.update',
                'roles.read',
                'search.read',
            ],
        ],
        'super_admin_settings' => [
            'permissions' => [
                'settings.read',
                'settings.update',
            ],
        ],
        'super_admin_sessions' => [
            'permissions' => [
                'sessions.read',
                'sessions.revoke',
            ],
        ],
        'super_admin_search' => [
            'permissions' => [
                'search.read',
            ],
        ],
        'admin_entreprise' => [
            'strategy' => 'all_subscribed',
        ],
        'site_manager' => [
            'strategy' => 'all_subscribed_for_site',
        ],
        'lecteur' => [
            'strategy' => 'read_only_subscribed',
        ],
    ],
];
