<?php

return [
    'allowed_countries' => ['US'],

    'disallowed_countries' => [],

    'accepted_locales' => ['en'],

    'modules' => [
        'states' => true,
        'cities' => true,
        'timezones' => false,
        'currencies' => false,
        'languages' => false,
        'geolocate' => false,
    ],

    'routes' => false,

    'connection' => env('WORLD_DB_CONNECTION', env('DB_CONNECTION')),

    'migrations' => [
        'countries' => [
            'table_name' => 'world_countries',
            'optional_fields' => [
                'phone_code' => [
                    'required' => true,
                    'type' => 'string',
                    'length' => 5,
                ],
                'iso3' => [
                    'required' => true,
                    'type' => 'string',
                    'length' => 3,
                ],
                'native' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'region' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'subregion' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'latitude' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'longitude' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'emoji' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'emojiU' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ],
        'states' => [
            'table_name' => 'world_states',
            'optional_fields' => [
                'country_code' => [
                    'required' => true,
                    'type' => 'string',
                    'length' => 3,
                ],
                'state_code' => [
                    'required' => true,
                    'type' => 'string',
                    'length' => 5,
                ],
                'type' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'latitude' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'longitude' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ],
        'cities' => [
            'table_name' => 'world_cities',
            'optional_fields' => [
                'country_code' => [
                    'required' => true,
                    'type' => 'string',
                    'length' => 3,
                ],
                'state_code' => [
                    'required' => true,
                    'type' => 'string',
                    'length' => 5,
                ],
                'latitude' => [
                    'required' => false,
                    'type' => 'string',
                ],
                'longitude' => [
                    'required' => false,
                    'type' => 'string',
                ],
            ],
        ],
        'timezones' => [
            'table_name' => 'world_timezones',
        ],
        'currencies' => [
            'table_name' => 'world_currencies',
        ],
        'languages' => [
            'table_name' => 'world_languages',
        ],
    ],

    'models' => [
        'cities' => \Nnjeim\World\Models\City::class,
        'countries' => \Nnjeim\World\Models\Country::class,
        'currencies' => \Nnjeim\World\Models\Currency::class,
        'languages' => \Nnjeim\World\Models\Language::class,
        'states' => \Nnjeim\World\Models\State::class,
        'timezones' => \Nnjeim\World\Models\Timezone::class,
    ],

    'geolocate' => [
        'database_path' => storage_path('app/geoip/GeoLite2-City.mmdb'),
        'cache_ttl' => env('WORLD_GEOLOCATE_CACHE_TTL', 86400),
        'maxmind_license_key' => env('MAXMIND_LICENSE_KEY'),
        'fallback_api' => env('WORLD_GEOLOCATE_FALLBACK_API', false),
    ],
];
