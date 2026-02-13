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
        Schema::table('domains', function (Blueprint $table) {
            // WHOIS Information
            if (!Schema::hasColumn('domains', 'registrar')) {
                $table->string('registrar')->nullable()->after('http_status');
            }
            if (!Schema::hasColumn('domains', 'created_date')) {
                $table->datetime('created_date')->nullable()->after('registrar');
            }
            if (!Schema::hasColumn('domains', 'updated_date')) {
                $table->datetime('updated_date')->nullable()->after('created_date');
            }
            if (!Schema::hasColumn('domains', 'expiration_date')) {
                $table->datetime('expiration_date')->nullable()->after('updated_date');
            }

            // HTTP and DNS
            if (!Schema::hasColumn('domains', 'http_status_code')) {
                $table->integer('http_status_code')->nullable()->after('available');
            }
            if (!Schema::hasColumn('domains', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('http_status_code');
            }
            if (!Schema::hasColumn('domains', 'last_http_check')) {
                $table->datetime('last_http_check')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('domains', 'nameserver_1')) {
                $table->string('nameserver_1')->nullable()->after('last_http_check');
            }
            if (!Schema::hasColumn('domains', 'nameserver_2')) {
                $table->string('nameserver_2')->nullable()->after('nameserver_1');
            }
            if (!Schema::hasColumn('domains', 'nameserver_3')) {
                $table->string('nameserver_3')->nullable()->after('nameserver_2');
            }

            // SEO Metrics (free sources)
            if (!Schema::hasColumn('domains', 'backlink_count')) {
                $table->unsignedInteger('backlink_count')->nullable()->after('nameserver_3');
            }
            if (!Schema::hasColumn('domains', 'referring_domains')) {
                $table->unsignedInteger('referring_domains')->nullable()->after('backlink_count');
            }
            if (!Schema::hasColumn('domains', 'domain_authority')) {
                $table->decimal('domain_authority', 5, 2)->nullable()->after('referring_domains');
            }
            if (!Schema::hasColumn('domains', 'spam_score')) {
                $table->decimal('spam_score', 5, 2)->nullable()->after('domain_authority');
            }
            if (!Schema::hasColumn('domains', 'indexed_pages')) {
                $table->unsignedInteger('indexed_pages')->nullable()->after('spam_score');
            }
            if (!Schema::hasColumn('domains', 'total_pages')) {
                $table->unsignedInteger('total_pages')->nullable()->after('indexed_pages');
            }
            if (!Schema::hasColumn('domains', 'external_links')) {
                $table->unsignedInteger('external_links')->nullable()->after('total_pages');
            }
            if (!Schema::hasColumn('domains', 'internal_links')) {
                $table->unsignedInteger('internal_links')->nullable()->after('external_links');
            }

            // Metadata
            if (!Schema::hasColumn('domains', 'metrics_source')) {
                $table->string('metrics_source')->nullable()->after('internal_links');
            }
            if (!Schema::hasColumn('domains', 'metrics_checked_at')) {
                $table->datetime('metrics_checked_at')->nullable()->after('metrics_source');
            }
            if (!Schema::hasColumn('domains', 'metrics_available')) {
                $table->boolean('metrics_available')->default(false)->after('metrics_checked_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'registrar',
                'created_date',
                'updated_date',
                'expiration_date',
                'http_status_code',
                'ip_address',
                'last_http_check',
                'nameserver_1',
                'nameserver_2',
                'nameserver_3',
                'backlink_count',
                'referring_domains',
                'domain_authority',
                'spam_score',
                'indexed_pages',
                'total_pages',
                'external_links',
                'internal_links',
                'metrics_source',
                'metrics_checked_at',
                'metrics_available',
            ]);
        });
    }
};
