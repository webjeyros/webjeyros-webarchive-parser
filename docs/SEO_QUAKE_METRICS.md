# SEOquake Analytics Integration

## Overview

The SEOquake Analytics feature provides comprehensive SEO metrics collection for domains found in your Web Archive parser. It integrates with multiple free and low-cost services to gather domain authority metrics similar to the SEOquake browser extension.

## Supported Metrics

### Google (Google Search Console / Parsing)
- **Google Index** - Number of pages indexed by Google
- **Google Backlinks** - Number of backlinks detected
- **Google Cache Date** - Last cache date from Google

### Yandex (via Zultrice API)
- **Yandex Index** - Number of pages indexed by Yandex
- **Yandex Backlinks** - Number of backlinks from Yandex perspective
- **Yandex TIC** - Citation Index (legacy but available via Zultrice)

### Yahoo
- **Yahoo Index** - Pages indexed by Yahoo (limited)

### Bing
- **Bing Index** - Pages indexed by Bing

### Baidu
- **Baidu Index** - Pages indexed by Chinese search engine
- **Baidu Links** - Backlinks from Baidu perspective

### SEMrush (Free Toolbar API)
- **SEMrush Rank** - Domain rank
- **SEMrush Backlinks** - Number of backlinks
- **SEMrush Links (Domain)** - Domain-level backlinks
- **SEMrush Links (Host)** - Host-level backlinks
- **SEMrush Traffic** - Estimated organic traffic
- **SEMrush Traffic Price** - Estimated PPC cost for traffic

### Alexa
- **Alexa Rank** - Global rank by Alexa (via API)

### Web Archive
- **Web Archive Age** - Days since first capture in Wayback Machine

### Social Metrics
- **Facebook Likes/Shares** - Via Facebook Graph API (free tier)

### Compete
- **Compete Rank** - Site rank (limited free access)

## API Setup & Configuration

### Free/Low-Cost Services Used

All services are configured to use free or minimal-cost options:

#### Zultrice API (Yandex metrics)
```env
# No API key required for basic requests
# Free tier available at: https://zultrice.com
```

#### Alexa API
```env
# Free tier available
# Endpoint: https://data.alexa.com/data
```

#### Web Archive API
```env
# Completely free
# Endpoint: https://archive.org/wayback/available
```

#### Facebook Graph API (Optional)
```env
FACEBOOK_APP_ID=your_app_id
FACEBOOK_APP_SECRET=your_app_secret
```

#### SEMrush (Free Data)
```env
# Uses public SEMrush toolbar data (no API key required)
# For premium data, API key can be added
```

## Usage

### 1. Check Metrics for Single Domain

```php
$domain = Domain::find(1);

// Dispatch async job
CheckSeoMetricsJob::dispatch($domain, force: true);
```

### 2. Check Metrics Batch

```php
$domains = Domain::where('project_id', 1)
    ->limit(100)
    ->get();

foreach ($domains as $domain) {
    CheckSeoMetricsJob::dispatch($domain)
        ->onQueue('default');
}
```

### 3. Fetch Metrics Directly

```php
$analyticsService = app(SeoQuakeAnalyticsService::class);

$metrics = $analyticsService->fetchAllMetrics('example.com');

echo $metrics['yandex_index'];      // Yandex index pages
echo $metrics['yandex_backlinks'];  // Yandex backlinks
echo $metrics['yandex_tic'];        // Yandex TIC
echo $metrics['alexa_rank'];        // Alexa rank
echo $metrics['webarchive_age'];    // Days in Web Archive
```

## API Endpoints

### Get Metrics for Domain

```http
GET /api/seo-metrics/domains/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "domain": "example.com",
    "metrics": {
      "google": {
        "index": 5000,
        "backlinks": 150,
        "cache_date": "2026-02-13"
      },
      "yandex": {
        "index": 3200,
        "backlinks": 89,
        "tic": 450
      },
      "semrush": {
        "rank": 12345,
        "links": 567,
        "traffic": 2500,
        "traffic_price": 1250.50
      },
      "alexa": {
        "rank": 50000
      },
      "webarchive": {
        "age": 5000
      }
    },
    "available_metrics": {
      "google_index": 5000,
      "yandex_index": 3200,
      "alexa_rank": 50000
    },
    "last_checked": "2026-02-13T17:54:00Z",
    "source": "seo_quake_analytics"
  }
}
```

### Check/Refresh Metrics

```http
POST /api/seo-metrics/domains/{id}/check
```

**Response:**
```json
{
  "success": true,
  "message": "SEO metrics check has been queued",
  "data": {
    "domain": "example.com",
    "status": "queued"
  }
}
```

### Check Metrics Batch

```http
POST /api/seo-metrics/check-batch
Content-Type: application/json

{
  "domain_ids": [1, 2, 3, 4, 5]
}
```

### Get Project Metrics

```http
GET /api/seo-metrics/projects/{id}/metrics
?per_page=50
```

### Filter by Metrics

```http
GET /api/seo-metrics/filter
?project_id=1
&min_yandex_index=1000
&min_yandex_backlinks=50
&min_yandex_tic=100
&min_alexa_rank=100000
&min_webarchive_age=365
&per_page=50
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 42,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1,
    "domains": [
      {
        "id": 1,
        "domain": "example.com",
        "status": "available",
        "metrics": {
          "google_index": 5000,
          "yandex_index": 3200,
          "yandex_backlinks": 89,
          "yandex_tic": 450,
          "alexa_rank": 50000,
          "webarchive_age": 5000
        }
      }
    ]
  }
}
```

### Export Metrics to CSV

```http
GET /api/seo-metrics/projects/{id}/export
```

Returns CSV file with all domain metrics.

## Database Schema

The `domain_metrics` table includes the following fields for SEO data:

```sql
ALTER TABLE domain_metrics ADD COLUMN (
    google_index INT UNSIGNED,
    google_backlinks INT UNSIGNED,
    google_cache_date VARCHAR(255),
    
    yandex_index INT UNSIGNED,
    yandex_backlinks INT UNSIGNED,
    yandex_tic INT UNSIGNED,
    
    yahoo_index INT UNSIGNED,
    
    bing_index INT UNSIGNED,
    
    baidu_index INT UNSIGNED,
    baidu_links INT UNSIGNED,
    
    semrush_links_domain INT UNSIGNED,
    semrush_links_host INT UNSIGNED,
    semrush_traffic_price DECIMAL(10,2),
    
    webarchive_age INT UNSIGNED,
    facebook_likes INT UNSIGNED,
    compete_rank INT UNSIGNED,
    
    seo_metrics_checked_at TIMESTAMP,
    seo_metrics_source VARCHAR(255)
);
```

## Queue Configuration

Make sure your queue worker is running:

```bash
php artisan queue:work --queue=default --tries=3
```

Job Configuration:
- **Queue**: `default`
- **Retries**: 3
- **Timeout**: 300 seconds (5 minutes)

## Caching

Metrics are cached for 24 hours (86400 seconds) to avoid excessive API calls:

```php
// Clear cache for specific domain
$analyticsService->clearCache('example.com');
```

## Performance Considerations

### Rate Limiting
- **Zultrice API**: Free tier has rate limits
- **Alexa**: ~100 requests per minute
- **Web Archive**: ~120 requests per minute
- **Facebook Graph**: Check app limits

### Batch Processing

For large-scale metric collection:

```php
$domains = Domain::where('project_id', 1)->get();
$batchSize = 50;

$domains->chunk($batchSize)->each(function($chunk) {
    foreach ($chunk as $domain) {
        CheckSeoMetricsJob::dispatch($domain)
            ->onQueue('default')
            ->delay(now()->addSeconds(rand(5, 60)));
    }
});
```

This spreads the API requests over time to avoid rate limiting.

## Error Handling

The service gracefully handles API errors:

```php
try {
    $metrics = $analyticsService->fetchAllMetrics('example.com');
    // Returns array with available metrics only
    // Missing metrics return null
} catch (\Exception $e) {
    logger()->error("Error: " . $e->getMessage());
}
```

## Troubleshooting

### No metrics returned
1. Check if queue worker is running: `php artisan queue:work`
2. Check logs: `storage/logs/laravel.log`
3. Verify internet connection
4. API services might be temporarily unavailable

### Partial metrics
- Some APIs may be down
- Domain might be too new (not in all indexes)
- Check `seo_metrics_source` field to see which service provided data

### Cache issues
```php
// Force refresh
Cache::forget("yandex_index_example.com");
CheckSeoMetricsJob::dispatch($domain, force: true);
```

## Cost Analysis

| Service | Cost | Data |
|---------|------|------|
| Zultrice | Free tier | Yandex metrics |
| Alexa | Free | Global rank |
| Web Archive | Free | Archive age |
| Facebook Graph | Free (limited) | Social metrics |
| SEMrush | Free toolbar data | Backlinks, rank, traffic |
| Google | Free (GSC) | Index, backlinks |
| Bing | Free (Webmaster Tools) | Index |
| Baidu | Free (limited) | Index, links |
| Yahoo | Free (limited) | Index |

**Total Monthly Cost: $0** (using free tiers)

## Future Enhancements

- [ ] Historical data tracking
- [ ] Trend analysis
- [ ] Automated daily/weekly updates
- [ ] Custom alerts for metric changes
- [ ] Integration with paid API tiers for premium data
- [ ] Competitor analysis
- [ ] Custom scoring algorithm
