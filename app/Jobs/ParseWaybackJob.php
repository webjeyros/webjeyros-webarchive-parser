<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Keyword;
use App\Models\Domain;
use App\Services\WaybackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Exception;

class ParseWaybackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    public function __construct(
        private Project $project,
        private Keyword $keyword,
    ) {}

    public function handle(WaybackService $waybackService): void
    {
        try {
            $results = $waybackService->searchByKeyword($this->keyword->keyword);

            DB::transaction(function () use ($results) {
                foreach ($results as $result) {
                    Domain::updateOrCreate(
                        [
                            'project_id' => $this->project->id,
                            'keyword_id' => $this->keyword->id,
                            'domain' => $result['domain'],
                        ],
                        [
                            'title' => $result['title'],
                            'snippet' => $result['snippet'],
                            'archived_url' => $result['archived_url'],
                            'status' => 'new',
                        ]
                    );
                }
            });

            $this->keyword->markAsParsed();

            // Dispatch check jobs for new domains
            $newDomains = $this->project->domains()
                ->where('keyword_id', $this->keyword->id)
                ->where('status', 'new')
                ->limit(100)
                ->get();

            foreach ($newDomains as $domain) {
                CheckDomainAvailabilityJob::dispatch($domain)->onQueue('default');
            }
        } catch (Exception $e) {
            logger()->error("Wayback parsing failed for keyword {$this->keyword->keyword}: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
