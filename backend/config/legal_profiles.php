<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profils Légaux Multi-Pays
    |--------------------------------------------------------------------------
    |
    | Configuration des champs légaux requis par pays pour les documents
    | personnalisés. Extensible à tous les pays.
    |
    */

    'BJ' => [
        'name' => 'Bénin',
        'fields' => ['rccm_number', 'ifu_number', 'cnss_number', 'fodefca_number'],
        'labels' => [
            'rccm_number' => 'RCCM',
            'ifu_number' => 'IFU',
            'cnss_number' => 'CNSS',
            'fodefca_number' => 'FODEFCA',
        ],
        'format' => '{name} - RCCM: {rccm_number} - IFU: {ifu_number} - CNSS: {cnss_number} - FODEFCA: {fodefca_number}',
        'validation' => [
            'rccm_number' => 'regex:/^RB\/[A-Z]{3}\/\d{2}\s[A-Z]\s\d+$/',
            'ifu_number' => 'regex:/^\d{13}$/',
            'cnss_number' => 'regex:/^\d{8}$/',
            'fodefca_number' => 'regex:/^\d{6}$/',
        ],
    ],

    'FR' => [
        'name' => 'France',
        'fields' => ['siret', 'rcs', 'vat_number', 'ape_code'],
        'labels' => [
            'siret' => 'SIRET',
            'rcs' => 'RCS',
            'vat_number' => 'TVA Intracommunautaire',
            'ape_code' => 'Code APE',
        ],
        'format' => '{name} - SIRET: {siret} - {rcs} - TVA: {vat_number}',
        'validation' => [
            'siret' => 'regex:/^\d{14}$/',
            'rcs' => 'string|max:100',
            'vat_number' => 'regex:/^FR\d{11}$/',
            'ape_code' => 'regex:/^\d{4}[A-Z]$/',
        ],
    ],

    'SN' => [
        'name' => 'Sénégal',
        'fields' => ['ninea', 'rc', 'ipres'],
        'labels' => [
            'ninea' => 'NINEA',
            'rc' => 'Registre de Commerce',
            'ipres' => 'IPRES',
        ],
        'format' => '{name} - NINEA: {ninea} - RC: {rc} - IPRES: {ipres}',
        'validation' => [
            'ninea' => 'regex:/^\d{7}$/',
            'rc' => 'string|max:50',
            'ipres' => 'regex:/^\d{7}$/',
        ],
    ],

    'CI' => [
        'name' => 'Côte d\'Ivoire',
        'fields' => ['cc', 'compte_contribuable', 'cnps'],
        'labels' => [
            'cc' => 'Compte Contribuable',
            'compte_contribuable' => 'Numéro Contribuable',
            'cnps' => 'CNPS',
        ],
        'format' => '{name} - CC: {cc} - Contribuable: {compte_contribuable} - CNPS: {cnps}',
        'validation' => [
            'cc' => 'string|max:50',
            'compte_contribuable' => 'string|max:50',
            'cnps' => 'regex:/^\d{7}$/',
        ],
    ],

    'TG' => [
        'name' => 'Togo',
        'fields' => ['rccm_number', 'ifu_number', 'cnss_number'],
        'labels' => [
            'rccm_number' => 'RCCM',
            'ifu_number' => 'IFU',
            'cnss_number' => 'CNSS',
        ],
        'format' => '{name} - RCCM: {rccm_number} - IFU: {ifu_number} - CNSS: {cnss_number}',
        'validation' => [
            'rccm_number' => 'string|max:100',
            'ifu_number' => 'regex:/^\d{13}$/',
            'cnss_number' => 'regex:/^\d{7}$/',
        ],
    ],

    'MA' => [
        'name' => 'Maroc',
        'fields' => ['ice', 'rc', 'if', 'cnss'],
        'labels' => [
            'ice' => 'ICE',
            'rc' => 'Registre de Commerce',
            'if' => 'Identifiant Fiscal',
            'cnss' => 'CNSS',
        ],
        'format' => '{name} - ICE: {ice} - RC: {rc} - IF: {if}',
        'validation' => [
            'ice' => 'regex:/^\d{15}$/',
            'rc' => 'string|max:50',
            'if' => 'regex:/^\d{8}$/',
            'cnss' => 'regex:/^\d{7}$/',
        ],
    ],

    'TN' => [
        'name' => 'Tunisie',
        'fields' => ['matricule_fiscal', 'rne', 'cnss'],
        'labels' => [
            'matricule_fiscal' => 'Matricule Fiscal',
            'rne' => 'RNE',
            'cnss' => 'CNSS',
        ],
        'format' => '{name} - MF: {matricule_fiscal} - RNE: {rne}',
        'validation' => [
            'matricule_fiscal' => 'regex:/^\d{7}[A-Z]{3}$/',
            'rne' => 'string|max:50',
            'cnss' => 'regex:/^\d{8}$/',
        ],
    ],

    'DZ' => [
        'name' => 'Algérie',
        'fields' => ['nif', 'nis', 'rc'],
        'labels' => [
            'nif' => 'NIF',
            'nis' => 'NIS',
            'rc' => 'Registre de Commerce',
        ],
        'format' => '{name} - NIF: {nif} - NIS: {nis} - RC: {rc}',
        'validation' => [
            'nif' => 'regex:/^\d{15}$/',
            'nis' => 'regex:/^\d{15}$/',
            'rc' => 'string|max:50',
        ],
    ],

    'CM' => [
        'name' => 'Cameroun',
        'fields' => ['nui', 'rccm_number', 'cnps'],
        'labels' => [
            'nui' => 'NUI',
            'rccm_number' => 'RCCM',
            'cnps' => 'CNPS',
        ],
        'format' => '{name} - NUI: {nui} - RCCM: {rccm_number}',
        'validation' => [
            'nui' => 'string|max:50',
            'rccm_number' => 'string|max:100',
            'cnps' => 'regex:/^\d{7}$/',
        ],
    ],

    'GA' => [
        'name' => 'Gabon',
        'fields' => ['rccm_number', 'nif', 'cnss'],
        'labels' => [
            'rccm_number' => 'RCCM',
            'nif' => 'NIF',
            'cnss' => 'CNSS',
        ],
        'format' => '{name} - RCCM: {rccm_number} - NIF: {nif}',
        'validation' => [
            'rccm_number' => 'string|max:100',
            'nif' => 'string|max:50',
            'cnss' => 'regex:/^\d{7}$/',
        ],
    ],

    // Profil générique pour pays non configurés
    'DEFAULT' => [
        'name' => 'Autre',
        'fields' => ['trade_register_number', 'tax_id'],
        'labels' => [
            'trade_register_number' => 'Registre de Commerce',
            'tax_id' => 'Numéro Fiscal',
        ],
        'format' => '{name} - RC: {trade_register_number} - Fiscal: {tax_id}',
        'validation' => [
            'trade_register_number' => 'string|max:100',
            'tax_id' => 'string|max:100',
        ],
    ],
];
