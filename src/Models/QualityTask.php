<?php

namespace Aftandilmmd\LaravelModelScores\Models;

use Aftandilmmd\LaravelModelScores\Enums\TaskType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualityTask extends Model
{
    protected $fillable = [
        'group_id',
        'key',
        'name',
        'description',
        'icon',
        'calculator',
        'type',
        'max_points',
        'weight',
        'order_column',
        'is_active',
        'show_in_checklist',
        'route',
        'profile',
        'decay_days',
        'metadata',
    ];

    protected $casts = [
        'type' => TaskType::class,
        'max_points' => 'integer',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'show_in_checklist' => 'boolean',
        'decay_days' => 'integer',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('model-scores.tables.tasks', 'model_scores_tasks');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(config('model-scores.models.task_group', QualityTaskGroup::class), 'group_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(config('model-scores.models.score', QualityScore::class), 'quality_task_id');
    }

    public function getEffectiveMaxPoints(): float
    {
        if (config('model-scores.features.weights', true)) {
            return $this->max_points * $this->weight;
        }

        return $this->max_points;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStatic(Builder $query): Builder
    {
        return $query->where('type', TaskType::Static);
    }

    public function scopePeriodic(Builder $query): Builder
    {
        return $query->where('type', TaskType::Periodic);
    }

    public function scopeForChecklist(Builder $query): Builder
    {
        return $query->where('show_in_checklist', true);
    }

    public function scopeForProfile(Builder $query, string $profile = 'default'): Builder
    {
        return $query->where('profile', $profile);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column');
    }
}
