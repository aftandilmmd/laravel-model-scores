<?php

namespace Aftandilmmd\LaravelModelScores\Calculators\Examples;

use Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator;
use Illuminate\Database\Eloquent\Model;

/**
 * Tiered calculator — Listing Photos.
 *
 * Uses tieredScore(): awards points based on threshold tiers.
 * Each tier maps a minimum count to a ratio (0.0–1.0). The highest matching
 * tier is selected and its ratio is used with proportionalScore() internally.
 *
 * Airbnb recommends at least 20 high-quality photos. Listings with more photos
 * get better visibility. We score in tiers to incentivize adding more.
 *
 * Formula: find highest tier where value >= threshold, then round(ratio * maxPoints)
 *
 * Tiers & results (maxPoints = 80):
 *   - 0 photos   → tier 0 => 0.0   → 0 points
 *   - 4 photos   → tier 1 => 0.15  → 12 points
 *   - 7 photos   → tier 5 => 0.35  → 28 points
 *   - 12 photos  → tier 10 => 0.60 → 48 points
 *   - 18 photos  → tier 15 => 0.80 → 64 points
 *   - 25 photos  → tier 20 => 1.0  → 80 points
 */
class ListingPhotosCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $count = $scoreable->photos()->count();

        return $this->tieredScore($count, [
            0  => 0.0,   // No photos
            1  => 0.15,  // At least one — listing is visible
            5  => 0.35,  // Basic coverage of the space
            10 => 0.60,  // Good coverage — each room shown
            15 => 0.80,  // Detailed — amenities and neighborhood
            20 => 1.0,   // Professional-level listing
        ], $maxPoints, [
            'photos_count' => $count,
        ]);
    }
}
