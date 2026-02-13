<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\DomainMetric;
use Illuminate\Support\Collection;

class DomainMetricsService
{
    public function __construct(
        private SeoRankService $seoRankService,
    ) {}

    public function updateDomainMetrics(Domain $domain): DomainMetric
    {
        try {
            $metrics = $this->seoRankService->fetchMetrics($domain->domain);

            return $domain->metric()->updateOrCreate(
                ['domain_id' => $domain->id],
                [
                    'da' => $metrics['da'],
                    'pa' => $metrics['pa'],
                    'alexa_rank' => $metrics['alexa_rank'],
                    'semrush_rank' => $metrics['semrush_rank'],
                    'backlinks_count' => $metrics['backlinks_count'],
                    'checked_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            // Log the error and return empty metric
            logger()->error("Failed to fetch metrics for {$domain->domain}: " . $e->getMessage());
            
            return $domain->metric()->updateOrCreate(
                ['domain_id' => $domain->id],
                ['checked_at' => now()]
            );
        }
    }

    public function updateBatchMetrics(Collection $domains): Collection
    {
        $results = collect();

        foreach ($domains as $domain) {
            $results->push($this->updateDomainMetrics($domain));
        }

        return $results;
    }

    public function getDomainsByMetrics(int $projectId, array $filters): Collection
    {
        $query = Domain::with('metric')
            ->where('project_id', $projectId)
            ->where('status', 'available')
            ->whereHas('metric');

        if (isset($filters['min_da'])) {
            $query->whereHas('metric', fn($q) => $q->where('da', '>=', $filters['min_da']));
        }

        if (isset($filters['min_pa'])) {
            $query->whereHas('metric', fn($q) => $q->where('pa', '>=', $filters['min_pa']));
        }

        if (isset($filters['max_alexa'])) {
            $query->whereHas('metric', fn($q) => $q->where('alexa_rank', '<=', $filters['max_alexa']));
        }

        if (isset($filters['min_backlinks'])) {
            $query->whereHas('metric', fn($q) => $q->where('backlinks_count', '>=', $filters['min_backlinks']));
        }

        return $query->get();
    }
}
