# 🚀 SEO Analytics - Quick Start Guide

## Step 1: Update Code & Database

```bash
# Pull latest code
git pull origin main

# Run migration
php artisan migrate

# Clear cache
php artisan cache:clear
```

## Step 2: Add API Routes

Открыте `routes/api.php` и добавьте:

```php
use App\Http\Controllers\SeoMetricsController;
use Illuminate\Support\Facades\Route;

Route::prefix('seo-metrics')->group(function () {
    Route::get('/domains/{domain}', [SeoMetricsController::class, 'show']);
    Route::post('/domains/{domain}/check', [SeoMetricsController::class, 'check']);
    Route::post('/check-batch', [SeoMetricsController::class, 'checkBatch']);
    Route::get('/projects/{projectId}/metrics', [SeoMetricsController::class, 'projectMetrics']);
    Route::get('/filter', [SeoMetricsController::class, 'filterByMetrics']);
    Route::get('/projects/{projectId}/export', [SeoMetricsController::class, 'exportMetrics']);
});
```

## Step 3: Start Queue Worker

```bash
php artisan queue:work --queue=default --tries=3
```

Чтобы запустить в фоне (production), используйте Supervisor:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --queue=default --tries=3
autostart=true
autorestart=true
numprocs=2
direct_child=false
```

## Step 4: Test API

### Check metrics for single domain

```bash
# Check domain metrics (async)
curl -X POST http://localhost:8000/api/seo-metrics/domains/1/check

# Get domain metrics
curl http://localhost:8000/api/seo-metrics/domains/1
```

### Check multiple domains

```bash
curl -X POST http://localhost:8000/api/seo-metrics/check-batch \
  -H "Content-Type: application/json" \
  -d '{"domain_ids": [1, 2, 3, 4, 5]}'
```

### Filter domains

```bash
# Domains with Yandex index >= 1000 and TIC >= 100
curl "http://localhost:8000/api/seo-metrics/filter?project_id=1&min_yandex_index=1000&min_yandex_tic=100"
```

### Export to CSV

```bash
curl http://localhost:8000/api/seo-metrics/projects/1/export > domains.csv
```

## Step 5: Use in Code

### Check metrics for domain

```php
use App\Models\Domain;
use App\Jobs\CheckSeoMetricsJob;

$domain = Domain::find(1);
CheckSeoMetricsJob::dispatch($domain, force: true);
```

### Get metrics from database

```php
use App\Models\Domain;

$domain = Domain::find(1);
$metrics = $domain->metric;

echo "Yandex Index: " . $metrics->yandex_index;
echo "Yandex Backlinks: " . $metrics->yandex_backlinks;
echo "Yandex TIC: " . $metrics->yandex_tic;
echo "Alexa Rank: " . $metrics->alexa_rank;
echo "Web Archive Age: " . $metrics->webarchive_age . " days";
```

### Filter domains by metrics

```php
use App\Models\Domain;

// Find premium domains
$premium = Domain::where('project_id', 1)
    ->with('metric')
    ->whereHas('metric', function($q) {
        $q->where('yandex_index', '>=', 5000)
          ->where('yandex_backlinks', '>=', 100)
          ->where('yandex_tic', '>=', 500);
    })
    ->get();

foreach ($premium as $domain) {
    echo $domain->domain . " - TIC: " . $domain->metric->yandex_tic . PHP_EOL;
}
```

## Step 6: Batch Check Existing Domains

```php
// In tinker or artisan command
use App\Models\Domain;
use App\Jobs\CheckSeoMetricsJob;

// Check all domains
Domain::all()->each(function($domain) {
    CheckSeoMetricsJob::dispatch($domain)
        ->delay(now()->addSeconds(rand(5, 60)));
});

// Check project domains
Domain::where('project_id', 1)
    ->each(fn($d) => CheckSeoMetricsJob::dispatch($d));
```

## Available Metrics

### Yandex Metrics 🇷🇺
- **yandex_index** - Пагиндексированных страниц
- **yandex_backlinks** - Найденных ссылок
- **yandex_tic** - Citation Index (Цитирование)

### Google Metrics 📄
- **google_index** - Пагиндексированные страниц
- **google_backlinks** - Обнаруженные ссылки
- **google_cache_date** - Дата кеш

### Other Metrics
- **alexa_rank** - Мировой рейтинг
- **webarchive_age** - Дней в Wayback Machine
- **semrush_rank** - Позиция в поиске
- **bing_index** - Индексируются в Bing
- **baidu_index** - Индексируются в Baidu
- **facebook_likes** - Лайки Facebook

## Common Tasks

### Export domains with metrics

```bash
curl http://localhost:8000/api/seo-metrics/projects/1/export > export.csv

# Or via PHP
GET /api/seo-metrics/projects/1/export > metrics.csv
```

### Find high-quality domains

```bash
# API
curl "http://localhost:8000/api/seo-metrics/filter?project_id=1&min_yandex_index=10000&min_yandex_tic=500&min_webarchive_age=1000"

# PHP
$premium = Domain::where('project_id', 1)
    ->with('metric')
    ->whereHas('metric', fn($q) => 
        $q->where('yandex_index', '>=', 10000)
          ->where('yandex_tic', '>=', 500)
          ->where('webarchive_age', '>=', 1000)
    )
    ->orderBy('yandex_tic', 'desc')
    ->limit(100)
    ->get();
```

### Monitor checking progress

```bash
# Check failed jobs
php artisan queue:failed

# Retry failed
php artisan queue:retry all

# Monitor worker
watch -n 1 'php artisan queue:work --dry-run'
```

### Clear metrics cache

```php
use App\Services\SeoQuakeAnalyticsService;

$service = app(SeoQuakeAnalyticsService::class);
$service->clearCache('example.com');

// Or clear all
Cache::flush();
```

## Troubleshooting

### Queue worker shows no jobs

```bash
# Check if jobs are queued
php artisan tinker
>>> Queue::size()

# If 0, dispatch some jobs
>>> Domain::first()->get()
>>> CheckSeoMetricsJob::dispatch($domain);
```

### No metrics in database

```bash
# Check job failures
php artisan queue:failed

# Check logs
tail -f storage/logs/laravel.log | grep -i "metrics"

# Manual check
php artisan tinker
>>> use App\Services\SeoQuakeAnalyticsService;
>>> $s = app(SeoQuakeAnalyticsService::class);
>>> $metrics = $s->fetchAllMetrics('example.com');
>>> dd($metrics);
```

### Slow API responses

```bash
# Check queue status
php artisan queue:work --verbose

# Monitor database performance
echo 'SELECT COUNT(*) FROM domain_metrics WHERE seo_metrics_checked_at IS NOT NULL;' | mysql -u root -p database_name
```

## Performance Tips

1. **Run multiple queue workers**
   ```bash
   php artisan queue:work &
   php artisan queue:work &
   php artisan queue:work &
   ```

2. **Use batch processing for large sets**
   ```php
   Domain::where('project_id', 1)
       ->chunk(50, function($domains) {
           foreach ($domains as $d) {
               CheckSeoMetricsJob::dispatch($d)->delay(rand(5, 60));
           }
       });
   ```

3. **Monitor cache hit rate**
   ```bash
   redis-cli info stats | grep -i hits
   ```

## Next Steps

- 📑 Read full documentation: `docs/SEO_QUAKE_METRICS.md`
- 💬 See examples: `docs/SEO_METRICS_EXAMPLES.md`
- 🌐 API reference: `docs/ROUTES_SETUP.md`
- 🔗 Set up Supervisor for production queue worker
- 📈 Create custom filtering and scoring logic
- 🚀 Automate metric checking with Laravel Scheduler

## Questions?

Check the documentation files in `docs/` folder or check `storage/logs/laravel.log` for errors.
