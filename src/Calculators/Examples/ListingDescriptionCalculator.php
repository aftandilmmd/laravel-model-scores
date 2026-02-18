<?php

namespace Aftandilmmd\LaravelModelScores\Calculators\Examples;

use Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator;
use Illuminate\Database\Eloquent\Model;

/**
 * Combined calculator — Listing Description Quality.
 *
 * Demonstrates combining multiple helpers in a single calculator.
 * Uses binaryScore() for the empty case and tieredScore() for length-based quality.
 *
 * Airbnb listings with detailed descriptions rank higher in search.
 * Empty descriptions fail immediately (binary). Non-empty descriptions
 * are scored by length in tiers, rewarding hosts who write more detail.
 *
 * Results (maxPoints = 60):
 *   - Empty description     → binary(false) → 0 points
 *   - "Nice place" (10ch)   → tier 1 => 0.20 → 12 points
 *   - 80 characters         → tier 50 => 0.40 → 24 points
 *   - 200 characters        → tier 150 => 0.70 → 42 points
 *   - 500+ characters       → tier 400 => 1.0 → 60 points
 */
class ListingDescriptionCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $description = $scoreable->description ?? '';
        $length = mb_strlen(strip_tags($description));

        if ($length === 0) {
            return $this->binaryScore(false, $maxPoints, [
                'description_length' => 0,
                'reason' => 'No description provided',
            ]);
        }

        return $this->tieredScore($length, [
            1   => 0.20,  // Has something — better than nothing
            50  => 0.40,  // Brief — covers the basics
            150 => 0.70,  // Detailed — mentions amenities & rules
            400 => 1.0,   // Comprehensive — neighborhood, tips, etc.
        ], $maxPoints, [
            'description_length' => $length,
        ]);
    }
}
