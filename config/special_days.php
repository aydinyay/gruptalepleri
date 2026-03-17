<?php

return [
    'fixed' => [
        [
            'key' => 'yeni-yil',
            'name' => 'Yeni Yil',
            'month' => 1,
            'day' => 1,
            'category' => 'genel',
            'default_prompt' => 'Yeni yil icin B2B seyahat dunyasina uygun kutlama gorseli ve mesaji olustur.',
            'priority' => 120,
        ],
        [
            'key' => 'ulusal-egemenlik',
            'name' => '23 Nisan Ulusal Egemenlik ve Cocuk Bayrami',
            'month' => 4,
            'day' => 23,
            'category' => 'milli',
            'default_prompt' => '23 Nisan icin kurumsal ve saygili bir kutlama gorseli ve mesaji olustur.',
            'priority' => 80,
        ],
        [
            'key' => 'genclik-spor',
            'name' => '19 Mayis Ataturk\'u Anma, Genclik ve Spor Bayrami',
            'month' => 5,
            'day' => 19,
            'category' => 'milli',
            'default_prompt' => '19 Mayis icin kurumsal kutlama gorseli ve mesaji olustur.',
            'priority' => 80,
        ],
        [
            'key' => 'zafer-bayrami',
            'name' => '30 Agustos Zafer Bayrami',
            'month' => 8,
            'day' => 30,
            'category' => 'milli',
            'default_prompt' => '30 Agustos icin kurumsal kutlama gorseli ve mesaji olustur.',
            'priority' => 80,
        ],
        [
            'key' => 'cumhuriyet-bayrami',
            'name' => '29 Ekim Cumhuriyet Bayrami',
            'month' => 10,
            'day' => 29,
            'category' => 'milli',
            'default_prompt' => '29 Ekim icin kurumsal kutlama gorseli ve mesaji olustur.',
            'priority' => 70,
        ],
        [
            'key' => 'ogretmenler-gunu',
            'name' => 'Ogretmenler Gunu',
            'month' => 11,
            'day' => 24,
            'category' => 'ozel',
            'default_prompt' => 'Ogretmenler Gunu icin sade ve samimi kutlama gorseli ve mesaji olustur.',
            'priority' => 120,
        ],
    ],

    'floating' => [
        [
            'key' => 'anneler-gunu',
            'name' => 'Anneler Gunu',
            'rule' => 'second_sunday_may',
            'category' => 'ozel',
            'default_prompt' => 'Anneler Gunu icin zarif ve samimi bir kutlama gorseli ve mesaji olustur.',
            'priority' => 100,
        ],
        [
            'key' => 'babalar-gunu',
            'name' => 'Babalar Gunu',
            'rule' => 'third_sunday_june',
            'category' => 'ozel',
            'default_prompt' => 'Babalar Gunu icin sade, kurumsal ve samimi bir kutlama gorseli olustur.',
            'priority' => 110,
        ],
    ],

    'yearly' => [
        2026 => [
            [
                'key' => 'kadir-gecesi',
                'name' => 'Kadir Gecesi',
                'date' => '2026-03-17',
                'category' => 'dini',
                'default_prompt' => 'Kadir Gecesi icin saygili, sade ve kurumsal bir kutlama gorseli olustur.',
                'priority' => 60,
            ],
            [
                'key' => 'ramazan-bayrami-1',
                'name' => 'Ramazan Bayrami',
                'date' => '2026-03-20',
                'category' => 'dini',
                'default_prompt' => 'Ramazan Bayrami icin kurumsal bir kutlama gorseli ve mesaji olustur.',
                'priority' => 55,
            ],
        ],
    ],
];

