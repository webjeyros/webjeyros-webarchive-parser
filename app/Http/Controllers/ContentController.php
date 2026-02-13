<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Content;
use App\Http\Resources\ContentResource;
use App\Jobs\CheckContentUniquenessJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $query = $project->contents()
            ->with('domain')
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_unique')) {
            $query->where('is_unique', (bool)$request->is_unique);
        }

        $contents = $query->paginate(50);
        return response()->json(ContentResource::collection($contents));
    }

    public function show(Project $project, Content $content): JsonResponse
    {
        $this->authorize('view', $project);
        $content->load('domain', 'plans');
        return response()->json(new ContentResource($content));
    }

    public function checkUniqueness(Project $project, Content $content): JsonResponse
    {
        $this->authorize('update', $project);
        CheckContentUniquenessJob::dispatch($content);
        return response()->json(['message' => 'Uniqueness check queued']);
    }
}
