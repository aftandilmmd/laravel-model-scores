<?php

namespace Aftandilmmd\LaravelModelScores\Livewire;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ScoreBreakdownChart extends Component
{
    public string $scoreableType;

    public int $scoreableId;

    public string $profile = 'default';

    public string $chartType = 'bar'; // bar, radar

    public function mount(Model|string $scoreable, ?int $scoreableId = null, string $profile = 'default', string $chartType = 'bar'): void
    {
        if ($scoreable instanceof Model) {
            $this->scoreableType = $scoreable->getMorphClass();
            $this->scoreableId = $scoreable->getKey();
        } else {
            $this->scoreableType = $scoreable;
            $this->scoreableId = $scoreableId;
        }

        $this->profile = $profile;
        $this->chartType = $chartType;
    }

    public function getScoreableProperty(): Model
    {
        return $this->scoreableType::findOrFail($this->scoreableId);
    }

    public function getBreakdownProperty()
    {
        $service = app(ModelScoresServiceInterface::class);

        return $service->getBreakdown($this->scoreable, $this->profile);
    }

    public function getChartDataProperty(): array
    {
        $breakdown = $this->breakdown;

        return [
            'labels' => $breakdown->pluck('task.name')->toArray(),
            'scores' => $breakdown->pluck('score')->toArray(),
            'maxScores' => $breakdown->pluck('max_score')->toArray(),
            'percentages' => $breakdown->pluck('percentage')->toArray(),
        ];
    }

    public function render()
    {
        return view('model-scores::livewire.score-breakdown-chart');
    }
}
