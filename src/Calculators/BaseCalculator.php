<?php

namespace Aftandilmmd\LaravelModelScores\Calculators;

use Aftandilmmd\LaravelModelScores\Contracts\ScoreCalculator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCalculator implements ScoreCalculator
{
    abstract public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array;

    /**
     * All-or-nothing score based on a boolean condition.
     */
    protected function binaryScore(bool $condition, int $maxPoints, array $metadata = []): array
    {
        return [
            'score' => $condition ? $maxPoints : 0,
            'metadata' => $metadata,
        ];
    }

    /**
     * Score proportional to a ratio (0.0 to 1.0).
     */
    protected function proportionalScore(float $ratio, int $maxPoints, array $metadata = []): array
    {
        $ratio = max(0, min(1, $ratio));

        return [
            'score' => (int) round($ratio * $maxPoints),
            'metadata' => $metadata,
        ];
    }

    /**
     * Inverse proportional score (lower ratio = higher score).
     * ratio = 0 gives full points, ratio >= threshold gives 0 points.
     */
    protected function inverseScore(float $ratio, float $threshold, int $maxPoints, array $metadata = []): array
    {
        if ($threshold <= 0) {
            return ['score' => $maxPoints, 'metadata' => $metadata];
        }

        if ($ratio >= $threshold) {
            $score = 0;
        } else {
            $score = (int) round((1 - ($ratio / $threshold)) * $maxPoints);
        }

        return [
            'score' => max(0, $score),
            'metadata' => $metadata,
        ];
    }

    /**
     * Tiered score based on value thresholds.
     * $tiers = [0 => 0.0, 10 => 0.25, 25 => 0.50, 50 => 0.75, 100 => 1.0]
     */
    protected function tieredScore(float $value, array $tiers, int $maxPoints, array $metadata = []): array
    {
        ksort($tiers);
        $ratio = 0.0;

        foreach ($tiers as $threshold => $tierRatio) {
            if ($value >= $threshold) {
                $ratio = $tierRatio;
            }
        }

        return $this->proportionalScore($ratio, $maxPoints, $metadata);
    }

    /**
     * Apply decay to a score based on days since last calculation.
     */
    public function applyDecay(int $score, ?int $decayDays, ?Carbon $calculatedAt): int
    {
        if (! config('model-scores.features.decay', true)) {
            return $score;
        }

        if ($decayDays === null || $calculatedAt === null) {
            return $score;
        }

        $daysSince = (int) $calculatedAt->diffInDays(now());

        if ($daysSince <= $decayDays) {
            return $score;
        }

        $decayAmount = $daysSince - $decayDays;
        $strategy = config('model-scores.decay.strategy', 'linear');

        if ($strategy === 'exponential') {
            $factor = pow(0.95, $decayAmount);
        } else {
            $factor = max(0, 1 - ($decayAmount * 0.02));
        }

        return (int) round($score * $factor);
    }
}
