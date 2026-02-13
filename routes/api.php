<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DomainCheckController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('api')->group(function () {
    /**
     * Проверка одного домена
     * 
     * POST /api/domain/check
     * Body: { "domain": "example.com" }
     * 
     * Ответ:
     * {
     *   "domain": "example.com",
     *   "status": "active" | "dead" | "error",
     *   "http_code": 200,
     *   "seo_metrics": {
     *     "ticy": 42,
     *     "yandex_rank": 123,
     *     "backlinks_ru": 5,
     *     "backlink_count": 156,
     *     "referring_domains": 23
     *   },
     *   "whois_data": { ... }
     * }
     */
    Route::post('/domain/check', [DomainCheckController::class, 'checkDomain'])
        ->name('domain.check')
        ->middleware('throttle:30,1');

    /**
     * Пакетная проверка доменов
     * 
     * POST /api/domain/batch-check
     * Body: { "domains": ["example.com", "google.com"] }
     * 
     * Ответ:
     * {
     *   "total": 2,
     *   "active": 2,
     *   "dead": 0,
     *   "results": [ ... ],
     *   "error_details": [ ... ]
     * }
     */
    Route::post('/domain/batch-check', [DomainCheckController::class, 'batchCheck'])
        ->name('domain.batch-check')
        ->middleware('throttle:10,1');

    /**
     * Получить домены с фильтрами
     * 
     * GET /api/domains
     * Query params:
     *   - status: 'live' | 'dead'
     *   - has_metrics: 1 | 0
     *   - min_ticy: number
     *   - min_backlinks: number
     *   - per_page: 50
     * 
     * Ответ: Пагинация доменов
     */
    Route::get('/domains', [DomainCheckController::class, 'listDomains'])
        ->name('domain.list')
        ->middleware('throttle:60,1');
});

// Приветственные роуты
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
    ]);
})->name('health');
