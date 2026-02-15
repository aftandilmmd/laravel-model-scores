<?php

namespace Aftandilmmd\LaravelModelScores\Commands;

use Aftandilmmd\LaravelModelScores\Models\QualityScoreEvent;
use Illuminate\Console\Command;

class PruneScoreEventsCommand extends Command
{
    protected $signature = 'model-scores:prune-events
        {--days= : Days to retain (overrides config)}
        {--type= : Only prune specific event type}';

    protected $description = 'Prune old quality score event logs';

    public function handle(): int
    {
        $days = $this->option('days') ?? config('model-scores.event_log.retention_days', 365);
        $type = $this->option('type');

        $eventModel = config('model-scores.models.score_event', QualityScoreEvent::class);

        $query = $eventModel::where('created_at', '<', now()->subDays((int) $days));

        if ($type !== null) {
            $query->where('event_type', $type);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No events to prune.');

            return self::SUCCESS;
        }

        $this->info("Pruning {$count} events older than {$days} days...");

        $query->delete();

        $this->info("Done. Pruned {$count} events.");

        return self::SUCCESS;
    }
}
