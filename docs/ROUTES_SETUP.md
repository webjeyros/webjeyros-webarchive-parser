# SEO Metrics Routes Setup

Добавьте эти маршруты в ваш `routes/api.php` файл:

```php
<?php

use App\Http\Controllers\SeoMetricsController;
use Illuminate\Support\Facades\Route;

// SEO Metrics API endpoints
Route::prefix('seo-metrics')->group(function () {
    // Get metrics for single domain
    Route::get('/domains/{domain}', [SeoMetricsController::class, 'show'])
        ->name('seo-metrics.show');

    // Check/refresh metrics for domain
    Route::post('/domains/{domain}/check', [SeoMetricsController::class, 'check'])
        ->name('seo-metrics.check');

    // Batch check metrics
    Route::post('/check-batch', [SeoMetricsController::class, 'checkBatch'])
        ->name('seo-metrics.check-batch');

    // Get all metrics for project
    Route::get('/projects/{projectId}/metrics', [SeoMetricsController::class, 'projectMetrics'])
        ->name('seo-metrics.project-metrics');

    // Filter domains by metrics
    Route::get('/filter', [SeoMetricsController::class, 'filterByMetrics'])
        ->name('seo-metrics.filter');

    // Export metrics to CSV
    Route::get('/projects/{projectId}/export', [SeoMetricsController::class, 'exportMetrics'])
        ->name('seo-metrics.export');
});
```

## API Endpoints Reference

### 1. Get Metrics for Single Domain

**Endpoint:** `GET /api/seo-metrics/domains/{id}`

**Parameters:**
- `id` - Domain ID (required)

**Example:**
```bash
curl http://localhost:8000/api/seo-metrics/domains/1
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
      "yahoo": {
        "index": null
      },
      "bing": {
        "index": 2500
      },
      "baidu": {
        "index": null,
        "links": null
      },
      "semrush": {
        "rank": 12345,
        "links": 567,
        "links_domain": 567,
        "links_host": 234,
        "traffic": 2500,
        "traffic_price": 1250.50
      },
      "alexa": {
        "rank": 50000
      },
      "webarchive": {
        "age": 5000
      },
      "social": {
        "facebook_likes": 150
      },
      "compete": {
        "rank": null
      },
      "metadata": {
        "checked_at": "2026-02-13T17:54:00Z",
        "source": "seo_quake_analytics"
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

---

### 2. Check/Refresh Metrics for Domain

**Endpoint:** `POST /api/seo-metrics/domains/{id}/check`

**Parameters:**
- `id` - Domain ID (required)

**Example:**
```bash
curl -X POST http://localhost:8000/api/seo-metrics/domains/1/check
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

---

### 3. Batch Check Metrics

**Endpoint:** `POST /api/seo-metrics/check-batch`

**Parameters (JSON):**
```json
{
  "domain_ids": [1, 2, 3, 4, 5]
}
```

**Example:**
```bash
curl -X POST http://localhost:8000/api/seo-metrics/check-batch \
  -H "Content-Type: application/json" \
  -d '{"domain_ids": [1, 2, 3, 4, 5]}'
```

**Response:**
```json
{
  "success": true,
  "message": "Queued SEO metrics check for 5 domains",
  "data": {
    "queued_count": 5,
    "domains": [
      "example.com",
      "example2.com",
      "example3.com",
      "example4.com",
      "example5.com"
    ]
  }
}
```

---

### 4. Get All Metrics for Project

**Endpoint:** `GET /api/seo-metrics/projects/{projectId}/metrics`

**Parameters:**
- `projectId` - Project ID (required)
- `per_page` - Results per page (optional, default: 50, max: 100)

**Example:**
```bash
curl "http://localhost:8000/api/seo-metrics/projects/1/metrics?per_page=50"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "project_id": 1,
    "total": 250,
    "per_page": 50,
    "current_page": 1,
    "last_page": 5,
    "domains": [
      {
        "id": 1,
        "domain": "example.com",
        "status": "available",
        "metrics": {
          "google_index": 5000,
          "yandex_index": 3200,
          "alexa_rank": 50000
        },
        "last_checked": "2026-02-13T17:54:00Z"
      }
    ]
  }
}
```

---

### 5. Filter Domains by Metrics

**Endpoint:** `GET /api/seo-metrics/filter`

**Query Parameters:**
- `project_id` - Project ID (required)
- `min_yandex_index` - Minimum Yandex index (optional)
- `min_yandex_backlinks` - Minimum Yandex backlinks (optional)
- `min_yandex_tic` - Minimum Yandex TIC (optional)
- `min_google_index` - Minimum Google index (optional)
- `min_alexa_rank` - Minimum Alexa rank value (optional)
- `max_alexa_rank` - Maximum Alexa rank value (optional)
- `min_webarchive_age` - Minimum Web Archive age in days (optional)
- `per_page` - Results per page (optional, default: 50, max: 100)

**Example:**
```bash
curl "http://localhost:8000/api/seo-metrics/filter?project_id=1&min_yandex_index=1000&min_yandex_backlinks=50&min_yandex_tic=100&min_webarchive_age=365&per_page=25"
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 42,
    "per_page": 25,
    "current_page": 1,
    "domains": [
      {
        "id": 1,
        "domain": "example.com",
        "status": "available",
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
          "alexa": {
            "rank": 50000
          },
          "webarchive": {
            "age": 5000
          }
        }
      }
    ]
  }
}
```

---

### 6. Export Metrics to CSV

**Endpoint:** `GET /api/seo-metrics/projects/{projectId}/export`

**Parameters:**
- `projectId` - Project ID (required)

**Example:**
```bash
curl http://localhost:8000/api/seo-metrics/projects/1/export > metrics-export.csv
```

**CSV Format:**
```
Domain,Status,Google Index,Google Backlinks,Yandex Index,Yandex Backlinks,Yandex TIC,Yahoo Index,Bing Index,Baidu Index,SEMrush Rank,SEMrush Links,SEMrush Traffic,Alexa Rank,Web Archive Age,Facebook Likes,Last Checked
example.com,available,5000,150,3200,89,450,,2500,,12345,567,2500,50000,5000,150,2026-02-13 17:54:00
example2.com,available,3000,100,2100,45,300,,1800,,8234,345,1500,75000,3000,75,2026-02-13 17:55:00
```

---

## Error Handling

Все endpoints возвращают ошибки в стандартном формате:

```json
{
  "success": false,
  "message": "Error description here"
}
```

**HTTP Status Codes:**
- `200` - Success
- `404` - Resource not found
- `422` - Validation error
- `500` - Server error

**Example Error Response:**
```json
{
  "success": false,
  "message": "Domain not found"
}
```

---

## Rate Limiting

Для предотвращения перегрузки можно добавить rate limiting middleware:

```php
Route::middleware('throttle:100,1')->prefix('seo-metrics')->group(function () {
    // API endpoints
});
```

Это ограничит 100 запросов в минуту.

---

## Authentication

Для защиты API добавьте аутентификацию:

```php
Route::middleware('auth:sanctum')->prefix('seo-metrics')->group(function () {
    // Protected endpoints
});
```

Или используйте API токены:

```php
Route::middleware('api')->prefix('seo-metrics')->group(function () {
    // API endpoints
});
```

---

## Testing API

### Using Postman

1. Импортируйте эту коллекцию в Postman
2. Установите `{{base_url}}` в переменную окружения
3. Используйте endpoints по примерам выше

### Using curl

```bash
# Get metrics
curl http://localhost:8000/api/seo-metrics/domains/1

# Check metrics
curl -X POST http://localhost:8000/api/seo-metrics/domains/1/check

# Batch check
curl -X POST http://localhost:8000/api/seo-metrics/check-batch \
  -H "Content-Type: application/json" \
  -d '{"domain_ids": [1, 2, 3]}'

# Filter
curl "http://localhost:8000/api/seo-metrics/filter?project_id=1&min_yandex_index=1000"

# Export
curl http://localhost:8000/api/seo-metrics/projects/1/export -o metrics.csv
```

### Using PHP

```php
// Get metrics
$response = Http::get('http://localhost:8000/api/seo-metrics/domains/1');
echo $response->json();

// Check metrics
Http::post('http://localhost:8000/api/seo-metrics/domains/1/check');

// Batch check
Http::post('http://localhost:8000/api/seo-metrics/check-batch', [
    'domain_ids' => [1, 2, 3]
]);

// Filter
$response = Http::get('http://localhost:8000/api/seo-metrics/filter', [
    'project_id' => 1,
    'min_yandex_index' => 1000
]);
```
