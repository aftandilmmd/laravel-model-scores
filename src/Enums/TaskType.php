<?php

namespace Aftandilmmd\LaravelModelScores\Enums;

enum TaskType: string
{
    case Static = 'static';
    case Periodic = 'periodic';

    public function label(): string
    {
        return match ($this) {
            self::Static => __('model-scores::messages.task_type.static'),
            self::Periodic => __('model-scores::messages.task_type.periodic'),
        };
    }
}
