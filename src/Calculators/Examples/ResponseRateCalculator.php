<?php

namespace Aftandilmmd\LaravelModelScores\Calculators\Examples;

use Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator;
use Illuminate\Database\Eloquent\Model;

/**
 * Proportional calculator — Host Response Rate.
 *
 * Uses proportionalScore(): points scale linearly with a 0.0–1.0 ratio.
 * Values are clamped automatically (below 0 → 0, above 1 → 1).
 *
 * Airbnb Superhost requires a 90%+ response rate. Here we score the host
 * proportionally — responding to 70% of inquiries earns 70% of points.
 *
 * Formula: round(clamp(ratio, 0, 1) * maxPoints)
 *
 * Results (maxPoints = 100):
 *   - 100% response rate → ratio 1.0 → 100 points
 *   - 90% response rate  → ratio 0.9 → 90 points
 *   - 50% response rate  → ratio 0.5 → 50 points
 *   - 0% response rate   → ratio 0.0 → 0 points
 */
class ResponseRateCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $totalInquiries = $scoreable->inquiries()
            ->where('created_at', '>=', now()->subYear())
            ->count();

        $respondedInquiries = $scoreable->inquiries()
            ->where('created_at', '>=', now()->subYear())
            ->whereNotNull('responded_at')
            ->count();

        $rate = $totalInquiries > 0 ? $respondedInquiries / $totalInquiries : 1.0;

        return $this->proportionalScore($rate, $maxPoints, [
            'total_inquiries' => $totalInquiries,
            'responded' => $respondedInquiries,
            'response_rate' => round($rate * 100, 1),
        ]);
    }
}
