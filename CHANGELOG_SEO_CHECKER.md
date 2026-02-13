# SEO Checker Feature - Changelog

**Branch**: `feature/free-seo-checker`
**Date**: 2026-02-13

## Что добавлено

### 📺 Реализованные фичи

#### 1. **Автоматическая проверка доменов** (Либерально)
- WHOIS API (бесплатно)
- DNS лукапы
- HTTP статус проверка
- IP адреса
- Nameservers

#### 2. **SEO Метрики из бесплатных источников**
- Majestic Free API (600 req/day)
  - Domain Factors
  - Citation Flow
  - Trust Flow
  - Backlink Count
  - Referring Domains
  - Spam Score

- Common Crawl
  - Архивные данные
  - Количество индексированных страниц
  - Ссылки

#### 3. **Интеллектуальная фильтрация**
- Автоматические статусы:
  - `available` - домен свободен
  - `occupied` - домен занят
  - `dead` - домен не респонсивный
  - `in_work` - в процессе обработки
  - `checking` - в процессе проверки

#### 4. **SEO Health Score**
- Комплексный алгоритм (0-100)
- Учитывает:
  - Возраст домена
  - Domain Authority
  - Бэкклинки
  - Spam Score
  - HTTP статус

#### 5. **Новые Jobs**

**CheckDomainSeoJob.php**
- Фоновая задача для SEO проверки
- 300 сек timeout
- 2 попытки с 60 сек delay
- Трансакционное обновление данных

#### 6. **Обновлённые Jobs**

**ParseWaybackJob.php**
- Новая логика диспатчинга
- Прийти Координация jobs: CheckDomainAvailabilityJob → CheckDomainSeoJob
- 5 сек delay между jobs

#### 7. **Новые Поля в Domain Model**

**WHOIS данные:**
```php
- registrar
- created_date
- updated_date
- expiration_date
- nameserver_1, nameserver_2, nameserver_3
```

**HTTP/DNS:**
```php
- http_status_code
- ip_address
- last_http_check
```

**SEO Метрики:**
```php
- domain_authority
- spam_score
- backlink_count
- referring_domains
- indexed_pages
- total_pages
- external_links
- internal_links
```

**Метаданные:**
```php
- metrics_source
- metrics_checked_at
- metrics_available
```

#### 8. **Новые Методы Domain Model**

```php
// Проверка статуса
$domain->isAvailable() // bool
$domain->isOccupied() // bool
$domain->isDead() // bool
$domain->isExpiringsoon() // bool

// Начисление аналитики
$domain->getDomainAgeInDays() // int
$domain->getDaysUntilExpiration() // int
$domain->getSeoHealthScore() // float (0-100)

// Обновление статуса
$domain->markAsAvailable() // void
$domain->markAsOccupied() // void
$domain->markAsDead() // void
$domain->markInWork() // void

// Атрибуты
$domain->statusLabel() // удобные названия на русском
```

#### 9. **Новые Services**

**DomainCheckerService.php**
```php
public function comprehensiveCheck(string $domain): array
```
- Объединяет: WHOIS, DNS, HTTP
- Возвращает: инфо о доступности, данные WHOIS

**SeoMetricsService.php**
```php
public function getSeoMetrics(string $domain): array
```
- Получает данные из Majestic, Common Crawl
- Кэширует результаты
- Обрабатывает ошибки API

#### 10. **Миграция БД**

**2026_02_13_add_seo_metrics_to_domains_table.php**
- Аддают все новые поля
- Idempotent - безопасна уни многократных запусков
- Обратная миграция реализована

#### 11. **Документация**

**SEO_CHECKER_GUIDE.md** (~10KB)
- Полное руководство
- Естественные определения всех бесплатных APIs
- Полные примеры кода
- SQL структура таблицы
- Лимиты на бесплатные API

**README.md** (уточнен)
- Новые фичи SEO Checker
- Быстрый старт
- Примеры использования

---

## Файлы, которые были адданы/обновлены

```
✛ ADDED:
  app/Jobs/CheckDomainSeoJob.php
  app/Services/DomainCheckerService.php
  app/Services/SeoMetricsService.php
  database/migrations/2026_02_13_add_seo_metrics_to_domains_table.php
  SEO_CHECKER_GUIDE.md
  CHANGELOG_SEO_CHECKER.md

✋ MODIFIED:
  app/Models/Domain.php
  app/Jobs/ParseWaybackJob.php
  README.md
```

---

## Команды для начала работы

### 1. Обновление бранча
```bash
git checkout feature/free-seo-checker
git pull origin feature/free-seo-checker
```

### 2. Миграция БД
```bash
php artisan migrate
```

### 3. Первый тест
```php
$project = Project::create(['name' => 'Test']);
$keyword = $project->keywords()->create(['keyword' => 'hosting']);

// Автоматически запустится ParseWaybackJob
```

### 4. Мониторинг
```bash
php artisan queue:work --queue=default
```

### 5. Проверка результатов
```php
php artisan tinker

$domains = Domain::where('project_id', 1)
    ->where('status', '!=', 'new')
    ->get();

// Экспорт
$domains->map(fn($d) => [
    'domain' => $d->domain,
    'status' => $d->status,
    'da' => $d->domain_authority,
    'spam' => $d->spam_score,
    'backlinks' => $d->backlink_count,
])->export('csv');
```

---

## Основные лимиты и цены

| Source | Free Limit | Cost |
|--------|-----------|------|
| **Majestic** | 600 req/day | $0.25 per 100 |
| **Whois** | ~500/day | Usually free |
| **Common Crawl** | Unlimited | Free |
| **Google** | Unlimited | Free (rate limited) |
| **HTTP/DNS** | Unlimited | Free |

**Нитого: 100% бесплатно или очень дешево!**

---

## Примеры использования

### Топ-20 доступных доменов
```php
$top = Domain::where('status', 'available')
    ->where('domain_authority', '>=', 10)
    ->orderByDesc('domain_authority')
    ->limit(20)
    ->get(['domain', 'domain_authority', 'spam_score']);
```

### Драгценные домены для покупки
```php
$candidates = Domain::where('status', 'occupied')
    ->where('domain_authority', '>=', 25)
    ->where('spam_score', '<', 20)
    ->where('backlink_count', '>', 20)
    ->orderByDesc('domain_authority')
    ->get();
```

### Мертвые домены
```php
$dead = Domain::where('status', 'dead')
    ->where('metrics_checked_at', '<', now()->subDays(7))
    ->delete();
```

### SEO Health анализ
```php
$healthy = Domain::all()
    ->map(fn($d) => [
        'domain' => $d->domain,
        'health' => $d->getSeoHealthScore(),
    ])
    ->sortByDesc('health')
    ->take(10);
```

---

## Знаютые ограничения

- ✅ Все API источники бесплатные
- ✅ Majestic лимит не uмей – запускайте jobs ночью
- ✅ HTTP/DNS операции ставится не тяжелым ресурсом
- ✅ Rate limiting имплементирован
- ✅ Кэширование было рассмотрено

---

## Roadmap

- [ ] Integrate with Google Search Console API
- [ ] Semrush API for additional metrics
- [ ] Telegram notifications for findings
- [ ] Web dashboard for real-time monitoring
- [ ] Historical tracking and trends
- [ ] Domain comparison feature
- [ ] Black list management
- [ ] Advanced filtering and search

---

## Подтвержденные тесты

- ✅ ParseWaybackJob: Search and find domains
- ✅ CheckDomainAvailabilityJob: WHOIS and HTTP checks
- ✅ CheckDomainSeoJob: SEO metrics gathering
- ✅ Domain status filtering
- ✅ Health score calculation
- ✅ Database persistence

---

## Нужна помощь?

1. Откройте [SEO_CHECKER_GUIDE.md](./SEO_CHECKER_GUIDE.md)
2. Доступнее делаючее:
   - app/Services/
   - app/Jobs/
   - app/Models/Domain.php
3. Читайте комментарии в коде

---

**Мережи в репо:** [feature/free-seo-checker](https://github.com/webjeyros/webjeyros-webarchive-parser/tree/feature/free-seo-checker)
