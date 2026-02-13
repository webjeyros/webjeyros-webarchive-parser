<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Domain;
use App\Http\Resources\DomainResource;
use App\Jobs\CheckDomainMetricsJob;
use App\Services\DomainMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DomainController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $query = $project->domains()
            ->with('metric', 'keyword')
            ->latest();

        // Filtering
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('available')) {
            $query->where('available', (bool)$request->available);
        }

        $domains = $query->paginate(50);
        return response()->json(DomainResource::collection($domains));
    }

    public function show(Project $project, Domain $domain): JsonResponse
    {
        $this->authorize('view', $project);
        $domain->load('metric', 'keyword', 'contents');
        return response()->json(new DomainResource($domain));
    }

    public function checkMetrics(Project $project, Domain $domain): JsonResponse
    {
        $this->authorize('update', $project);
        CheckDomainMetricsJob::dispatch($domain);
        return response()->json(['message' => 'Metrics check queued']);
    }

    public function update(Request $request, Project $project, Domain $domain): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => 'in:new,available,occupied,dead,in_work',
        ]);

        $domain->update($validated);
        return response()->json(new DomainResource($domain));
    }

    public function export(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $domains = $project->domains()
            ->where('status', 'available')
            ->with('metric')
            ->get();

        $data = $domains->map(fn($domain) => [
            'domain' => $domain->domain,
            'da' => $domain->metric?->da,
            'pa' => $domain->metric?->pa,
            'alexa_rank' => $domain->metric?->alexa_rank,
            'backlinks' => $domain->metric?->backlinks_count,
        ]);

        return response()->json($data);
    }
}
