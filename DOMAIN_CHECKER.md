# Domain Checker с поддержкой SEO метрик

## Описание

Модуль проверки доменов с поддержкой:
- **HTTP 200 validation** - проверка, что домен живой перед дорогостоящими API запросами
- **WHOIS информация** - данные о регистрации домена
- **SEO метрики** из бесплатных источников:
  - **ТИЦ (Индекс Цитирования Яндекса)** - через `pr-cy.ru` API
  - **Русские бэклинки** - через archive.org
  - **Бэклинки и домены** - через Majestic Free API
  - **Индексируемые страницы** - через Common Crawl
  - **Meta теги** - парсинг самой страницы

## Ключевая оптимизация

### Проверка HTTP 200 перед API запросами

```php
// Если домен мертв (не 200), пропускаем дорогие запросы
if ($httpStatus['code'] !== 200) {
    Log::info("Domain {$domain} returned HTTP {$httpStatus['code']} - skipping metrics");
    return [
        'domain' => $domain,
        'status' => 'dead',
        'http_code' => $httpStatus['code'],
    ];
}

// Только для живых доменов запрашиваем SEO метрики
$seoMetrics = $this->seoMetricsService->getSeoMetrics($domain);
```

## API Endpoints

### 1. Проверить один домен

```bash
curl -X POST http://localhost:8000/api/domain/check \
  -H "Content-Type: application/json" \
  -d '{"domain": "example.com"}'
```

**Ответ (живой домен HTTP 200):**
```json
{
  "domain": "example.com",
  "status": "active",
  "http_code": 200,
  "seo_metrics": {
    "ticy": 42,
    "yandex_rank": 123,
    "backlinks_ru": 156,
    "backlink_count": 450,
    "referring_domains": 42,
    "domain_authority": 35.5,
    "spam_score": 12.3,
    "indexed_pages": 1250
  },
  "whois_data": {
    "registrar": "REG.RU",
    "created_date": "2015-03-01",
    "expiration_date": "2026-03-01"
  },
  "message": "Full check completed successfully"
}
```

**Ответ (мертвый домен):**
```json
{
  "domain": "example.com",
  "status": "dead",
  "http_code": 404,
  "message": "Domain is inaccessible - no additional checks required"
}
```

### 2. Батч проверка доменов

```bash
curl -X POST http://localhost:8000/api/domain/batch-check \
  -H "Content-Type: application/json" \
  -d '{
    "domains": [
      "google.com",
      "example.com",
      "dead-domain.com"
    ]
  }'
```

**Ответ:**
```json
{
  "total": 3,
  "active": 2,
  "dead": 1,
  "errors": 0,
  "results": [
    {
      "domain": "google.com",
      "status": "active",
      "http_code": 200,
      "seo_metrics": { ... }
    },
    {
      "domain": "example.com",
      "status": "active",
      "http_code": 200,
      "seo_metrics": { ... }
    },
    {
      "domain": "dead-domain.com",
      "status": "dead",
      "http_code": 0,
      "message": "Domain is inaccessible"
    }
  ]
}
```

### 3. Получить домены с фильтрами

```bash
# Только живые домены
curl "http://localhost:8000/api/domains?status=live"

# Только с метриками
curl "http://localhost:8000/api/domains?has_metrics=1"

# С минимальным ТИЦ 30
curl "http://localhost:8000/api/domains?min_ticy=30"

# С минимум 100 бэклинками
curl "http://localhost:8000/api/domains?min_backlinks=100"

# Комбинированный фильтр
curl "http://localhost:8000/api/domains?status=live&has_metrics=1&min_ticy=25&min_backlinks=50&per_page=20"
```

**Ответ:**
```json
{
  "data": [
    {
      "id": 1,
      "domain": "example.com",
      "http_status_code": 200,
      "ticy": 42,
      "yandex_rank": 123,
      "backlinks_ru": 156,
      "backlink_count": 450,
      "referring_domains": 42,
      "domain_authority": 35.5,
      "spam_score": 12.3,
      "indexed_pages": 1250,
      "metrics_available": true,
      "metrics_checked_at": "2026-02-13T17:30:00Z"
    }
  ],
  "meta": {
    "total": 150,
    "per_page": 20,
    "current_page": 1
  }
}
```

## Использование в коде

### Проверить один домен

```php
<?php

use App\Services\SeoMetricsService;

class SomeClass
{
    public function __construct(private SeoMetricsService $seoService)
    {}

    public function analyzeDomain($domain)
    {
        $metrics = $this->seoService->getSeoMetrics($domain);
        
        echo "ТИЦ: " . $metrics['ticy'] . "\n";
        echo "Бэклинки (RU): " . $metrics['backlinks_ru'] . "\n";
        echo "Бэклинки: " . $metrics['backlink_count'] . "\n";
    }
}
```

### Проверить батч доменов

```php
<?php

use App\Models\Domain;

$domains = [
    'google.com',
    'example.com',
    'github.com',
];

foreach ($domains as $domain) {
    $response = Http::post('http://localhost:8000/api/domain/check', [
        'domain' => $domain
    ]);
    
    if ($response->successful()) {
        $data = $response->json();
        if ($data['status'] === 'active') {
            // Домен живой - используем метрики
            echo "TICY: {$data['seo_metrics']['ticy']}\n";
        }
    }
}
```

## Конфигурация

Добавьте в `.env`:

```env
# Yandex TICs API
YANDEX_API_URL=https://pr-cy.ru/api/yandex_citation
YANDEX_FALLBACK_API=https://api.scopeit.ru/api/yandex_citation

# Majestic (опционально, если есть API key)
MAJESTIC_API_KEY=your_api_key_here

# Таймауты
DOMAIN_HTTP_TIMEOUT=5
SEO_HTTP_TIMEOUT=10
WHOIS_TIMEOUT=10

# Оптимизация
SKIP_DEAD_DOMAINS=true  # Пропускать API для мертвых доменов
CHECK_HTTP_ONLY_LIVE_DOMAINS=true  # WHOIS/SEO только для живых
SEO_CACHE_TTL=2592000  # 30 дней кэша
```

## Оптимизация лимитов

### Что мы экономим

1. **Majestic API** (600 запросов/день):
   - Только для доменов с HTTP 200
   - Результаты кэшируются 30 дней

2. **WHOIS запросы**:
   - Проверяем только живые домены
   - Кэшируем результаты

3. **Yandex TICs API** (бесплатный):
   - Кэшируется 30 дней
   - Используем fallback API для надежности

### Пример оптимизации

```php
// ДО: 1000 доменов = 1000 Majestic запросов
foreach ($domains as $domain) {
    $metrics = $this->seoService->getSeoMetrics($domain);
}

// ПОСЛЕ: только живые домены
foreach ($domains as $domain) {
    $status = $this->checkHttpStatus($domain);
    
    if ($status['code'] === 200) {
        // Только 150 живых доменов = 150 Majestic запросов
        $metrics = $this->seoService->getSeoMetrics($domain);
    } else {
        // Мертвый домен, пропускаем дорогие запросы
        Log::info("Skipped dead domain: {$domain}");
    }
}
```

## Миграция

```bash
php artisan migrate
```

Добавляет таблицы:
- `domains` с новыми полями:
  - `ticy` - ТИЦ Яндекса
  - `yandex_rank` - Ранг Яндекса
  - `backlinks_ru` - Русские бэклинки
  - `http_status_code` - HTTP статус
  - `metrics_available` - флаг наличия метрик

## Throttling

```php
Route::post('/domain/check', [...])              // 30 запросов/минуту
Route::post('/domain/batch-check', [...])        // 10 запросов/минуту
Route::get('/domains', [...])                     // 60 запросов/минуту
```

## Ошибки и обработка

```json
{
  "domain": "example.com",
  "status": "error",
  "message": "Connection timeout after 5 seconds",
  "error_code": "TIMEOUT"
}
```

## Лицензия

MIT
