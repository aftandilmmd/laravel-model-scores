<?php

namespace Aftandilmmd\LaravelModelScores\Traits;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Aftandilmmd\LaravelModelScores\Models\ModelScore;
use Aftandilmmd\LaravelModelScores\Models\ModelScoreAdjustment;
use Aftandilmmd\LaravelModelScores\Models\ModelScoreBadge;
use Aftandilmmd\LaravelModelScores\Models\ModelScoreEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasModelScores
{
    public function scores(): MorphMany
    {
        return $this->morphMany(
            config('model-scores.models.score', ModelScore::class),
            'scoreable'
        );
    }

    public function scoreEvents(): MorphMany
    {
        return $this->morphMany(
            config('model-scores.models.score_event', ModelScoreEvent::class),
            'scoreable'
        );
    }

    public function scoreAdjustments(): MorphMany
    {
        return $this->morphMany(
            config('model-scores.models.adjustment', ModelScoreAdjustment::class),
            'scoreable'
        );
    }

    public function calculateScore(?string $type = null, string $profile = 'default'): int
    {
        return app(ModelScoresServiceInterface::class)->calculateFor($this, $type, $profile);
    }

    public function scoreBreakdown(string $profile = 'default'): Collection
    {
        return app(ModelScoresServiceInterface::class)->getBreakdown($this, $profile);
    }

    public function scoreChecklist(string $profile = 'default'): Collection
    {
        return app(ModelScoresServiceInterface::class)->getChecklistItems($this, $profile);
    }

    public function scoreBadge(string $profile = 'default'): ?ModelScoreBadge
    {
        return app(ModelScoresServiceInterface::class)->getCurrentBadge($this, $profile);
    }

    public function scoreHistory(string $profile = 'default', int $days = 30): Collection
    {
        return app(ModelScoresServiceInterface::class)->getScoreHistory($this, $profile, $days);
    }

    public function addScoreBonus(int $points, ?string $reason = null, ?Carbon $expiresAt = null, string $profile = 'default'): ModelScoreAdjustment
    {
        return app(ModelScoresServiceInterface::class)->addAdjustment(
            $this,
            abs($points),
            'bonus',
            $reason,
            $expiresAt,
            $profile
        );
    }

    public function addScorePenalty(int $points, ?string $reason = null, ?Carbon $expiresAt = null, string $profile = 'default'): ModelScoreAdjustment
    {
        return app(ModelScoresServiceInterface::class)->addAdjustment(
            $this,
            -abs($points),
            'penalty',
            $reason,
            $expiresAt,
            $profile
        );
    }
}
