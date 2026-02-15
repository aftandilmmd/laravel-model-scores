<?php

namespace Aftandilmmd\LaravelModelScores;

use Aftandilmmd\LaravelModelScores\Commands\CalculateScoresCommand;
use Aftandilmmd\LaravelModelScores\Commands\PruneScoreEventsCommand;
use Aftandilmmd\LaravelModelScores\Contracts\ModelScoresServiceInterface;
use Aftandilmmd\LaravelModelScores\Services\ModelScoreService;
use Illuminate\Support\ServiceProvider;

class LaravelModelScoresServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/model-scores.php', 'model-scores');

        $this->app->singleton(ModelScoresServiceInterface::class, function ($app) {
            return new ModelScoreService;
        });

        $this->app->alias(ModelScoresServiceInterface::class, 'model-scores');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/model-scores.php' => config_path('model-scores.php'),
        ], 'model-scores-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'model-scores-migrations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'model-scores');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/model-scores'),
        ], 'model-scores-translations');

        if (is_dir($viewsPath = __DIR__.'/../resources/views')) {
            $this->loadViewsFrom($viewsPath, 'model-scores');

            $this->publishes([
                $viewsPath => resource_path('views/vendor/model-scores'),
            ], 'model-scores-views');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CalculateScoresCommand::class,
                PruneScoreEventsCommand::class,
            ]);
        }

        $this->registerLivewireComponents();
        $this->registerApiRoutes();
    }

    protected function registerLivewireComponents(): void
    {
        if (! config('model-scores.livewire.enabled', true)) {
            return;
        }

        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component('model-scores-checklist', Livewire\ScoreChecklist::class);
        \Livewire\Livewire::component('model-scores-progress-bar', Livewire\ScoreProgressBar::class);
        \Livewire\Livewire::component('model-scores-breakdown-chart', Livewire\ScoreBreakdownChart::class);
    }

    protected function registerApiRoutes(): void
    {
        if (! config('model-scores.api.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
