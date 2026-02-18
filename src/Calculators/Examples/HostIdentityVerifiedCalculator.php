<?php

namespace Aftandilmmd\LaravelModelScores\Calculators\Examples;

use Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator;
use Illuminate\Database\Eloquent\Model;

/**
 * Binary calculator — Host Identity Verification.
 *
 * Uses binaryScore(): full points if the condition is true, zero otherwise.
 *
 * Airbnb requires hosts to verify their identity (government ID, selfie, etc.)
 * before they can become Superhosts. This is a simple pass/fail check.
 *
 * Formula: condition ? maxPoints : 0
 *
 * Results (maxPoints = 50):
 *   - Verified   → 50
 *   - Unverified → 0
 */
class HostIdentityVerifiedCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $verified = ! empty($scoreable->identity_verified_at);

        return $this->binaryScore($verified, $maxPoints, [
            'verified' => $verified,
            'verified_at' => $scoreable->identity_verified_at,
        ]);
    }
}
