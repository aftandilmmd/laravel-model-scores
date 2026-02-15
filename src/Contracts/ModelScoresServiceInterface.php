<?php

namespace Aftandilmmd\LaravelModelScores\Contracts;

use Aftandilmmd\LaravelModelScores\Models\QualityAdjustment;
use Aftandilmmd\LaravelModelScores\Models\QualityBadge;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface ModelScoresServiceInterface
{
    // Calculation
    public function calculateFor(Model $scoreable, ?string $type = null, string $profile = 'default'): int;

    public function calculateForAll(string $scoreableClass, ?string $type = null, string $profile = 'default', ?Builder $query = null): int;

    // Querying
    public function getBreakdown(Model $scoreable, string $profile = 'default'): Collection;

    public function getChecklistItems(Model $scoreable, string $profile = 'default'): Collection;

    public function getTasks(?string $type = null, string $profile = 'default'): Collection;

    public function getTaskGroups(string $profile = 'default'): Collection;

    public function getTotalScore(Model $scoreable, string $profile = 'default'): int;

    // Badge
    public function getCurrentBadge(Model $scoreable, string $profile = 'default'): ?QualityBadge;

    public function getAvailableBadges(string $profile = 'default'): Collection;

    // Event Log & History
    public function getScoreHistory(Model $scoreable, string $profile = 'default', int $days = 30): Collection;

    public function getScoreTimeline(Model $scoreable, string $profile = 'default', ?string $eventType = null, int $limit = 50): Collection;

    // Manual Adjustment
    public function addAdjustment(Model $scoreable, int $points, string $type = 'manual', ?string $reason = null, ?Carbon $expiresAt = null, string $profile = 'default'): QualityAdjustment;

    public function revokeAdjustment(QualityAdjustment $adjustment): void;

    public function getActiveAdjustments(Model $scoreable, string $profile = 'default'): Collection;

    public function getAdjustmentsTotal(Model $scoreable, string $profile = 'default'): int;
}
