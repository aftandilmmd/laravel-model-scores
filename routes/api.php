<?php

use Aftandilmmd\LaravelModelScores\Http\Controllers\ModelScoresApiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('model-scores.api.prefix', 'api/model-scores'),
    'middleware' => config('model-scores.api.middleware', ['api', 'auth:sanctum']),
], function () {
    Route::get('tasks', [ModelScoresApiController::class, 'tasks']);
    Route::get('{scoreable_type}/{id}/breakdown', [ModelScoresApiController::class, 'breakdown']);
    Route::get('{scoreable_type}/{id}/history', [ModelScoresApiController::class, 'history']);
    Route::post('{scoreable_type}/{id}/adjustments', [ModelScoresApiController::class, 'addAdjustment']);
    Route::delete('adjustments/{id}', [ModelScoresApiController::class, 'revokeAdjustment']);
});
