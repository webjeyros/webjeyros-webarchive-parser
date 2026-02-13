<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'majestic' => [
        'api_key' => env('MAJESTIC_API_KEY'),
    ],

    'whois' => [
        'timeout' => env('WHOIS_TIMEOUT', 10),
    ],

    'seo_metrics' => [
        // Основные параметры
        'http_timeout' => env('SEO_HTTP_TIMEOUT', 10),
        'cache_ttl' => env('SEO_CACHE_TTL', 2592000), // 30 дней

        // Yandex API
        'yandex_api' => env('YANDEX_API_URL', 'https://pr-cy.ru/api/yandex_citation'),
        'yandex_fallback' => env('YANDEX_FALLBACK_API', 'https://api.scopeit.ru/api/yandex_citation'),

        // Маъестик
        'majestic_api_url' => env('MAJESTIC_API_URL', 'https://api.majestic.com/api/json'),
        'majestic_datasource' => env('MAJESTIC_DATASOURCE', 'fresh'),

        // Common Crawl
        'common_crawl_api' => env('COMMON_CRAWL_API', 'https://index.commoncrawl.org/CC-MAIN-2026-04'),

        // Archive.org
        'archive_api' => env('ARCHIVE_API', 'https://web.archive.org/cdx/search/cdx'),
    ],

    'domain_check' => [
        // HTTP проверка
        'http_timeout' => env('DOMAIN_HTTP_TIMEOUT', 5),
        'verify_ssl' => env('DOMAIN_VERIFY_SSL', false),

        // Оптимизация
        'skip_dead_domains' => env('SKIP_DEAD_DOMAINS', true), // Пропускать API для доменов не 200
        'check_http_only' => env('CHECK_HTTP_ONLY_LIVE_DOMAINS', true), // Проверять WHOIS + SEO только где HTTP 200
    ],
];
