<?php

namespace Aftandilmmd\LaravelModelScores\Models;

use Aftandilmmd\LaravelModelScores\Enums\ScoreEventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelScoreEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scoreable_type',
        'scoreable_id',
        'profile',
        'event_type',
        'model_score_task_id',
        'old_score',
        'new_score',
        'old_total',
        'new_total',
        'caused_by',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'event_type' => ScoreEventType::class,
        'old_score' => 'integer',
        'new_score' => 'integer',
        'old_total' => 'integer',
        'new_total' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('model-scores.tables.score_events', 'model_scores_score_events');
    }

    public function scoreable(): MorphTo
    {
        return $this->morphTo();
    }

    public function modelScoreTask(): BelongsTo
    {
        return $this->belongsTo(config('model-scores.models.task', ModelScoreTask::class), 'model_score_task_id');
    }

    public function causedBy(): BelongsTo
    {
        return $this->belongsTo(config('model-scores.user_model', 'App\\Models\\User'), 'caused_by');
    }

    public function scopeForProfile(Builder $query, string $profile = 'default'): Builder
    {
        return $query->where('profile', $profile);
    }

    public function scopeOfType(Builder $query, ScoreEventType|string $eventType): Builder
    {
        $value = $eventType instanceof ScoreEventType ? $eventType->value : $eventType;

        return $query->where('event_type', $value);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForTask(Builder $query, int $taskId): Builder
    {
        return $query->where('model_score_task_id', $taskId);
    }

    protected static function booted(): void
    {
        static::creating(function (self $event) {
            $event->created_at ??= now();
        });
    }
}
