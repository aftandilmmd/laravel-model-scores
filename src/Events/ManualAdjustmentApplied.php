<?php

namespace Aftandilmmd\LaravelModelScores\Events;

use Aftandilmmd\LaravelModelScores\Models\QualityAdjustment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ManualAdjustmentApplied
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Model $scoreable,
        public readonly QualityAdjustment $adjustment,
        public readonly string $profile = 'default',
    ) {}
}
