<?php

namespace Aftandilmmd\LaravelModelScores\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreakdownResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'task' => [
                'id' => $this->task->id,
                'key' => $this->task->key,
                'name' => $this->task->name,
                'icon' => $this->task->icon,
            ],
            'group' => $this->group ? [
                'id' => $this->group->id,
                'name' => $this->group->name,
            ] : null,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'weighted_score' => $this->weighted_score,
            'max_weighted_score' => $this->max_weighted_score,
            'percentage' => $this->percentage,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
