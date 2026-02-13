<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Keyword;
use App\Jobs\ParseWaybackJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KeywordController extends Controller
{
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'keywords' => 'required|array|min:1|max:100',
            'keywords.*' => 'string|max:255|distinct',
        ]);

        $keywords = collect($validated['keywords'])
            ->map(fn($keyword) => [
                'project_id' => $project->id,
                'keyword' => $keyword,
                'status' => 'pending',
            ])
            ->toArray();

        $created = Keyword::insert($keywords);

        return response()->json([
            'message' => "Created {$created} keywords",
            'count' => count($keywords),
        ], 201);
    }

    public function parse(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->keywords()
            ->where('status', 'pending')
            ->get()
            ->each(fn($keyword) => ParseWaybackJob::dispatch($project, $keyword));

        $project->update(['status' => 'parsing']);

        return response()->json([
            'message' => 'Parsing started',
            'keywords_count' => $project->keywords()->count(),
        ]);
    }
}
