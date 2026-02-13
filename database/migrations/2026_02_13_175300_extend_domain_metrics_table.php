<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('domain_metrics', function (Blueprint $table) {
            // Google metrics
            $table->unsignedInteger('google_index')->nullable()->after('semrush_rank');
            $table->unsignedInteger('google_backlinks')->nullable()->after('google_index');
            $table->string('google_cache_date')->nullable()->after('google_backlinks');

            // Yandex metrics
            $table->unsignedInteger('yandex_index')->nullable()->after('google_cache_date');
            $table->unsignedInteger('yandex_backlinks')->nullable()->after('yandex_index');
            $table->unsignedInteger('yandex_tic')->nullable()->after('yandex_backlinks');

            // Yahoo metrics
            $table->unsignedInteger('yahoo_index')->nullable()->after('yandex_tic');

            // Bing metrics
            $table->unsignedInteger('bing_index')->nullable()->after('yahoo_index');

            // Baidu metrics
            $table->unsignedInteger('baidu_index')->nullable()->after('bing_index');
            $table->unsignedInteger('baidu_links')->nullable()->after('baidu_index');

            // SEMrush metrics (extended)
            $table->unsignedInteger('semrush_links')->nullable()->change(); // nullable
            $table->unsignedInteger('semrush_links_domain')->nullable()->after('semrush_links');
            $table->unsignedInteger('semrush_links_host')->nullable()->after('semrush_links_domain');
            $table->decimal('semrush_traffic_price', 10, 2)->nullable()->after('semrush_links_host');

            // Alexa metrics
            $table->unsignedInteger('alexa_rank')->nullable()->change(); // Make nullable for consistency

            // Web Archive metrics
            $table->unsignedInteger('webarchive_age')->nullable()->after('semrush_traffic_price');

            // Social metrics
            $table->unsignedInteger('facebook_likes')->nullable()->after('webarchive_age');

            // Compete metrics
            $table->unsignedInteger('compete_rank')->nullable()->after('facebook_likes');

            // Metadata
            $table->timestamp('seo_metrics_checked_at')->nullable()->after('compete_rank');
            $table->string('seo_metrics_source')->nullable()->after('seo_metrics_checked_at'); // Track which service provided data
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_metrics', function (Blueprint $table) {
            // Drop all new columns
            $table->dropColumn([
                'google_index',
                'google_backlinks',
                'google_cache_date',
                'yandex_index',
                'yandex_backlinks',
                'yandex_tic',
                'yahoo_index',
                'bing_index',
                'baidu_index',
                'baidu_links',
                'semrush_links_domain',
                'semrush_links_host',
                'semrush_traffic_price',
                'webarchive_age',
                'facebook_likes',
                'compete_rank',
                'seo_metrics_checked_at',
                'seo_metrics_source',
            ]);
        });
    }
};
