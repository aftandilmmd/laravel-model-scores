<?php

namespace Aftandilmmd\LaravelModelScores\Services;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Aftandilmmd\LaravelModelScores\Contracts\ScoreCalculator;
use Aftandilmmd\LaravelModelScores\Enums\AdjustmentType;
use Aftandilmmd\LaravelModelScores\Enums\ScoreEventType;
use Aftandilmmd\LaravelModelScores\Enums\TaskType;
use Aftandilmmd\LaravelModelScores\Models\QualityAdjustment;
use Aftandilmmd\LaravelModelScores\Models\QualityBadge;
use Aftandilmmd\LaravelModelScores\Models\QualityScore;
use Aftandilmmd\LaravelModelScores\Models\QualityScoreEvent;
use Aftandilmmd\LaravelModelScores\Models\QualityTask;
use Aftandilmmd\LaravelModelScores\Models\QualityTaskGroup;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QualityScoreService implements ModelScoresServiceInterface
{
    // ──────────────────────────────────────────────────────────────
    // Calculation
    // ──────────────────────────────────────────────────────────────

    public function calculateFor(Model $scoreable, ?string $type = null, string $profile = 'default'): int
    {
        $tasks = $this->getActiveTasks($type, $profile);
        $scoreColumn = config('model-scores.score_column', 'quality_score');
        $oldTotal = (int) ($scoreable->{$scoreColumn} ?? 0);
        $oldBadge = $this->getCurrentBadge($scoreable, $profile);

        $totalWeightedScore = 0;

        foreach ($tasks as $task) {
            $totalWeightedScore += $this->calculateTask($scoreable, $task);
        }

        // Add active adjustments
        $adjustmentsTotal = $this->processAdjustments($scoreable, $profile, $oldTotal);
        $newTotal = max(0, $totalWeightedScore + $adjustmentsTotal);

        // Update the scoreable model's score column
        $scoreable->{$scoreColumn} = $newTotal;
        $scoreable->saveQuietly();

        // Check badge changes
        $this->processBadgeChanges($scoreable, $oldBadge, $profile);

        // Check threshold crossings
        $this->checkThresholds($scoreable, $oldTotal, $newTotal, $profile);

        // Log recalculated event
        $this->logEvent($scoreable, $profile, ScoreEventType::Recalculated, null, null, null, $oldTotal, $newTotal);

        // Dispatch ScoresCalculated event
        $this->dispatchEvent('scores_calculated', $scoreable, $newTotal, $profile);

        return $newTotal;
    }

    public function calculateForAll(string $scoreableClass, ?string $type = null, string $profile = 'default', ?Builder $query = null): int
    {
        $query = $query ?? $scoreableClass::query();
        $count = 0;

        $query->chunk(100, function ($scoreables) use ($type, $profile, &$count) {
            foreach ($scoreables as $scoreable) {
                $this->calculateFor($scoreable, $type, $profile);
                $count++;
            }
        });

        return $count;
    }

    // ──────────────────────────────────────────────────────────────
    // Querying
    // ──────────────────────────────────────────────────────────────

    public function getBreakdown(Model $scoreable, string $profile = 'default'): Collection
    {
        $tasks = $this->getActiveTasks(null, $profile);
        $scoreModel = config('model-scores.models.score', QualityScore::class);

        $scores = $scoreModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->get()
            ->keyBy('quality_task_id');

        return $tasks->map(function (QualityTask $task) use ($scores) {
            $score = $scores->get($task->id);

            return (object) [
                'task' => $task,
                'group' => $task->group,
                'score' => $score?->score ?? 0,
                'max_score' => $task->max_points,
                'weighted_score' => $score?->weighted_score ?? 0,
                'max_weighted_score' => $task->getEffectiveMaxPoints(),
                'percentage' => $task->max_points > 0
                    ? round(($score?->score ?? 0) / $task->max_points * 100)
                    : 0,
                'metadata' => $score?->metadata ?? [],
                'calculated_at' => $score?->calculated_at,
            ];
        });
    }

    public function getChecklistItems(Model $scoreable, string $profile = 'default'): Collection
    {
        $taskModel = config('model-scores.models.task', QualityTask::class);
        $scoreModel = config('model-scores.models.score', QualityScore::class);

        $tasks = $taskModel::active()
            ->forProfile($profile)
            ->forChecklist()
            ->ordered()
            ->with('group')
            ->get();

        $scores = $scoreModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->get()
            ->keyBy('quality_task_id');

        return $tasks->map(function (QualityTask $task) use ($scores) {
            $score = $scores->get($task->id);
            $earned = $score?->score ?? 0;
            $isComplete = $earned >= $task->max_points;
            $percentage = $task->max_points > 0
                ? round($earned / $task->max_points * 100)
                : 0;

            return (object) [
                'task' => $task,
                'group' => $task->group,
                'is_complete' => $isComplete,
                'score' => $earned,
                'max_score' => $task->max_points,
                'percentage' => $percentage,
                'route' => $task->route,
                'metadata' => $score?->metadata ?? [],
            ];
        });
    }

    public function getTasks(?string $type = null, string $profile = 'default'): Collection
    {
        return $this->getActiveTasks($type, $profile);
    }

    public function getTaskGroups(string $profile = 'default'): Collection
    {
        $groupModel = config('model-scores.models.task_group', QualityTaskGroup::class);

        return $groupModel::active()
            ->ordered()
            ->with(['tasks' => fn ($q) => $q->active()->forProfile($profile)->ordered()])
            ->get();
    }

    public function getTotalScore(Model $scoreable, string $profile = 'default'): int
    {
        $scoreColumn = config('model-scores.score_column', 'quality_score');

        return (int) ($scoreable->{$scoreColumn} ?? 0);
    }

    // ──────────────────────────────────────────────────────────────
    // Badge
    // ──────────────────────────────────────────────────────────────

    public function getCurrentBadge(Model $scoreable, string $profile = 'default'): ?QualityBadge
    {
        if (! config('model-scores.features.badges', true)) {
            return null;
        }

        $scoreColumn = config('model-scores.score_column', 'quality_score');
        $score = (int) ($scoreable->{$scoreColumn} ?? 0);
        $badgeModel = config('model-scores.models.badge', QualityBadge::class);

        return $badgeModel::active()
            ->forProfile($profile)
            ->forScore($score)
            ->ordered()
            ->first();
    }

    public function getAvailableBadges(string $profile = 'default'): Collection
    {
        $badgeModel = config('model-scores.models.badge', QualityBadge::class);

        return $badgeModel::active()
            ->forProfile($profile)
            ->ordered()
            ->get();
    }

    // ──────────────────────────────────────────────────────────────
    // Event Log & History
    // ──────────────────────────────────────────────────────────────

    public function getScoreHistory(Model $scoreable, string $profile = 'default', int $days = 30): Collection
    {
        $eventModel = config('model-scores.models.score_event', QualityScoreEvent::class);

        return $eventModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->forProfile($profile)
            ->ofType(ScoreEventType::Recalculated)
            ->recent($days)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($event) => (object) [
                'date' => $event->created_at,
                'total' => $event->new_total,
                'previous_total' => $event->old_total,
                'change' => ($event->new_total ?? 0) - ($event->old_total ?? 0),
            ]);
    }

    public function getScoreTimeline(Model $scoreable, string $profile = 'default', ?string $eventType = null, int $limit = 50): Collection
    {
        $eventModel = config('model-scores.models.score_event', QualityScoreEvent::class);

        $query = $eventModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->forProfile($profile)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($eventType !== null) {
            $query->ofType($eventType);
        }

        return $query->get();
    }

    // ──────────────────────────────────────────────────────────────
    // Manual Adjustment
    // ──────────────────────────────────────────────────────────────

    public function addAdjustment(
        Model $scoreable,
        int $points,
        string $type = 'manual',
        ?string $reason = null,
        ?Carbon $expiresAt = null,
        string $profile = 'default'
    ): QualityAdjustment {
        $adjustmentModel = config('model-scores.models.adjustment', QualityAdjustment::class);

        $adjustment = $adjustmentModel::create([
            'scoreable_type' => $scoreable->getMorphClass(),
            'scoreable_id' => $scoreable->getKey(),
            'profile' => $profile,
            'points' => $points,
            'type' => $type,
            'reason' => $reason,
            'expires_at' => $expiresAt,
            'granted_by' => auth()->id(),
        ]);

        $scoreColumn = config('model-scores.score_column', 'quality_score');
        $oldTotal = (int) ($scoreable->{$scoreColumn} ?? 0);
        $newTotal = max(0, $oldTotal + $points);

        // Log adjustment event
        $this->logEvent(
            $scoreable,
            $profile,
            ScoreEventType::AdjustmentAdded,
            null,
            null,
            null,
            $oldTotal,
            $newTotal,
            [
                'adjustment_id' => $adjustment->id,
                'points' => $points,
                'type' => $type,
                'reason' => $reason,
            ]
        );

        // Update total score
        $scoreable->{$scoreColumn} = $newTotal;
        $scoreable->saveQuietly();

        // Check badge changes
        $oldBadge = $this->getCurrentBadgeForScore($oldTotal, $profile);
        $this->processBadgeChanges($scoreable, $oldBadge, $profile);

        // Dispatch event
        $this->dispatchEvent('manual_adjustment_applied', $scoreable, $adjustment, $profile);

        return $adjustment;
    }

    public function revokeAdjustment(QualityAdjustment $adjustment): void
    {
        $adjustment->update(['revoked_at' => now()]);

        $scoreable = $adjustment->scoreable;
        $profile = $adjustment->profile;
        $scoreColumn = config('model-scores.score_column', 'quality_score');
        $oldTotal = (int) ($scoreable->{$scoreColumn} ?? 0);
        $newTotal = max(0, $oldTotal - $adjustment->points);

        $this->logEvent(
            $scoreable,
            $profile,
            ScoreEventType::AdjustmentRevoked,
            null,
            null,
            null,
            $oldTotal,
            $newTotal,
            [
                'adjustment_id' => $adjustment->id,
                'points' => $adjustment->points,
                'reason' => $adjustment->reason,
            ]
        );

        $scoreable->{$scoreColumn} = $newTotal;
        $scoreable->saveQuietly();
    }

    public function getActiveAdjustments(Model $scoreable, string $profile = 'default'): Collection
    {
        $adjustmentModel = config('model-scores.models.adjustment', QualityAdjustment::class);

        return $adjustmentModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->forProfile($profile)
            ->active()
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAdjustmentsTotal(Model $scoreable, string $profile = 'default'): int
    {
        $adjustmentModel = config('model-scores.models.adjustment', QualityAdjustment::class);

        return (int) $adjustmentModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->forProfile($profile)
            ->active()
            ->sum('points');
    }

    // ──────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────

    private function getActiveTasks(?string $type, string $profile): Collection
    {
        $taskModel = config('model-scores.models.task', QualityTask::class);

        $query = $taskModel::active()->forProfile($profile)->ordered();

        if ($type !== null && $type !== 'all') {
            $query->where('type', $type);
        }

        return $query->with('group')->get();
    }

    private function calculateTask(Model $scoreable, QualityTask $task): float
    {
        $calculatorClass = $task->calculator;

        if (! class_exists($calculatorClass)) {
            return 0;
        }

        /** @var ScoreCalculator $calculator */
        $calculator = app($calculatorClass);
        $result = $calculator->calculate($scoreable, $task->max_points, $task->metadata ?? []);

        $rawScore = (int) ($result['score'] ?? 0);
        $metadata = $result['metadata'] ?? [];

        // Apply decay if applicable
        $scoreModel = config('model-scores.models.score', QualityScore::class);
        $existingScore = $scoreModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->where('quality_task_id', $task->id)
            ->first();

        if ($task->decay_days && $existingScore) {
            $rawScore = $calculator instanceof \Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator
                ? $calculator->applyDecay($rawScore, $task->decay_days, $existingScore->calculated_at)
                : $rawScore;
        }

        // Calculate weighted score
        $weight = config('model-scores.features.weights', true) ? $task->weight : 1.00;
        $weightedScore = round($rawScore * $weight, 2);

        $oldScore = $existingScore?->score ?? 0;

        // Save/update the score
        $scoreModel::updateOrCreate(
            [
                'scoreable_type' => $scoreable->getMorphClass(),
                'scoreable_id' => $scoreable->getKey(),
                'quality_task_id' => $task->id,
            ],
            [
                'score' => $rawScore,
                'max_score' => $task->max_points,
                'weighted_score' => $weightedScore,
                'metadata' => $metadata,
                'calculated_at' => now(),
            ]
        );

        // Log task score change if changed
        if ($oldScore !== $rawScore) {
            $this->logEvent(
                $scoreable,
                $task->profile,
                ScoreEventType::TaskScoreChanged,
                $task->id,
                $oldScore,
                $rawScore,
                null,
                null,
                ['task_key' => $task->key, 'metadata' => $metadata]
            );

            $this->dispatchEvent('task_score_updated', $scoreable, $task, $oldScore, $rawScore);
        }

        return $weightedScore;
    }

    private function processAdjustments(Model $scoreable, string $profile, int $currentTotal): int
    {
        if (! config('model-scores.features.adjustments', true)) {
            return 0;
        }

        $adjustmentModel = config('model-scores.models.adjustment', QualityAdjustment::class);

        // Handle newly expired adjustments
        $newlyExpired = $adjustmentModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->forProfile($profile)
            ->expired()
            ->get();

        foreach ($newlyExpired as $expired) {
            $this->logEvent(
                $scoreable,
                $profile,
                ScoreEventType::AdjustmentExpired,
                null,
                null,
                null,
                null,
                null,
                [
                    'adjustment_id' => $expired->id,
                    'points' => $expired->points,
                    'reason' => $expired->reason,
                ]
            );
        }

        // Sum active adjustments
        return (int) $adjustmentModel::where('scoreable_type', $scoreable->getMorphClass())
            ->where('scoreable_id', $scoreable->getKey())
            ->forProfile($profile)
            ->active()
            ->sum('points');
    }

    private function processBadgeChanges(Model $scoreable, ?QualityBadge $oldBadge, string $profile): void
    {
        if (! config('model-scores.features.badges', true)) {
            return;
        }

        $newBadge = $this->getCurrentBadge($scoreable, $profile);

        $oldBadgeId = $oldBadge?->id;
        $newBadgeId = $newBadge?->id;

        if ($oldBadgeId === $newBadgeId) {
            return;
        }

        $scoreColumn = config('model-scores.score_column', 'quality_score');
        $currentTotal = (int) ($scoreable->{$scoreColumn} ?? 0);

        if ($oldBadge !== null) {
            $this->logEvent(
                $scoreable,
                $profile,
                ScoreEventType::BadgeLost,
                null,
                null,
                null,
                null,
                $currentTotal,
                ['badge_key' => $oldBadge->key, 'badge_name' => $oldBadge->name]
            );

            $this->dispatchEvent('badge_lost', $scoreable, $oldBadge, $profile);
        }

        if ($newBadge !== null) {
            $this->logEvent(
                $scoreable,
                $profile,
                ScoreEventType::BadgeEarned,
                null,
                null,
                null,
                null,
                $currentTotal,
                ['badge_key' => $newBadge->key, 'badge_name' => $newBadge->name]
            );

            $this->dispatchEvent('badge_earned', $scoreable, $newBadge, $profile);
        }
    }

    private function checkThresholds(Model $scoreable, int $oldTotal, int $newTotal, string $profile): void
    {
        if (! config('model-scores.features.thresholds', true)) {
            return;
        }

        $roseAbove = config('model-scores.thresholds.score_rose_above', []);
        foreach ($roseAbove as $threshold) {
            if ($oldTotal < $threshold && $newTotal >= $threshold) {
                $this->dispatchEvent('score_threshold_crossed', $scoreable, $threshold, 'up', $profile);
            }
        }

        $droppedBelow = config('model-scores.thresholds.score_dropped_below', []);
        foreach ($droppedBelow as $threshold) {
            if ($oldTotal >= $threshold && $newTotal < $threshold) {
                $this->dispatchEvent('score_threshold_crossed', $scoreable, $threshold, 'down', $profile);
            }
        }
    }

    private function getCurrentBadgeForScore(int $score, string $profile): ?QualityBadge
    {
        if (! config('model-scores.features.badges', true)) {
            return null;
        }

        $badgeModel = config('model-scores.models.badge', QualityBadge::class);

        return $badgeModel::active()
            ->forProfile($profile)
            ->forScore($score)
            ->ordered()
            ->first();
    }

    private function logEvent(
        Model $scoreable,
        string $profile,
        ScoreEventType $eventType,
        ?int $taskId = null,
        ?int $oldScore = null,
        ?int $newScore = null,
        ?int $oldTotal = null,
        ?int $newTotal = null,
        array $metadata = []
    ): void {
        if (! config('model-scores.features.event_log', true)) {
            return;
        }

        $eventModel = config('model-scores.models.score_event', QualityScoreEvent::class);

        $eventModel::create([
            'scoreable_type' => $scoreable->getMorphClass(),
            'scoreable_id' => $scoreable->getKey(),
            'profile' => $profile,
            'event_type' => $eventType,
            'quality_task_id' => $taskId,
            'old_score' => $oldScore,
            'new_score' => $newScore,
            'old_total' => $oldTotal,
            'new_total' => $newTotal,
            'caused_by' => auth()->id(),
            'metadata' => ! empty($metadata) ? $metadata : null,
        ]);
    }

    private function dispatchEvent(string $eventKey, mixed ...$args): void
    {
        $eventClass = config("model-scores.events.{$eventKey}");

        if ($eventClass === null || ! class_exists($eventClass)) {
            return;
        }

        event(new $eventClass(...$args));
    }
}
