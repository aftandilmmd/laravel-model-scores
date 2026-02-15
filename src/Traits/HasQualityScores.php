<?php

namespace Aftandilmmd\LaravelModelScores\Traits;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Aftandilmmd\LaravelModelScores\Models\QualityAdjustment;
use Aftandilmmd\LaravelModelScores\Models\QualityBadge;
use Aftandilmmd\LaravelModelScores\Models\QualityScore;
use Aftandilmmd\LaravelModelScores\Models\QualityScoreEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasQualityScores
{
    public function qualityScores(): MorphMany
    {
        return $this->morphMany(
            config('model-scores.models.score', QualityScore::class),
            'scoreable'
        );
    }

    public function qualityEvents(): MorphMany
    {
        return $this->morphMany(
            config('model-scores.models.score_event', QualityScoreEvent::class),
            'scoreable'
        );
    }

    public function qualityAdjustments(): MorphMany
    {
        return $this->morphMany(
            config('model-scores.models.adjustment', QualityAdjustment::class),
            'scoreable'
        );
    }

    public function calculateQualityScore(?string $type = null, string $profile = 'default'): int
    {
        return app(ModelScoresServiceInterface::class)->calculateFor($this, $type, $profile);
    }

    public function getQualityBreakdown(string $profile = 'default'): Collection
    {
        return app(ModelScoresServiceInterface::class)->getBreakdown($this, $profile);
    }

    public function getQualityChecklist(string $profile = 'default'): Collection
    {
        return app(ModelScoresServiceInterface::class)->getChecklistItems($this, $profile);
    }

    public function getQualityBadge(string $profile = 'default'): ?QualityBadge
    {
        return app(ModelScoresServiceInterface::class)->getCurrentBadge($this, $profile);
    }

    public function getQualityHistory(string $profile = 'default', int $days = 30): Collection
    {
        return app(ModelScoresServiceInterface::class)->getScoreHistory($this, $profile, $days);
    }

    public function addQualityBonus(int $points, ?string $reason = null, ?Carbon $expiresAt = null, string $profile = 'default'): QualityAdjustment
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

    public function addQualityPenalty(int $points, ?string $reason = null, ?Carbon $expiresAt = null, string $profile = 'default'): QualityAdjustment
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
