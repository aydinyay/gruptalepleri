<?php

return [
    'currency' => 'EUR',
    'rfq_max_suppliers' => 10,

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
