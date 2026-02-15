<?php

namespace Aftandilmmd\LaravelModelScores\Http\Controllers;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Aftandilmmd\LaravelModelScores\Http\Resources\BreakdownResource;
use Aftandilmmd\LaravelModelScores\Http\Resources\ScoreResource;
use Aftandilmmd\LaravelModelScores\Http\Resources\TaskResource;
use Aftandilmmd\LaravelModelScores\Models\QualityAdjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class ModelScoresApiController extends Controller
{
    public function __construct(
        protected ModelScoresServiceInterface $service
    ) {}

    public function tasks(Request $request): AnonymousResourceCollection
    {
        $profile = $request->query('profile', 'default');
        $type = $request->query('type');

        return TaskResource::collection($this->service->getTasks($type, $profile));
    }

    public function breakdown(Request $request, string $scoreableType, int $id): AnonymousResourceCollection
    {
        $scoreable = $this->resolveScoreable($scoreableType, $id);
        $profile = $request->query('profile', 'default');

        return BreakdownResource::collection($this->service->getBreakdown($scoreable, $profile));
    }

    public function history(Request $request, string $scoreableType, int $id): AnonymousResourceCollection
    {
        $scoreable = $this->resolveScoreable($scoreableType, $id);
        $profile = $request->query('profile', 'default');
        $days = (int) $request->query('days', 30);

        return ScoreResource::collection($this->service->getScoreHistory($scoreable, $profile, $days));
    }

    public function addAdjustment(Request $request, string $scoreableType, int $id): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer',
            'type' => 'sometimes|string|in:bonus,penalty,manual',
            'reason' => 'nullable|string|max:500',
            'expires_at' => 'nullable|date|after:now',
            'profile' => 'sometimes|string|max:32',
        ]);

        $scoreable = $this->resolveScoreable($scoreableType, $id);

        $adjustment = $this->service->addAdjustment(
            $scoreable,
            $validated['points'],
            $validated['type'] ?? 'manual',
            $validated['reason'] ?? null,
            isset($validated['expires_at']) ? \Carbon\Carbon::parse($validated['expires_at']) : null,
            $validated['profile'] ?? 'default'
        );

        return response()->json([
            'data' => $adjustment,
            'message' => 'Adjustment added successfully.',
        ], 201);
    }

    public function revokeAdjustment(int $id): JsonResponse
    {
        $adjustmentModel = config('model-scores.models.adjustment', QualityAdjustment::class);
        $adjustment = $adjustmentModel::findOrFail($id);

        $this->service->revokeAdjustment($adjustment);

        return response()->json([
            'message' => 'Adjustment revoked successfully.',
        ]);
    }

    protected function resolveScoreable(string $type, int $id)
    {
        $class = str_replace('-', '\\', $type);

        if (! class_exists($class)) {
            abort(404, "Scoreable type [{$type}] not found.");
        }

        return $class::findOrFail($id);
    }
}
