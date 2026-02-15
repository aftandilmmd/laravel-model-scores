<?php

namespace Aftandilmmd\LaravelModelScores\Models;

use Aftandilmmd\LaravelModelScores\Enums\AdjustmentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class QualityAdjustment extends Model
{
    protected $fillable = [
        'scoreable_type',
        'scoreable_id',
        'profile',
        'points',
        'type',
        'reason',
        'expires_at',
        'granted_by',
        'revoked_at',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'type' => AdjustmentType::class,
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function getTable(): string
    {
        return config('model-scores.tables.adjustments', 'model_scores_adjustments');
    }

    public function scoreable(): MorphTo
    {
        return $this->morphTo();
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(config('model-scores.user_model', 'App\\Models\\User'), 'granted_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isActive(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeForProfile(Builder $query, string $profile = 'default'): Builder
    {
        return $query->where('profile', $profile);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
