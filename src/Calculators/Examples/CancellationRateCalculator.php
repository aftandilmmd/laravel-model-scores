<?php

namespace Aftandilmmd\LaravelModelScores\Calculators\Examples;

use Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator;
use Illuminate\Database\Eloquent\Model;

/**
 * Inverse calculator — Host Cancellation Rate.
 *
 * Uses inverseScore(): lower ratio = higher score.
 * Score decreases linearly from maxPoints (at 0%) to 0 (at threshold%).
 *
 * Airbnb penalizes hosts who cancel confirmed reservations. Superhost status
 * requires less than 1% cancellation rate. Here we use a 5% threshold —
 * hosts with 5%+ cancellations score zero, and 0% cancellations earn full points.
 *
 * Formula: ratio >= threshold ? 0 : round((1 - ratio / threshold) * maxPoints)
 *
 * Results (maxPoints = 100, threshold = 0.05):
 *   - 0% cancellation  → 100 points
 *   - 1% cancellation  → 80 points
 *   - 2.5% cancellation → 50 points
 *   - 4% cancellation  → 20 points
 *   - 5%+ cancellation → 0 points
 */
class CancellationRateCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $threshold = $taskMetadata['threshold'] ?? 0.05;

        $totalReservations = $scoreable->reservations()
            ->where('check_in', '>=', now()->subYear())
            ->count();

        $cancelledByHost = $scoreable->reservations()
            ->where('check_in', '>=', now()->subYear())
            ->where('cancelled_by', 'host')
            ->count();

        $rate = $totalReservations > 0 ? $cancelledByHost / $totalReservations : 0;

        return $this->inverseScore($rate, $threshold, $maxPoints, [
            'total_reservations' => $totalReservations,
            'cancelled_by_host' => $cancelledByHost,
            'cancellation_rate' => round($rate * 100, 2),
            'threshold_percent' => $threshold * 100,
        ]);
    }
}
