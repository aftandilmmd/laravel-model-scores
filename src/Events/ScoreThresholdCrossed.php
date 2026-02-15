<?php

namespace Aftandilmmd\LaravelModelScores\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoreThresholdCrossed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $scoreable,
        public readonly int $threshold,
        public readonly string $direction, // 'up' or 'down'
        public readonly string $profile = 'default',
    ) {}
}
