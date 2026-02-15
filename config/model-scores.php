<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | Customize the model classes used by Laravel Model Scores. You can extend
    | the default models and specify your custom classes here.
    |
    */

    'models' => [
        'task' => \Aftandilmmd\LaravelModelScores\Models\ModelScoreTask::class,
        'task_group' => \Aftandilmmd\LaravelModelScores\Models\ModelScoreTaskGroup::class,
        'score' => \Aftandilmmd\LaravelModelScores\Models\ModelScore::class,
        'score_event' => \Aftandilmmd\LaravelModelScores\Models\ModelScoreEvent::class,
        'badge' => \Aftandilmmd\LaravelModelScores\Models\ModelScoreBadge::class,
        'adjustment' => \Aftandilmmd\LaravelModelScores\Models\ModelScoreAdjustment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the table names used by Laravel Model Scores.
    |
    */

    'tables' => [
        'task_groups' => 'model_scores_task_groups',
        'tasks' => 'model_scores_tasks',
        'scores' => 'model_scores_scores',
        'score_events' => 'model_scores_score_events',
        'badges' => 'model_scores_badges',
        'adjustments' => 'model_scores_adjustments',
    ],

    /*
    |--------------------------------------------------------------------------
    | Score Column
    |--------------------------------------------------------------------------
    |
    | The column name on the scoreable model's table that caches the total
    | score. Set to null to skip caching on the model (scores will be
    | computed from the scores table on each read). When set, you must
    | add this column to your model's table yourself.
    |
    */

    'score_column' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class used for tracking who granted adjustments
    | and who caused score events.
    |
    */

    'user_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Toggle individual features on or off.
    |
    */

    'features' => [
        'badges' => true,
        'event_log' => true,
        'decay' => true,
        'adjustments' => true,
        'weights' => true,
        'groups' => true,
        'thresholds' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Log
    |--------------------------------------------------------------------------
    |
    | Configure the event-level score history tracking.
    |
    */

    'event_log' => [
        'enabled' => true,
        'retention_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Decay
    |--------------------------------------------------------------------------
    |
    | Configure score decay for periodic tasks. When a task has decay_days
    | set, the score will gradually decrease after that many days since
    | the last calculation.
    |
    */

    'decay' => [
        'strategy' => 'linear', // linear, exponential
        'check_interval_days' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    |
    | Define score thresholds that trigger ScoreThresholdCrossed events.
    | Useful for sending notifications when a scoreable reaches or drops
    | below certain score levels.
    |
    */

    'thresholds' => [
        // 'score_rose_above' => [500, 750, 900],
        // 'score_dropped_below' => [200, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Customize the event classes dispatched by Laravel Model Scores.
    | Set to null to disable a specific event.
    |
    */

    'events' => [
        'scores_calculated' => \Aftandilmmd\LaravelModelScores\Events\ScoresCalculated::class,
        'task_score_updated' => \Aftandilmmd\LaravelModelScores\Events\TaskScoreUpdated::class,
        'badge_earned' => \Aftandilmmd\LaravelModelScores\Events\BadgeEarned::class,
        'badge_lost' => \Aftandilmmd\LaravelModelScores\Events\BadgeLost::class,
        'score_threshold_crossed' => \Aftandilmmd\LaravelModelScores\Events\ScoreThresholdCrossed::class,
        'manual_adjustment_applied' => \Aftandilmmd\LaravelModelScores\Events\ManualAdjustmentApplied::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | Configure the optional REST API for managing scores and tasks.
    |
    */

    'api' => [
        'enabled' => false,
        'prefix' => 'api/model-scores',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Configure the optional Livewire components.
    |
    */

    'livewire' => [
        'enabled' => true,
    ],

];
