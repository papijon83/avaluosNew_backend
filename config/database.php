<?php
return [
    'default' => 'oracle',
    'connections' => [
        'oracle' => [
            'driver'        => 'oracle',
            'tns'           => env('DB_TNS', ''),
            'host'          => env('DB_HOST', ''),
            'port'          => env('DB_PORT', '1521'),
            'database'      => env('DB_DATABASE', ''),
            'username'      => env('DB_USERNAME', ''),
            'password'      => env('DB_PASSWORD', ''),
            'charset'       => env('DB_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB_PREFIX', ''),
            'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
            'edition'       => env('DB_EDITION', 'ora$base'),
            
        ],
        'oracleovica' => [
            'driver'        => 'oracle',
            'tns'           => env('DB_TNS_OV', ''),
            'host'          => env('DB_HOST', ''),
            'port'          => env('DB_PORT', '1521'),
            'database'      => env('DB_DATABASE', ''),
            'username'      => env('DB_USERNAME_OV', ''),
            'password'      => env('DB_PASSWORD_OV', ''),
            'charset'       => env('DB_CHARSET', 'AL32UTF8'),
            'prefix'        => env('DB_PREFIX', ''),
            'prefix_schema' => env('DB_SCHEMA_PREFIX', ''),
            'edition'       => env('DB_EDITION', 'ora$base'),
        ]
    ]
];
