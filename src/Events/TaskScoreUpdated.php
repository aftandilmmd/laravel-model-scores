<?php

namespace Aftandilmmd\LaravelModelScores\Events;

use Aftandilmmd\LaravelModelScores\Models\QualityTask;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskScoreUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $scoreable,
        public readonly QualityTask $task,
        public readonly int $oldScore,
        public readonly int $newScore,
    ) {}
}
