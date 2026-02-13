<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\PlanController;

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Keywords and parsing
    Route::post('projects/{project}/keywords', [KeywordController::class, 'store']);
    Route::post('projects/{project}/parse', [KeywordController::class, 'parse']);

    // Domains
    Route::get('projects/{project}/domains', [DomainController::class, 'index']);
    Route::get('projects/{project}/domains/{domain}', [DomainController::class, 'show']);
    Route::post('projects/{project}/domains/{domain}/check-metrics', [DomainController::class, 'checkMetrics']);
    Route::patch('projects/{project}/domains/{domain}', [DomainController::class, 'update']);
    Route::get('projects/{project}/domains/export', [DomainController::class, 'export']);

    // Content
    Route::get('projects/{project}/content', [ContentController::class, 'index']);
    Route::get('projects/{project}/content/{content}', [ContentController::class, 'show']);
    Route::post('projects/{project}/content/{content}/check-uniqueness', [ContentController::class, 'checkUniqueness']);

    // Plans
    Route::get('projects/{project}/plan', [PlanController::class, 'index']);
    Route::post('projects/{project}/plan', [PlanController::class, 'store']);
    Route::patch('projects/{project}/plan/{plan}/taken', [PlanController::class, 'markTaken']);
});
