<?php

namespace Aftandilmmd\LaravelModelScores\Livewire;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ScoreChecklist extends Component
{
    public string $scoreableType;

    public int $scoreableId;

    public string $profile = 'default';

    public function mount(Model|string $scoreable, ?int $scoreableId = null, string $profile = 'default'): void
    {
        if ($scoreable instanceof Model) {
            $this->scoreableType = $scoreable->getMorphClass();
            $this->scoreableId = $scoreable->getKey();
        } else {
            $this->scoreableType = $scoreable;
            $this->scoreableId = $scoreableId;
        }

        $this->profile = $profile;
    }

    public function getScoreableProperty(): Model
    {
        return $this->scoreableType::findOrFail($this->scoreableId);
    }

    public function getChecklistProperty()
    {
        $service = app(ModelScoresServiceInterface::class);

        return $service->getChecklistItems($this->scoreable, $this->profile);
    }

    public function getGroupedChecklistProperty()
    {
        return $this->checklist->groupBy(fn ($item) => $item->group?->name ?? __('model-scores::messages.ungrouped'));
    }

    public function getCompletedCountProperty(): int
    {
        return $this->checklist->where('is_complete', true)->count();
    }

    public function getTotalCountProperty(): int
    {
        return $this->checklist->count();
    }

    public function getProgressPercentageProperty(): int
    {
        if ($this->totalCount === 0) {
            return 0;
        }

        return (int) round($this->completedCount / $this->totalCount * 100);
    }

    public function render()
    {
        return view('model-scores::livewire.score-checklist');
    }
}
