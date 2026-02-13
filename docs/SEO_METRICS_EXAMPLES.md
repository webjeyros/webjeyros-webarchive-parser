# SEO Metrics Usage Examples

## Installation

1. **Run migration to add new columns:**
```bash
php artisan migrate
```

2. **Clear cache:**
```bash
php artisan cache:clear
```

## Basic Usage

### Example 1: Check Metrics for Single Domain

```php
use App\Models\Domain;
use App\Jobs\CheckSeoMetricsJob;

$domain = Domain::find(1);

// Dispatch async job (non-blocking)
CheckSeoMetricsJob::dispatch($domain, force: true);

// Job will check all metrics and save to database
```

### Example 2: Get Metrics from Database

```php
use App\Models\Domain;

$domain = Domain::find(1);
$metrics = $domain->metric;

if ($metrics) {
    echo "Yandex Index: " . $metrics->yandex_index;
    echo "Yandex Backlinks: " . $metrics->yandex_backlinks;
    echo "Yandex TIC: " . $metrics->yandex_tic;
    echo "Alexa Rank: " . $metrics->alexa_rank;
    echo "Web Archive Age: " . $metrics->webarchive_age . " days";
    echo "Last Checked: " . $metrics->seo_metrics_checked_at;
}
```

### Example 3: Fetch Metrics Directly

```php
use App\Services\SeoQuakeAnalyticsService;

$analyticsService = app(SeoQuakeAnalyticsService::class);

// Get metrics immediately (may be slow for multiple calls)
$metrics = $analyticsService->fetchAllMetrics('example.com');

echo "Google Index: " . $metrics['google_index'];
echo "Yandex Index: " . $metrics['yandex_index'];
echo "Alexa Rank: " . $metrics['alexa_rank'];
echo "Web Archive Age: " . $metrics['webarchive_age'];

// Filter out null values
$availableMetrics = array_filter($metrics, fn($v) => $v !== null);
echo "Found metrics: " . count($availableMetrics);
```

## Advanced Usage

### Example 4: Batch Check Multiple Domains

```php
use App\Models\Domain;
use App\Jobs\CheckSeoMetricsJob;

// Get all domains from project
$domains = Domain::where('project_id', 1)
    ->where('status', 'available')
    ->limit(100)
    ->get();

// Dispatch jobs with delay to avoid rate limiting
foreach ($domains as $domain) {
    CheckSeoMetricsJob::dispatch($domain)
        ->onQueue('default')
        ->delay(now()->addSeconds(rand(5, 60)));
}

echo "Queued " . $domains->count() . " domains for metric checking";
```

### Example 5: Filter Domains by Yandex Metrics

```php
use App\Models\Domain;

// Find domains with good Yandex metrics
$goodDomains = Domain::where('project_id', 1)
    ->with('metric')
    ->whereHas('metric', function($query) {
        $query->where('yandex_index', '>=', 1000)
              ->where('yandex_backlinks', '>=', 50)
              ->where('yandex_tic', '>=', 100);
    })
    ->get();

foreach ($goodDomains as $domain) {
    $m = $domain->metric;
    echo $domain->domain . " - ";
    echo "Index: " . $m->yandex_index . ", ";
    echo "Backlinks: " . $m->yandex_backlinks . ", ";
    echo "TIC: " . $m->yandex_tic . PHP_EOL;
}
```

### Example 6: Filter by Multiple Metrics

```php
use App\Models\Domain;

// Find old, authoritative domains
$premium = Domain::where('project_id', 1)
    ->with('metric')
    ->whereHas('metric', function($query) {
        $query->where('yandex_index', '>=', 5000)           // Large index
              ->where('yandex_backlinks', '>=', 100)         // Many backlinks
              ->where('yandex_tic', '>=', 500)               // High TIC
              ->where('webarchive_age', '>=', 365)           // Over 1 year old
              ->where('alexa_rank', '<=', 1000000);          // Good Alexa rank
    })
    ->orderBy('yandex_tic', 'desc')
    ->get();

echo "Found " . $premium->count() . " premium domains";
```

### Example 7: Export Domains with Metrics

```php
use App\Models\Domain;

$domains = Domain::where('project_id', 1)
    ->with('metric')
    ->whereHas('metric')
    ->get();

// Create CSV
$csv = implode(",", [
    'Domain',
    'Yandex Index',
    'Yandex Backlinks',
    'Yandex TIC',
    'Alexa Rank',
    'Web Archive Age',
    'Last Checked'
]) . PHP_EOL;

foreach ($domains as $domain) {
    $m = $domain->metric;
    $csv .= implode(",", [
        $domain->domain,
        $m->yandex_index ?? '',
        $m->yandex_backlinks ?? '',
        $m->yandex_tic ?? '',
        $m->alexa_rank ?? '',
        $m->webarchive_age ?? '',
        $m->seo_metrics_checked_at ?? ''
    ]) . PHP_EOL;
}

// Save to file
file_put_contents('domains-metrics.csv', $csv);
echo "Exported to domains-metrics.csv";
```

### Example 8: Compare Metrics

```php
use App\Models\Domain;

$domain1 = Domain::find(1);
$domain2 = Domain::find(2);

$m1 = $domain1->metric;
$m2 = $domain2->metric;

echo "Comparison:" . PHP_EOL;
echo $domain1->domain . " vs " . $domain2->domain . PHP_EOL;
echo PHP_EOL;

echo "Yandex Index:" . PHP_EOL;
echo "  " . $domain1->domain . ": " . ($m1->yandex_index ?? 'N/A') . PHP_EOL;
echo "  " . $domain2->domain . ": " . ($m2->yandex_index ?? 'N/A') . PHP_EOL;
echo PHP_EOL;

echo "Yandex Backlinks:" . PHP_EOL;
echo "  " . $domain1->domain . ": " . ($m1->yandex_backlinks ?? 'N/A') . PHP_EOL;
echo "  " . $domain2->domain . ": " . ($m2->yandex_backlinks ?? 'N/A') . PHP_EOL;
echo PHP_EOL;

echo "Yandex TIC:" . PHP_EOL;
echo "  " . $domain1->domain . ": " . ($m1->yandex_tic ?? 'N/A') . PHP_EOL;
echo "  " . $domain2->domain . ": " . ($m2->yandex_tic ?? 'N/A') . PHP_EOL;
echo PHP_EOL;

echo "Web Archive Age (days):" . PHP_EOL;
echo "  " . $domain1->domain . ": " . ($m1->webarchive_age ?? 'N/A') . PHP_EOL;
echo "  " . $domain2->domain . ": " . ($m2->webarchive_age ?? 'N/A') . PHP_EOL;
```

### Example 9: Get Metrics Array Format

```php
use App\Models\Domain;

$domain = Domain::find(1);
$metric = $domain->metric;

// Get structured array
$metricsArray = $metric->toSeoArray();

echo json_encode($metricsArray, JSON_PRETTY_PRINT);

// Output:
// {
//   "google": {
//     "index": 5000,
//     "backlinks": 150,
//     "cache_date": "2026-02-13"
//   },
//   "yandex": {
//     "index": 3200,
//     "backlinks": 89,
//     "tic": 450
//   },
//   "semrush": {
//     "rank": 12345,
//     "links": 567,
//     "traffic": 2500,
//     "traffic_price": 1250.50
//   },
//   "alexa": {
//     "rank": 50000
//   },
//   "webarchive": {
//     "age": 5000
//   }
// }
```

### Example 10: Get Only Available Metrics

```php
use App\Models\Domain;

$domain = Domain::find(1);
$metric = $domain->metric;

// Get only non-null metrics
$available = $metric->getAvailableMetrics();

echo "Available metrics:" . PHP_EOL;
foreach ($available as $key => $value) {
    echo "  " . $key . ": " . $value . PHP_EOL;
}

// Output (example):
// Available metrics:
//   google_index: 5000
//   yandex_index: 3200
//   yandex_backlinks: 89
//   yandex_tic: 450
//   alexa_rank: 50000
//   webarchive_age: 5000
```

## API Endpoints Examples

### Get Single Domain Metrics

```bash
curl http://localhost:8000/api/seo-metrics/domains/1
```

### Check Domain Metrics

```bash
curl -X POST http://localhost:8000/api/seo-metrics/domains/1/check
```

### Check Multiple Domains

```bash
curl -X POST http://localhost:8000/api/seo-metrics/check-batch \
  -H "Content-Type: application/json" \
  -d '{"domain_ids": [1, 2, 3, 4, 5]}'
```

### Get Project Metrics

```bash
curl http://localhost:8000/api/seo-metrics/projects/1/metrics?per_page=50
```

### Filter by Metrics

```bash
curl "http://localhost:8000/api/seo-metrics/filter?project_id=1&min_yandex_index=1000&min_yandex_backlinks=50&min_yandex_tic=100"
```

### Export Metrics

```bash
curl http://localhost:8000/api/seo-metrics/projects/1/export > metrics.csv
```

## Database Queries

### Get metrics summary

```sql
SELECT 
    COUNT(*) as total_domains,
    AVG(yandex_index) as avg_yandex_index,
    AVG(yandex_backlinks) as avg_yandex_backlinks,
    AVG(yandex_tic) as avg_yandex_tic,
    AVG(alexa_rank) as avg_alexa_rank,
    AVG(webarchive_age) as avg_webarchive_age,
    COUNT(CASE WHEN yandex_index > 0 THEN 1 END) as domains_with_yandex_index
FROM domain_metrics
WHERE seo_metrics_checked_at IS NOT NULL;
```

### Find premium domains (Yandex perspective)

```sql
SELECT 
    d.domain,
    m.yandex_index,
    m.yandex_backlinks,
    m.yandex_tic,
    m.webarchive_age,
    m.seo_metrics_checked_at
FROM domains d
JOIN domain_metrics m ON d.id = m.domain_id
WHERE m.yandex_index >= 5000
  AND m.yandex_backlinks >= 100
  AND m.yandex_tic >= 500
  AND m.webarchive_age >= 365
ORDER BY m.yandex_tic DESC;
```

## Troubleshooting

### No metrics appearing

```bash
# Check if queue worker is running
php artisan queue:work

# Check job failures
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Clear and refresh metrics

```php
use App\Models\Domain;
use App\Services\SeoQuakeAnalyticsService;

$domain = Domain::find(1);
$analyticsService = app(SeoQuakeAnalyticsService::class);

// Clear cache
$analyticsService->clearCache($domain->domain);

// Dispatch new check
CheckSeoMetricsJob::dispatch($domain, force: true);
```

### Performance optimization

```php
// Load domains with metrics in single query
$domains = Domain::with('metric')->get();

// Much faster than loading metrics separately
foreach ($domains as $domain) {
    echo $domain->metric->yandex_index;
}
```
