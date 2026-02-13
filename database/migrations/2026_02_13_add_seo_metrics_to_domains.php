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
            // Domain availability and status
            $table->string('registrar')->nullable()->comment('Domain registrar');
            $table->dateTime('created_date')->nullable()->comment('Domain creation date');
            $table->dateTime('updated_date')->nullable()->comment('Domain update date');
            $table->dateTime('expiration_date')->nullable()->comment('Domain expiration date');

            // Basic SEO metrics
            $table->integer('http_status_code')->nullable()->comment('HTTP status code');
            $table->string('ip_address')->nullable()->comment('Domain IP address');
            $table->dateTime('last_http_check')->nullable()->comment('Last HTTP check timestamp');

            // DNS info
            $table->string('nameserver_1')->nullable();
            $table->string('nameserver_2')->nullable();
            $table->string('nameserver_3')->nullable();

            // Backlink and authority metrics
            $table->integer('backlink_count')->nullable()->comment('Number of backlinks');
            $table->integer('referring_domains')->nullable()->comment('Number of referring domains');
            $table->decimal('domain_authority', 5, 2)->nullable()->comment('Domain Authority (0-100)');
            $table->decimal('spam_score', 5, 2)->nullable()->comment('Spam score (0-100)');

            // Page metrics
            $table->integer('indexed_pages')->nullable()->comment('Number of indexed pages');
            $table->integer('total_pages')->nullable()->comment('Total pages count');

            // External links
            $table->integer('external_links')->nullable()->comment('Number of external links');
            $table->integer('internal_links')->nullable()->comment('Number of internal links');

            // Metrics source and update info
            $table->string('metrics_source')->nullable()->comment('Source of metrics (whoisjson, pr-cy, etc)');
            $table->dateTime('metrics_checked_at')->nullable()->comment('When metrics were last updated');
            $table->boolean('metrics_available')->default(false)->comment('Are metrics available for this domain');
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
