<?php

namespace Aftandilmmd\LaravelModelScores\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'type' => $this->type,
            'max_points' => $this->max_points,
            'weight' => $this->weight,
            'effective_max_points' => $this->getEffectiveMaxPoints(),
            'show_in_checklist' => $this->show_in_checklist,
            'profile' => $this->profile,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'key' => $this->group->key,
                'name' => $this->group->name,
            ]),
        ];
    }
}
