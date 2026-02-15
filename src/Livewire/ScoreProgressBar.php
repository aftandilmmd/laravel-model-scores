<?php

namespace Aftandilmmd\LaravelModelScores\Livewire;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ScoreProgressBar extends Component
{
    public string $scoreableType;

    public int $scoreableId;

    public string $profile = 'default';

    public int $maxPossibleScore = 1000;

    public function mount(Model|string $scoreable, ?int $scoreableId = null, string $profile = 'default', int $maxPossibleScore = 1000): void
    {
        if ($scoreable instanceof Model) {
            $this->scoreableType = $scoreable->getMorphClass();
            $this->scoreableId = $scoreable->getKey();
        } else {
            $this->scoreableType = $scoreable;
            $this->scoreableId = $scoreableId;
        }

        $this->profile = $profile;
        $this->maxPossibleScore = $maxPossibleScore;
    }

    public function getScoreableProperty(): Model
    {
        return $this->scoreableType::findOrFail($this->scoreableId);
    }

    public function getTotalScoreProperty(): int
    {
        $service = app(ModelScoresServiceInterface::class);

        return $service->getTotalScore($this->scoreable, $this->profile);
    }

    public function getBadgeProperty()
    {
        $service = app(ModelScoresServiceInterface::class);

        return $service->getCurrentBadge($this->scoreable, $this->profile);
    }

    public function getProgressPercentageProperty(): int
    {
        if ($this->maxPossibleScore === 0) {
            return 0;
        }

        return min(100, (int) round($this->totalScore / $this->maxPossibleScore * 100));
    }

    public function render()
    {
        return view('model-scores::livewire.score-progress-bar');
    }
}
