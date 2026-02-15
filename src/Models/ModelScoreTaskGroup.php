<?php

namespace Aftandilmmd\LaravelModelScores\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelScoreTaskGroup extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'order_column',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('model-scores.tables.task_groups', 'model_scores_task_groups');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(config('model-scores.models.task', ModelScoreTask::class), 'group_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column');
    }
}
