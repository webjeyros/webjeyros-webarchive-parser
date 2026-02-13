# Web Archive Parser - Wayback Machine Domain Hunter

Комплексная Laravel система для поиска контента в Web Archive, выявления свободных drop-доменов, проверки авторитетности и управления рабочим процессом сбора контента.

## Возможности

- 🔍 **Парсинг Web Archive** - Поиск доменов и контента по ключевым словам (по названию и текстовому содержимому)
- 🌐 **Проверка доменов** - Определение занятости через WHOIS и HTTP-проверки
- 📊 **SEO Метрики** - Бесплатные данные: Domain Authority, Backlinks, Spam Score (Majestic, Common Crawl)
- 💾 **Хранение данных** - Все метрики сохраняются в БД для анализа
- 👥 **Управление правами** - Разграничение доступа для команд
- ⚡ **Оптимизация** - Queue-система, кэширование, соблюдение лимитов API
- 📈 **Аналитика** - Отсортировка и экспорт найденных доменов
- 🧠 **Интеллектуальная фильтрация** - Отсеивание мертвых доменов

## Варианты применения

1. Поиск авторитетных доменов для покупки/переоформления
2. Анализ доменной истории для SEO
3. Определение потенциально ценных drop-доменов
4. Сбор качественного контента со старых доменов
5. Оценка домена перед инвестированием
6. Составление черного списка спам-доменов
- 📡 **Anchor Search** - Поиск по заголовкам и текстовому содержимому сохраненных страниц

## Варианты применения

1. Наполнение сайтов релевантным контентом со старых доменов
2. Поиск авторитетных доменов с исторической релевантностью
3. Экономия на наполнении сайтов (бюджетная методика)
4. Использование контента при размещении на сторонних площадках
5. Наполнение Яндекс.Дзена и других платформ
6. Поиск статей для перевода с англоязычного контента
7. Поиск доменов по актуальному контенту (заголовки, текст)

## Требования

- PHP 8.1+
- Laravel 10+
- MySQL 5.7+
- Redis (опционально, для кэширования)
- Composer

## Установка

### 1. Клонирование репозитория

```bash
git clone https://github.com/webjeyros/webjeyros-webarchive-parser.git
cd webjeyros-webarchive-parser
git checkout feature/free-seo-checker
```

### 2. Установка зависимостей

```bash
composer install
composer require whois-api-india/php-whois-parser
composer require guzzlehttp/guzzle
```

### 3. Конфигурация

```bash
cp .env.example .env
php artisan key:generate
```

Обновите `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=webarchive_parser
DB_USERNAME=root
DB_PASSWORD=secret

QUEUE_DRIVER=database

# Optional: Paid APIs (для расширенных функций)
MAJESTIC_API_KEY=your_free_key
PAGES_API_KEY=your_key
```

### 4. Миграция БД

```bash
php artisan migrate
```

### 5. Запуск queue worker

```bash
php artisan queue:work --queue=default --timeout=600 --tries=2
```

## Быстрый старт

### 1. Создание проекта

```php
php artisan tinker

$project = \App\Models\Project::create(['name' => 'My Domain Research']);
$keyword = $project->keywords()->create(['keyword' => 'best php hosting']);

// Автоматически запустится ParseWaybackJob
```

### 2. Мониторинг процесса

```bash
# Запустить queue worker
php artisan queue:work
```

### 3. Получение результатов

```php
// Доступные домены
$available = \App\Models\Domain::where('project_id', $project->id)
    ->where('status', 'available')
    ->orderByDesc('domain_authority')
    ->get();

// Занятые домены с хорошим авторитетом
$good = \App\Models\Domain::where('project_id', $project->id)
    ->where('status', 'occupied')
    ->where('domain_authority', '>=', 30)
    ->where('spam_score', '<', 10)
    ->orderByDesc('backlink_count')
    ->get();
```

## Бесплатные источники SEO данных

| Source | Limit | Данные |
|--------|-------|--------|
| **Majestic Free** | 600 req/day | CF, TF, Links, Domains |
| **Common Crawl** | Unlimited | Archive Data, Indexed Pages |
| **Whois APIs** | 500/day | WHOIS, registrar, dates |
| **DNS Lookups** | Unlimited | Nameservers, MX, A records |
| **HTTP Status** | Unlimited | Response codes, headers |
| **Google Search** | Unlimited | Indexed pages check |
```env
# Web Archive API (Wayback Machine) - Anchor Search
WAYBACK_ANCHOR_API_URL=https://web.archive.org/__wb/search/anchor

# Web Archive API (Wayback Machine) - Domain Search
WAYBACK_API_URL=https://archive.org/wayback/available

**Дополнительно:** Платные APIs (опционально) для расширенных метрик

## Архитектура

### Основные компоненты

```
Keyword (ключевое слово)
    ↓
ParseWaybackJob (поиск в Wayback Machine)
    ↓
Domain (найденный домен)
    ↓
CheckDomainAvailabilityJob (WHOIS, DNS, HTTP)
    ↓
CheckDomainSeoJob (SEO метрики)
    ↓
Database (сохранение всех данных)
```

### Jobs (фоновые задачи)

- **ParseWaybackJob** - Поиск доменов по ключевым словам
- **CheckDomainAvailabilityJob** - Проверка WHOIS и HTTP статуса
- **CheckDomainSeoJob** - Сбор SEO метрик из бесплатных источников

### Services (бизнес-логика)
1. Создание проекта
2. Добавление ключевых слов
3. Парсинг Web Archive - получение списка доменов со сниппетами по названиям и текстовому содержимому

- **WaybackService** - Работа с Wayback Machine API
- **DomainCheckerService** - Проверка доменов (WHOIS, DNS, HTTP)
- **SeoMetricsService** - Сбор SEO данных (Majestic, Common Crawl)

## Структура БД

### Таблица `domains`

```
- id, project_id, keyword_id, domain
- status (new, checking, available, occupied, dead, in_work)
- available (boolean)

# HTTP/DNS
- http_status_code, ip_address, last_http_check
- nameserver_1, nameserver_2, nameserver_3

# WHOIS
- registrar, created_date, updated_date, expiration_date

# SEO Metrics
- domain_authority, spam_score, backlink_count, referring_domains
- indexed_pages, external_links, internal_links

# Metadata
- metrics_source, metrics_checked_at, metrics_available
```

## Примеры использования

### Пример 1: Найти лучшие доступные домены
app/
├── Models/
│   ├── Project.php              # Проект сборки
│   ├── Keyword.php              # Ключевые слова
│   ├── Domain.php               # Найденные домены
│   ├── DomainMetric.php         # Метрики доменов (DA/PA/Alexa)
│   ├── Content.php              # Содержимое статей
│   ├── ContentPlan.php          # План сборки контента
│   ├── User.php                 # Пользователи
│   └── ProjectAccess.php        # Доступ к проектам
├── Services/
│   ├── WaybackService.php       # Парсинг Web Archive (domain search)
│   ├── WaybackAnchorService.php # Anchor Search - поиск по заголовкам и тексту
│   ├── DomainAvailabilityService.php  # Проверка доменов
│   ├── WhoisService.php         # WHOIS запросы
│   ├── SeoRankService.php       # API seo-rank
│   ├── UniqueCheckerService.php # Проверка уникальности
│   └── DomainMetricsService.php # Сбор метрик доменов
├── Jobs/
│   ├── ParseWaybackJob.php      # Job парсинга (использует anchor search)
│   ├── CheckDomainAvailabilityJob.php
│   ├── CheckDomainMetricsJob.php
│   └── CheckContentUniquenessJob.php
├── Http/Controllers/
│   ├── ProjectController.php
│   ├── DomainController.php
│   ├── ContentController.php
│   └── PlanController.php
└── Http/Resources/
    ├── ProjectResource.php
    ├── DomainResource.php
    └── ContentResource.php
```

## API Endpoints

### Проекты

- `POST /api/projects` - Создание проекта
- `GET /api/projects` - Список проектов
- `GET /api/projects/{id}` - Детали проекта
- `PATCH /api/projects/{id}` - Обновление проекта
- `DELETE /api/projects/{id}` - Удаление проекта

### Ключи и парсинг

- `POST /api/projects/{id}/keywords` - Добавление ключей
- `POST /api/projects/{id}/parse` - Запуск парсинга Web Archive (anchor search)

### Домены

- `GET /api/projects/{id}/domains` - Список доменов проекта
- `GET /api/projects/{id}/domains/{domainId}` - Детали домена
- `POST /api/projects/{id}/domains/{domainId}/check-metrics` - Проверка метрик
- `PATCH /api/projects/{id}/domains/{domainId}` - Обновление статуса домена

### Контент и планирование

- `GET /api/projects/{id}/content` - Список контента
- `POST /api/projects/{id}/content/{contentId}/plan` - Добавление в план
- `GET /api/projects/{id}/plan` - План сборки
- `PATCH /api/projects/{id}/plan/{contentId}/taken` - Отметить как "взято"

### Управление доступом

- `POST /api/projects/{id}/access` - Предоставить доступ
- `GET /api/projects/{id}/access` - Список доступов
- `DELETE /api/projects/{id}/access/{userId}` - Отозвать доступ

## Использование

### Пример: Создание и запуск парсинга

```php
$bestAvailable = \App\Models\Domain::where('project_id', $projectId)
    ->where('status', 'available')
    ->where('domain_authority', '>=', 20)
    ->orderByDesc('domain_authority')
    ->paginate(50);
```

### Пример 2: Найти занятые домены для покупки

```php
$candidates = \App\Models\Domain::where('project_id', $projectId)
    ->where('status', 'occupied')
    ->where('spam_score', '<', 15)
    ->where('backlink_count', '>', 10)
    ->where('expiration_date', '<', now()->addMonths(3))
    ->orderByDesc('domain_authority')
    ->get();
// Запуск парсинга через job (использует anchor search)
ParseWaybackJob::dispatch($project);
```

### Пример 3: Экспорт в CSV

```php
$domains = \App\Models\Domain::where('project_id', $projectId)->get();

$csv = "domain,da,spam,backlinks,expiration\n";
foreach ($domains as $d) {
    $csv .= "{$d->domain},{$d->domain_authority},{$d->spam_score},{$d->backlink_count},{$d->expiration_date}\n";
}

file_put_contents('domains.csv', $csv);
```

### Пример 4: Получить SEO Health Score

```php
$domain = \App\Models\Domain::find($domainId);
$healthScore = $domain->getSeoHealthScore(); // 0-100

echo "Domain: {$domain->domain} - Health: {$healthScore}/100";

if ($domain->isExpiringsoon()) {
    echo " (Expiring soon!)";
}
```

## Документация

📚 [SEO Checker Guide](./SEO_CHECKER_GUIDE.md) - Полное руководство со всеми деталями

## Лимиты и оптимизация

### Бесплатные лимиты
## Anchor Search vs Domain Search

### Параметры поиска

| Метод | API Endpoint | Поиск по | Преимущества |
|-------|-------------|----------|-------------|
| **Domain Search** | `/wayback/available` | Доменное имя | Быстро, простое использование |
| **Anchor Search** | `/__wb/search/anchor` | Заголовок/текст | Точнее по релевантности, полные данные архива |

### Когда использовать Anchor Search

- Поиск по конкретной тематике в содержимом
- Необходимость получить точные результаты по текстовому содержимому
- Сбор статистики архивирования (первое/последнее сохранение, количество снимков)
- Поиск по многословным ключевым фразам

## Производительность и оптимизация

- **Majestic**: 600 запросов в день (~18-24 домена)
- **Whois**: ~500 запросов в день
- **Google**: Unlimited но с rate limiting по IP
- **Common Crawl**: Unlimited (медленно)

### Рекомендации
- Парсинг Web Archive (Anchor Search) - `high` приоритет
- Проверка доменов - `default` приоритет
- Проверка метрик - `low` приоритет (высокое количество)

✅ Запускайте проверки ночью
✅ Используйте rate limiting в сервисах
✅ Кэшируйте результаты (Redis)
✅ Обрабатывайте батчи по 10-20 доменов
✅ Используйте database queue для надежности

## Статусы доменов
- Результаты WHOIS кэшируются на 7 дней
- Метрики доменов кэшируются на 30 дней
- Результаты anchor search кэшируются на 1 день (24 часа)

| Status | Значение |
|--------|----------|
| **new** | Новый найденный домен |
| **checking** | Идет проверка |
| **available** | Домен свободен |
| **occupied** | Домен занят |
| **dead** | Домен не отвечает |
| **in_work** | В процессе обработки |

## Производительность

- **ParseWaybackJob**: ~2-5 сек на 1 ключевое слово
- **CheckDomainAvailabilityJob**: ~0.5-1 сек на 1 домен
- **CheckDomainSeoJob**: ~2-3 сек на 1 домен (зависит от API)

**Итого**: ~3-4 сек на полную проверку 1 домена

## Roadmap

- [ ] Веб-интерфейс для управления проектами
- [ ] Экспорт в различные форматы (XLSX, JSON)
- [ ] Telegram уведомления
- [ ] Интеграция с Google Search Console
- [ ] Расширенная аналитика
- [ ] Сравнение доменов
- [ ] История мониторинга
- seo-rank API: 1000 проверок за $0.04
- Wayback API: соблюдение лимитов автоматическое
- Батчинг запросов (максимум 100 за раз)
- Retry механика с exponential backoff

## Документация

Полная документация по Wayback Anchor Search доступна в [`docs/WAYBACK_ANCHOR_SEARCH.md`](docs/WAYBACK_ANCHOR_SEARCH.md)

## Лицензия

MIT License

## Автор

[Webjeyros](https://github.com/webjeyros)

## Поддержка

Для вопросов и багов используйте [Issues](https://github.com/webjeyros/webjeyros-webarchive-parser/issues)
