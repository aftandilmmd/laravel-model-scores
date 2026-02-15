<?php

namespace Aftandilmmd\LaravelModelScores\Facades;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Illuminate\Support\Facades\Facade;

/**
 * @method static int calculateFor(\Illuminate\Database\Eloquent\Model $scoreable, ?string $type = null, string $profile = 'default')
 * @method static int calculateForAll(string $scoreableClass, ?string $type = null, string $profile = 'default', ?\Illuminate\Database\Eloquent\Builder $query = null)
 * @method static \Illuminate\Support\Collection getBreakdown(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default')
 * @method static \Illuminate\Support\Collection getChecklistItems(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default')
 * @method static \Illuminate\Support\Collection getTasks(?string $type = null, string $profile = 'default')
 * @method static \Illuminate\Support\Collection getTaskGroups(string $profile = 'default')
 * @method static int getTotalScore(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default')
 * @method static ?\Aftandilmmd\LaravelModelScores\Models\QualityBadge getCurrentBadge(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default')
 * @method static \Illuminate\Support\Collection getAvailableBadges(string $profile = 'default')
 * @method static \Illuminate\Support\Collection getScoreHistory(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default', int $days = 30)
 * @method static \Illuminate\Support\Collection getScoreTimeline(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default', ?string $eventType = null, int $limit = 50)
 * @method static \Aftandilmmd\LaravelModelScores\Models\QualityAdjustment addAdjustment(\Illuminate\Database\Eloquent\Model $scoreable, int $points, string $type = 'manual', ?string $reason = null, ?\Carbon\Carbon $expiresAt = null, string $profile = 'default')
 * @method static void revokeAdjustment(\Aftandilmmd\LaravelModelScores\Models\QualityAdjustment $adjustment)
 * @method static \Illuminate\Support\Collection getActiveAdjustments(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default')
 * @method static int getAdjustmentsTotal(\Illuminate\Database\Eloquent\Model $scoreable, string $profile = 'default')
 *
 * @see \Aftandilmmd\LaravelModelScores\Services\QualityScoreService
 */
class ModelScores extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModelScoresServiceInterface::class;
    }
}
