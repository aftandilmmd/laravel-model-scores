<?php

namespace Aftandilmmd\LaravelModelScores\Enums;

enum ScoreEventType: string
{
    case TaskScoreChanged = 'task_score_changed';
    case AdjustmentAdded = 'adjustment_added';
    case AdjustmentExpired = 'adjustment_expired';
    case AdjustmentRevoked = 'adjustment_revoked';
    case BadgeEarned = 'badge_earned';
    case BadgeLost = 'badge_lost';
    case Recalculated = 'recalculated';
}
