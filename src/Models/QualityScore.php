<?php

namespace Aftandilmmd\LaravelModelScores\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QualityScore extends Model
{
    protected $fillable = [
        'scoreable_type',
        'scoreable_id',
        'quality_task_id',
        'score',
        'max_score',
        'weighted_score',
        'metadata',
        'calculated_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'max_score' => 'integer',
        'weighted_score' => 'decimal:2',
        'metadata' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('model-scores.tables.scores', 'model_scores_scores');
    }

    public function scoreable(): MorphTo
    {
        return $this->morphTo();
    }

    public function qualityTask(): BelongsTo
    {
        return $this->belongsTo(config('model-scores.models.task', QualityTask::class), 'quality_task_id');
    }

    public function isStale(int $days): bool
    {
        if (! $this->calculated_at) {
            return true;
        }

        return $this->calculated_at->diffInDays(now()) >= $days;
    }
}
