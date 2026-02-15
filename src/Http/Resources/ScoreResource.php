<?php

namespace Aftandilmmd\LaravelModelScores\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->date?->toIso8601String(),
            'total' => $this->total,
            'previous_total' => $this->previous_total,
            'change' => $this->change,
        ];
    }
}
