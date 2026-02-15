<?php

namespace Aftandilmmd\LaravelModelScores\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScoresCalculated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $scoreable,
        public readonly int $totalScore,
        public readonly string $profile = 'default',
    ) {}
}
