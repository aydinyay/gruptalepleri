<?php

return [
    'currency' => 'EUR',
    'rfq_max_suppliers' => 10,
    'rfq_deadline_hour' => 16,

    'company' => [
        'brand' => env('CHARTER_COMPANY_BRAND', 'GrupTalepleri.com'),
        'legal_name' => env('CHARTER_COMPANY_LEGAL_NAME', 'GrupTalepleri Turizm Organizasyon ve Tic. Ltd. Sti.'),
        'unit' => env('CHARTER_COMPANY_UNIT', 'Kurumsal Charter Operasyon Birimi'),
        'address' => env('CHARTER_COMPANY_ADDRESS', 'Inonu Mah. Cumhuriyet Cad. No:93/12 Sisli / Istanbul'),
        'phone' => env('CHARTER_COMPANY_PHONE', '+90 535 415 47 99'),
        'support_email' => env('CHARTER_COMPANY_SUPPORT_EMAIL', 'destek@gruptalepleri.com'),
        'website' => env('CHARTER_COMPANY_WEBSITE', 'www.gruptalepleri.com'),
    ],

    'markup' => [
        'global_percent' => 12.0,
        'jet_percent' => 15.0,
        'helicopter_percent' => 14.0,
        'airliner_percent' => 10.0,
        'min_profit' => 1500.0,
    ],

    // System disi tedarikci havuzu (pasif supplier)
    'suppliers' => [
        [
            'name' => 'SkyOps Jet',
            'email' => 'rfq@skyops.example',
            'phone' => '905001110011',
            'service_types' => ['jet', 'airliner'],
        ],
        [
            'name' => 'HeliTransfer',
            'email' => 'ops@helitransfer.example',
            'phone' => '905002220022',
            'service_types' => ['helicopter'],
        ],
        [
            'name' => 'Group Air Carrier',
            'email' => 'sales@groupcarrier.example',
            'phone' => '905003330033',
            'service_types' => ['airliner'],
        ],
    ],
];
