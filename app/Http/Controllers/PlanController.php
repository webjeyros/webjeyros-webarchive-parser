<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Content;
use App\Models\ContentPlan;
use App\Http\Resources\ContentPlanResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $plans = $project->contentPlans()
            ->with('content.domain', 'user')
            ->where('status', 'pending')
            ->latest()
            ->paginate(50);

        return response()->json(ContentPlanResource::collection($plans));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'content_id' => 'required|exists:contents,id',
        ]);

        $content = Content::findOrFail($validated['content_id']);

        // Verify content belongs to project
        if ($content->project_id !== $project->id) {
            return response()->json(['error' => 'Invalid content'], 422);
        }

        $plan = $project->contentPlans()->create([
            'content_id' => $content->id,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json(new ContentPlanResource($plan), 201);
    }

    public function markTaken(Request $request, Project $project, ContentPlan $plan): JsonResponse
    {
        $this->authorize('update', $project);

        $plan->markAsTaken();
        return response()->json(new ContentPlanResource($plan));
    }
}
