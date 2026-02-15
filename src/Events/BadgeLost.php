<?php

namespace Aftandilmmd\LaravelModelScores\Events;

use Aftandilmmd\LaravelModelScores\Models\QualityBadge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadgeLost
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $scoreable,
        public readonly QualityBadge $badge,
        public readonly string $profile = 'default',
    ) {}
}
