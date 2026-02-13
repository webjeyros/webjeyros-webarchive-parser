<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\UniqueCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class CheckContentUniquenessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 2;

    public function __construct(private Content $content) {}

    public function handle(UniqueCheckerService $checker): void
    {
        try {
            $isUnique = $checker->checkUniqueness($this->content->snippet);
            $this->content->markAsChecked($isUnique);
        } catch (Exception $e) {
            logger()->error("Content uniqueness check failed for content ID {$this->content->id}: " . $e->getMessage());
            $this->release(300); // Retry after 5 minutes
        }
    }
}
