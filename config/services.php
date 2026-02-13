<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'seorank' => [
        'key' => env('SEORANK_API_KEY'),
        'url' => env('SEORANK_API_URL', 'https://api.seo-rank.com'),
        'rate_limit' => env('SEORANK_RATE_LIMIT', 1000),
    ],

    'yandex' => [
        'api_key' => env('YANDEX_API_KEY'),
    ],

    'wayback' => [
        'api_url' => env('WAYBACK_API_URL', 'https://archive.org/wayback/available'),
        'timeout' => env('WAYBACK_TIMEOUT', 30),
        'per_page' => env('WAYBACK_PARSER_PER_PAGE', 100),
    ],
];
