<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WaybackService;
use App\Services\DomainAvailabilityService;
use App\Services\WhoisService;
use App\Services\SeoRankService;
use App\Services\DomainMetricsService;
use App\Services\UniqueCheckerService;
use App\Policies\ProjectPolicy;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Services
        $this->app->singleton(WaybackService::class);
        $this->app->singleton(DomainAvailabilityService::class);
        $this->app->singleton(WhoisService::class);
        $this->app->singleton(SeoRankService::class);
        $this->app->singleton(UniqueCheckerService::class);
        $this->app->singleton(DomainMetricsService::class, function ($app) {
            return new DomainMetricsService(
                $app->make(SeoRankService::class)
            );
        });
    }

    public function boot(): void
    {
        // Policies
        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
