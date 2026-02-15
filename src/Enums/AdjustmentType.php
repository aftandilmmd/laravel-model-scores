<?php

namespace Aftandilmmd\LaravelModelScores\Enums;

enum AdjustmentType: string
{
    case Bonus = 'bonus';
    case Penalty = 'penalty';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Bonus => __('model-scores::messages.adjustment_type.bonus'),
            self::Penalty => __('model-scores::messages.adjustment_type.penalty'),
            self::Manual => __('model-scores::messages.adjustment_type.manual'),
        };
    }
}
