<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainMetric extends Model
{
    protected $fillable = [
        'domain_id',
        // Original metrics
        'da',
        'pa',
        'alexa_rank',
        'semrush_rank',
        'backlinks_count',
        'checked_at',
        // Google metrics
        'google_index',
        'google_backlinks',
        'google_cache_date',
        // Yandex metrics
        'yandex_index',
        'yandex_backlinks',
        'yandex_tic',
        // Yahoo metrics
        'yahoo_index',
        // Bing metrics
        'bing_index',
        // Baidu metrics
        'baidu_index',
        'baidu_links',
        // Extended SEMrush metrics
        'semrush_links',
        'semrush_links_domain',
        'semrush_links_host',
        'semrush_traffic_price',
        // Web Archive metrics
        'webarchive_age',
        // Social metrics
        'facebook_likes',
        // Compete metrics
        'compete_rank',
        // Metadata
        'seo_metrics_checked_at',
        'seo_metrics_source',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'seo_metrics_checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'google_index' => 'integer',
        'google_backlinks' => 'integer',
        'yandex_index' => 'integer',
        'yandex_backlinks' => 'integer',
        'yandex_tic' => 'integer',
        'yahoo_index' => 'integer',
        'bing_index' => 'integer',
        'baidu_index' => 'integer',
        'baidu_links' => 'integer',
        'semrush_links' => 'integer',
        'semrush_links_domain' => 'integer',
        'semrush_links_host' => 'integer',
        'semrush_traffic_price' => 'float',
        'webarchive_age' => 'integer',
        'facebook_likes' => 'integer',
        'compete_rank' => 'integer',
        'alexa_rank' => 'integer',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get all SEO metrics as array
     */
    public function toSeoArray(): array
    {
        return [
            'google' => [
                'index' => $this->google_index,
                'backlinks' => $this->google_backlinks,
                'cache_date' => $this->google_cache_date,
            ],
            'yandex' => [
                'index' => $this->yandex_index,
                'backlinks' => $this->yandex_backlinks,
                'tic' => $this->yandex_tic,
            ],
            'yahoo' => [
                'index' => $this->yahoo_index,
            ],
            'bing' => [
                'index' => $this->bing_index,
            ],
            'baidu' => [
                'index' => $this->baidu_index,
                'links' => $this->baidu_links,
            ],
            'semrush' => [
                'rank' => $this->semrush_rank,
                'links' => $this->semrush_links,
                'links_domain' => $this->semrush_links_domain,
                'links_host' => $this->semrush_links_host,
                'traffic_price' => $this->semrush_traffic_price,
            ],
            'alexa' => [
                'rank' => $this->alexa_rank,
            ],
            'webarchive' => [
                'age' => $this->webarchive_age,
            ],
            'social' => [
                'facebook_likes' => $this->facebook_likes,
            ],
            'compete' => [
                'rank' => $this->compete_rank,
            ],
            'metadata' => [
                'checked_at' => $this->seo_metrics_checked_at,
                'source' => $this->seo_metrics_source,
            ],
        ];
    }

    /**
     * Get available metrics (non-null values)
     */
    public function getAvailableMetrics(): array
    {
        $metrics = [];
        $seoArray = $this->toSeoArray();

        foreach ($seoArray as $service => $serviceMetrics) {
            if ($service === 'metadata') {
                continue;
            }
            foreach ($serviceMetrics as $key => $value) {
                if ($value !== null) {
                    $metrics["{$service}_{$key}"] = $value;
                }
            }
        }

        return $metrics;
    }
}
