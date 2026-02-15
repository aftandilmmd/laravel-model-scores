<?php

namespace Aftandilmmd\LaravelModelScores\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ScoreCalculator
{
    /**
     * Calculate the score for a given scoreable model.
     *
     * @return array{score: int, metadata: array}
     */
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array;
}
