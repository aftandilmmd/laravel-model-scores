<?php

namespace Aftandilmmd\LaravelModelScores\Commands;

use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Illuminate\Console\Command;

class CalculateScoresCommand extends Command
{
    protected $signature = 'model-scores:calculate
        {scoreable : The fully qualified model class name}
        {--id= : Calculate for a specific model ID only}
        {--type=all : Task type filter (all, static, periodic)}
        {--profile=default : Score profile to calculate}';

    protected $description = 'Calculate model scores for scoreable models';

    public function handle(ModelScoresServiceInterface $service): int
    {
        $scoreableClass = $this->argument('scoreable');
        $id = $this->option('id');
        $type = $this->option('type');
        $profile = $this->option('profile');

        if (! class_exists($scoreableClass)) {
            $this->error("Class [{$scoreableClass}] does not exist.");

            return self::FAILURE;
        }

        if ($id !== null) {
            $scoreable = $scoreableClass::find($id);

            if (! $scoreable) {
                $this->error("Model [{$scoreableClass}] with ID [{$id}] not found.");

                return self::FAILURE;
            }

            $this->info("Calculating scores for {$scoreableClass} #{$id}...");
            $totalScore = $service->calculateFor($scoreable, $type === 'all' ? null : $type, $profile);
            $this->info("Done. Total score: {$totalScore}");

            return self::SUCCESS;
        }

        $this->info("Calculating scores for all [{$scoreableClass}] models...");

        $query = $type === 'all' ? null : $type;
        $count = $service->calculateForAll($scoreableClass, $query, $profile);

        $this->info("Done. Calculated scores for {$count} models.");

        return self::SUCCESS;
    }
}
