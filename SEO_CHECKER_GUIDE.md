# Бесплатная система проверки SEO и доступности доменов

## Обзор

Эта система автоматически проверяет найденные домены по ключевым словам и собирает SEO метрики из бесплатных источников.

### Основные возможности

✅ **Проверка доступности домена** - Whois, DNS, HTTP
✅ **SEO метрики** из бесплатных источников (Majestic API, Common Crawl)
✅ **Автоматизация** - фоновые jobs для массовой обработки
✅ **Хранение данных** - сохранение в БД для дальнейшего анализа
✅ **Интеллектуальная фильтрация** - отсеивание мертвых доменов

---

## Установка и настройка

### 1. Миграция БД

```bash
php artisan migrate
```

Это добавит все необходимые поля для хранения SEO метрик:
- WHOIS данные (регистратор, даты)
- HTTP статусы и IP адреса
- Nameservers
- SEO метрики (бэклинки, DA, спам-скор)

### 2. Установка зависимостей

```bash
composer require whois-api-india/php-whois-parser
composer require guzzlehttp/guzzle
```

### 3. Конфигурация (`.env`)

```env
# Free SEO APIs
MAJESTIC_API_KEY=your_free_key  # Optional for paid, but basic info is free
PAGES_API_KEY=your_key          # Optional

# Queue settings
QUEUE_DRIVER=database

# Rate limiting
SEO_CHECK_RATE_LIMIT=10         # requests per minute
WHOIS_RATE_LIMIT=5              # queries per minute
```

---

## Бесплатные источники SEO данных

### 1. **Whois API** (БЕСПЛАТНО - до 500 запросов/день)

**Что получаем:**
- Статус регистрации
- Регистратор
- Даты создания, обновления, истечения
- Nameservers

**API:**
```bash
curl 'https://www.whois.com/whois/example.com' \
  -H 'Accept: application/json'
```

**Или через PHP:**
```php
$whois = new WhoisQuery();
$result = $whois->lookup('example.com');
```

### 2. **HTTP Status Check** (БЕСПЛАТНО)

```php
$response = Http::timeout(5)->get('http://example.com');
$statusCode = $response->status();
```

### 3. **DNS Lookup** (БЕСПЛАТНО)

```php
$records = dns_get_record('example.com', DNS_A + DNS_NS);
$ip = $records[0]['ip'] ?? null;
```

### 4. **Majestic API** (БЕСПЛАТНО - базовые метрики)

**Бесплатные метрики:**
- Citation Flow (CF) - как часто цитируется
- Trust Flow (TF) - насколько надежный
- External Links Count
- Referring Domains Count

**API endpoint:**
```
https://api.majestic.com/api/json?cmd=GetIndexItemInfo&item=example.com&datasource=fresh&app_api_key=YOUR_FREE_KEY
```

**Регистрация:** https://majestic.com/register

### 5. **Common Crawl** (БЕСПЛАТНО)

Дает информацию о том, сколько раз сайт был проиндексирован:

```php
$url = 'https://index.commoncrawl.org/CC-MAIN-2026-04/?url=example.com';
$index = file_get_contents($url);
```

### 6. **Google Search Index** (БЕСПЛАТНО - через Google)

```php
// Проверка индексации
$searchUrl = "https://www.google.com/search?q=site:example.com";
```

### 7. **Built With API** (БЕСПЛАТНО - основные данные)

**Получаем:** какие технологии используются на сайте

```
https://api.builtwith.com/free/api.json?key=YOUR_KEY&domain=example.com
```

---

## Архитектура решения

### Основные компоненты

```
Keyword (ключевое слово)
    ↓
ParseWaybackJob (поиск в Wayback Machine)
    ↓
Domain (найденный домен)
    ↓
CheckDomainAvailabilityJob (Whois, DNS)
    ↓
CheckDomainSeoJob (SEO метрики)
    ↓
Database (сохранение)
```

### Jobs (Фоновые задачи)

#### 1. **ParseWaybackJob**
- Ищет домены по ключевым словам в Wayback Machine
- Создает записи Domain
- Диспатчит CheckDomainAvailabilityJob

#### 2. **CheckDomainAvailabilityJob**
- Проверяет WHOIS информацию
- Проверяет HTTP статус
- Получает DNS записи

#### 3. **CheckDomainSeoJob**
- Получает SEO метрики из Majestic, Common Crawl
- Рассчитывает SEO Health Score
- Обновляет статус домена (available/occupied/dead)

### Сервисы

#### **DomainCheckerService**
```php
class DomainCheckerService
{
    public function comprehensiveCheck(string $domain): array
    {
        return [
            'available' => bool,
            'http_status' => int,
            'availability_data' => [...whois...]
        ];
    }
}
```

#### **SeoMetricsService**
```php
class SeoMetricsService
{
    public function getSeoMetrics(string $domain): array
    {
        return [
            'domain_authority' => float,
            'backlink_count' => int,
            'spam_score' => float,
            ...
        ];
    }
}
```

---

## Использование

### 1. Автоматическая проверка при парсинге

```php
// При создании проекта и добавлении ключевых слов
$project = Project::create(['name' => 'My Project']);
$keyword = $project->keywords()->create(['keyword' => 'best php hosting']);

// Автоматически запустится ParseWaybackJob
```

### 2. Ручная проверка домена

```php
$domain = Domain::find(1);
CheckDomainSeoJob::dispatch($domain);
```

### 3. Массовая переполиция

```php
// Перепроверить все домены со статусом 'in_work'
$domains = Domain::where('status', 'in_work')
    ->limit(100)
    ->get();

foreach ($domains as $domain) {
    CheckDomainSeoJob::dispatch($domain);
}
```

### 4. Фильтрация результатов

```php
// Получить все доступные домены
$available = Domain::where('status', 'available')
    ->orderBy('domain_authority', 'desc')
    ->limit(20)
    ->get();

// Получить занятые домены с хорошим трафиком
$good = Domain::where('status', 'occupied')
    ->where('domain_authority', '>=', 30)
    ->where('spam_score', '<=', 10)
    ->orderBy('backlink_count', 'desc')
    ->get();
```

### 5. Анализ SEO Health

```php
foreach ($domains as $domain) {
    $score = $domain->getSeoHealthScore(); // 0-100
    
    echo $domain->domain . ': ' . $score . '/100';
    
    if ($domain->isExpiringsoon()) {
        echo ' (Expiring soon!)';
    }
}
```

---

## Структура БД

### Таблица `domains`

```sql
CREATE TABLE domains (
    -- Base fields
    id INT PRIMARY KEY,
    project_id INT,
    keyword_id INT,
    domain VARCHAR(255),
    status VARCHAR(50),  -- new, checking, available, occupied, dead, in_work
    available BOOLEAN,
    
    -- HTTP/DNS
    http_status_code INT,
    ip_address VARCHAR(45),
    last_http_check DATETIME,
    
    -- WHOIS
    registrar VARCHAR(255),
    created_date DATETIME,
    updated_date DATETIME,
    expiration_date DATETIME,
    nameserver_1 VARCHAR(255),
    nameserver_2 VARCHAR(255),
    nameserver_3 VARCHAR(255),
    
    -- SEO Metrics
    domain_authority DECIMAL(5,2),
    spam_score DECIMAL(5,2),
    backlink_count INT,
    referring_domains INT,
    indexed_pages INT,
    external_links INT,
    internal_links INT,
    
    -- Metadata
    metrics_source VARCHAR(50),
    metrics_checked_at DATETIME,
    metrics_available BOOLEAN,
    
    -- System
    created_at DATETIME,
    updated_at DATETIME,
    
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (keyword_id) REFERENCES keywords(id)
);
```

---

## Примеры использования

### Пример 1: Найти лучшие доступные домены

```php
$bestAvailable = Domain::where('status', 'available')
    ->where('domain_authority', '>=', 20)
    ->orderBy('domain_authority', 'desc')
    ->paginate(50);
```

### Пример 2: Найти занятые домены для покупки

```php
$candidates = Domain::where('status', 'occupied')
    ->where('spam_score', '<', 15)  // Low spam
    ->where('backlink_count', '>', 10)  // Some links
    ->where(function ($query) {
        $query->whereNull('expiration_date')
              ->orWhere('expiration_date', '<', now()->addMonths(3));
    })
    ->orderBy('domain_authority', 'desc')
    ->get();
```

### Пример 3: Экспорт данных

```php
// Export to CSV
$domains = Domain::where('status', 'occupied')->get();

$csv = "domain,authority,spam_score,backlinks,expiration\n";
foreach ($domains as $domain) {
    $csv .= implode(',', [
        $domain->domain,
        $domain->domain_authority,
        $domain->spam_score,
        $domain->backlink_count,
        $domain->expiration_date->format('Y-m-d'),
    ]) . "\n";
}

file_put_contents('domains.csv', $csv);
```

---

## Лимиты и ограничения

### Бесплатные лимиты

| Source | Limit | Notes |
|--------|-------|-------|
| Majestic Free | 600 requests/day | Базовые метрики |
| Common Crawl | Unlimited | Но медленно |
| Whois | 500/day | Через открытые API |
| Google | Unlimited | Rate limited по IP |
| Ping | Unlimited | ~10 per second |

### Рекомендации

- ✅ Запускайте проверки ночью
- ✅ Используйте rate limiting
- ✅ Кэшируйте результаты
- ✅ Проверяйте batch по 10-20 доменов

---

## Roadmap (будущие улучшения)

- [ ] Google Search Console интеграция
- [ ] Semrush API (бесплатные данные)
- [ ] Archive.org API улучшение
- [ ] Telegram/Email уведомления
- [ ] Веб-интерфейс для управления
- [ ] Экспорт в различные форматы

---

## Поддержка

При вопросах смотрите:
- `/app/Services` - логика проверки
- `/app/Jobs` - фоновые задачи
- `/app/Models` - структура данных
