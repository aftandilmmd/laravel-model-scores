<?php

namespace Aftandilmmd\LaravelModelScores\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelScoreBadge extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'color',
        'profile',
        'min_score',
        'max_score',
        'order_column',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('model-scores.tables.badges', 'model_scores_badges');
    }

    public function isEarnedBy(Model $scoreable): bool
    {
        $score = app(\Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface::class)
            ->getTotalScore($scoreable);

        if ($this->max_score !== null) {
            return $score >= $this->min_score && $score <= $this->max_score;
        }

        return $score >= $this->min_score;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForProfile(Builder $query, string $profile = 'default'): Builder
    {
        return $query->where('profile', $profile);
    }

    public function scopeForScore(Builder $query, int $score): Builder
    {
        return $query->where('min_score', '<=', $score)
            ->where(function (Builder $q) use ($score) {
                $q->whereNull('max_score')
                    ->orWhere('max_score', '>=', $score);
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column');
    }
}
