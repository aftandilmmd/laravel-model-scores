# Laravel Model Scores

A flexible, multi-dimensional quality scoring system for Laravel models. Define scoring tasks, assign calculators, track score history with event sourcing, manage badges, and apply manual adjustments — all through a clean, extensible package.

**[Turkish (TR)](README.tr.md)** | **[Azerbaijani (AZ)](README.az.md)**

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Features

- **Strategy Pattern Calculators** — Each scoring task has its own calculator class
- **Polymorphic Scoring** — Score any Eloquent model (User, Tenant, Company, etc.)
- **Task Groups** — Organize tasks into logical groups (Profile, Performance, etc.)
- **Weighted Scoring** — Tasks can have different weights (0.50x to 2.00x)
- **Score Decay** — Periodic task scores gradually decrease over time
- **Badge System** — Automatic badge assignment based on score thresholds
- **Event Sourcing** — Every score change logged as a separate event record
- **Manual Adjustments** — Add bonus/penalty points with optional expiration
- **Score Profiles** — Multiple score types per model (quality, trust, engagement)
- **Threshold Notifications** — Dispatch events when scores cross configured thresholds
- **Livewire Components** — Optional checklist, progress bar, and breakdown chart widgets
- **REST API** — Optional API endpoints for managing scores and tasks
- **Artisan Commands** — Calculate scores in bulk, prune old events

## Installation

```bash
composer require aftandilmmd/laravel-model-scores
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=model-scores-config
php artisan vendor:publish --tag=model-scores-migrations
php artisan migrate
```

## Configuration

The package is highly configurable via `config/model-scores.php`:

```php
return [
    'models' => [
        'task' => QualityTask::class,
        'task_group' => QualityTaskGroup::class,
        'score' => QualityScore::class,
        // ...
    ],
    'tables' => [
        'task_groups' => 'model_scores_task_groups',
        'tasks' => 'model_scores_tasks',
        // ...
    ],
    'score_column' => 'quality_score',
    'features' => [
        'badges' => true,
        'event_log' => true,
        'decay' => true,
        'adjustments' => true,
        'weights' => true,
        // ...
    ],
];
```

## Usage

### 1. Add the Trait to Your Model

```php
use Aftandilmmd\LaravelModelScores\Traits\HasQualityScores;

class Tenant extends Model
{
    use HasQualityScores;
}
```

### 2. Create a Calculator

```php
use Aftandilmmd\LaravelModelScores\Calculators\BaseCalculator;
use Illuminate\Database\Eloquent\Model;

class ProfilePhotoCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        return $this->binaryScore(
            ! empty($scoreable->profile_photo),
            $maxPoints
        );
    }
}
```

### 3. Seed Tasks into Database

```php
QualityTask::create([
    'key' => 'profile_photo',
    'name' => 'Upload Profile Photo',
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
    'weight' => 1.00,
]);
```

### 4. Calculate Scores

```php
use Aftandilmmd\LaravelModelScores\Facades\ModelScores;

// Calculate for a single model
$totalScore = ModelScores::calculateFor($tenant);

// Calculate for all models
ModelScores::calculateForAll(Tenant::class);

// Or via the trait
$tenant->calculateQualityScore();
```

### 5. Query Scores

```php
// Get detailed breakdown
$breakdown = ModelScores::getBreakdown($tenant);

// Get checklist items (for wizard UI)
$checklist = ModelScores::getChecklistItems($tenant);

// Get current badge
$badge = ModelScores::getCurrentBadge($tenant);

// Get score history (event sourcing)
$history = ModelScores::getScoreHistory($tenant, 'default', 30);

// Get timeline of all events
$timeline = ModelScores::getScoreTimeline($tenant);
```

### 6. Manual Adjustments

```php
// Add bonus points (with optional expiry)
ModelScores::addAdjustment($tenant, 20, 'bonus', 'Welcome bonus', now()->addDays(30));

// Add penalty
ModelScores::addAdjustment($tenant, -10, 'penalty', 'Late payment');

// Via trait shortcuts
$tenant->addQualityBonus(20, 'Welcome bonus', now()->addDays(30));
$tenant->addQualityPenalty(10, 'Late payment');

// Revoke an adjustment
ModelScores::revokeAdjustment($adjustment);
```

## BaseCalculator Helpers

The `BaseCalculator` provides helper methods for common scoring patterns:

| Method | Description |
|--------|-------------|
| `binaryScore($condition, $maxPoints)` | All-or-nothing based on boolean |
| `proportionalScore($ratio, $maxPoints)` | Score proportional to 0.0–1.0 ratio |
| `inverseScore($ratio, $threshold, $maxPoints)` | Lower ratio = higher score |
| `tieredScore($value, $tiers, $maxPoints)` | Tiered thresholds with ratios |
| `applyDecay($score, $decayDays, $calculatedAt)` | Gradual score decrease over time |

## Artisan Commands

```bash
# Calculate scores for all models
php artisan model-scores:calculate "App\Models\Tenant"

# Calculate for a specific model
php artisan model-scores:calculate "App\Models\Tenant" --id=1

# Only static or periodic tasks
php artisan model-scores:calculate "App\Models\Tenant" --type=static

# Prune old event logs
php artisan model-scores:prune-events --days=365
```

## Events

| Event | When |
|-------|------|
| `ScoresCalculated` | After all tasks are calculated |
| `TaskScoreUpdated` | When a single task score changes |
| `BadgeEarned` | When score reaches a new badge level |
| `BadgeLost` | When score drops below a badge level |
| `ScoreThresholdCrossed` | When configured threshold is crossed |
| `ManualAdjustmentApplied` | When a manual adjustment is added |

## Livewire Components (Optional)

Enable in config with `model-scores.livewire.enabled = true`:

```blade
{{-- Score checklist (wizard-style) --}}
<livewire:model-scores-checklist :scoreable="$tenant" />

{{-- Progress bar with badge --}}
<livewire:model-scores-progress-bar :scoreable="$tenant" />

{{-- Breakdown chart (ApexCharts) --}}
<livewire:model-scores-breakdown-chart :scoreable="$tenant" />
```

## REST API (Optional)

Enable in config with `model-scores.api.enabled = true`:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/model-scores/tasks` | List all tasks |
| GET | `/api/model-scores/{type}/{id}/breakdown` | Score breakdown |
| GET | `/api/model-scores/{type}/{id}/history` | Score history |
| POST | `/api/model-scores/{type}/{id}/adjustments` | Add adjustment |
| DELETE | `/api/model-scores/adjustments/{id}` | Revoke adjustment |

## Database Tables

The package creates 6 tables (all customizable via config):

- `model_scores_task_groups` — Task group definitions
- `model_scores_tasks` — Task definitions with calculator FQCN
- `model_scores_scores` — Per-model task score results
- `model_scores_score_events` — Event-level score history
- `model_scores_badges` — Badge/level definitions
- `model_scores_adjustments` — Manual point adjustments

## License

MIT License. See [LICENSE](LICENSE) for details.
