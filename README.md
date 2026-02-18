# Laravel Model Scores

A flexible scoring system for Laravel models. Add points, penalties, and badges to any Eloquent model — no setup required. When you're ready, go deeper with calculators, task groups, decay, event sourcing, and more.

**[Turkish (TR)](README.tr.md)** | **[Azerbaijani (AZ)](README.az.md)**

## Why This Package?

Most Laravel applications eventually need to score, rank, or rate something — a vendor's reliability, a user's profile completeness, a listing's quality. You start with a few `if` statements, then add weights, then need history tracking, then someone asks for badges. Before long, scoring logic is scattered across your codebase with no audit trail and no consistency.

Laravel Model Scores gives you a structured way to handle this. Define each scoring criterion as an isolated calculator class, group them logically, assign weights, and let the package handle the rest — badge transitions, event sourcing, score decay, and bulk recalculation.

**Common use cases:**

- **Marketplace quality scores** — Score vendors on profile completeness, response rates, reviews, and fulfillment metrics. Think Airbnb Superhost or Etsy Star Seller.
- **Profile completion** — Drive users to complete their profiles with a checklist and progress bar. Each missing field is a scoring task.
- **Gamification and loyalty tiers** — Award points for engagement, purchases, or content creation. Assign Bronze/Silver/Gold badges automatically based on score ranges.
- **Compliance scoring** — Score organizations on safety audits, regulatory adherence, or process completion. Decay ensures stale compliance degrades over time.
- **Content and listing quality** — Score products or articles on data completeness, image count, and description quality. Use scores for search ranking.

**When to use it:**

- You have multiple independent scoring criteria
- Criteria use different logic (boolean checks, proportional metrics, inverse ratios, tiered thresholds)
- You need an audit trail of score changes
- You want badges or tiers that update automatically
- Some metrics should decay if not refreshed

**When you probably don't need it:**

- A single integer counter is enough (just use a column)
- You only need user-submitted star ratings (use a reviews package)
- Your "score" is one computed value with no history requirement

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require aftandilmmd/laravel-model-scores
```

Publish the config and migrations:

```bash
php artisan vendor:publish --tag=model-scores-config
php artisan vendor:publish --tag=model-scores-migrations
php artisan migrate
```

## Quick Start

### 1. Add the Trait

```php
use Aftandilmmd\LaravelModelScores\Traits\HasModelScores;

class Tenant extends Model
{
    use HasModelScores;
}
```

### 2. Add & Remove Points

```php
// Add bonus points (with optional expiry)
$tenant->addScoreBonus(20, 'Welcome bonus', now()->addDays(30));

// Add penalty
$tenant->addScorePenalty(10, 'Late payment');

// Read the total score
$total = ModelScore::getTotalScore($tenant);
```

### 3. Get Current Badge

```php
$badge = $tenant->scoreBadge();
// $badge->name, $badge->color, $badge->icon
```

### 4. View Score History

```php
$history = $tenant->scoreHistory(days: 30);
// [{ date, total, previous_total, change }, ...]
```

That's the basics — bonus/penalty, badges, history. No calculators or tasks needed.

### Using the Facade

The `ModelScore` facade gives you the same capabilities plus bulk operations:

```php
use Aftandilmmd\LaravelModelScores\Facades\ModelScore;

// Add adjustment via facade
ModelScore::addAdjustment($tenant, 20, 'bonus', 'Welcome bonus', now()->addDays(30));

// Revoke an adjustment
ModelScore::revokeAdjustment($adjustment);

// Query adjustments
$active = ModelScore::getActiveAdjustments($tenant);
$total = ModelScore::getAdjustmentsTotal($tenant);

// Get all available badges
$badges = ModelScore::getAvailableBadges();
```

### Caching Score on Model (Optional)

By default, the total score is computed from the database on each read. For faster access, you can cache it on your model's table:

1. Add a column to your migration:

```php
$table->unsignedInteger('model_score')->default(0);
```

2. Set it in config:

```php
// config/model-scores.php
'score_column' => 'model_score',
```

Now `$tenant->model_score` is always available and updated automatically.

---

# Advanced Usage

Everything below is optional — use what you need.

## Calculator System

Instead of manual bonuses/penalties, define **scoring tasks** with calculators that automatically evaluate your models.

### Create a Calculator

Each scoring criterion gets its own calculator class:

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

### Register Tasks

Define what's being scored via seeder or migration:

```php
use Aftandilmmd\LaravelModelScores\Models\ModelScoreTask;

ModelScoreTask::create([
    'key' => 'profile_photo',
    'name' => 'Upload Profile Photo',
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
]);
```

### Calculate Scores

```php
// Calculate all tasks for a model
$tenant->calculateScore();

// Only static or periodic tasks
$tenant->calculateScore('static');

// Calculate for all models (chunks of 100)
ModelScore::calculateForAll(Tenant::class);

// With a custom query
ModelScore::calculateForAll(Tenant::class, query: Tenant::where('is_active', true));
```

### Query Results

```php
// Detailed breakdown per task
$breakdown = $tenant->scoreBreakdown();

// Checklist items (for wizard UI)
$checklist = $tenant->scoreChecklist();
```

## Calculator Helpers

`BaseCalculator` provides helpers for common scoring patterns:

```php
// All-or-nothing (boolean check)
return $this->binaryScore($condition, $maxPoints);

// Proportional (0.0–1.0 ratio)
return $this->proportionalScore($ratio, $maxPoints);

// Inverse (lower ratio = higher score)
return $this->inverseScore($ratio, $threshold, $maxPoints);

// Tiered thresholds
return $this->tieredScore($value, [
    0 => 0.0,     // 0+ items = 0%
    5 => 0.25,    // 5+ items = 25%
    10 => 0.50,   // 10+ items = 50%
    25 => 0.75,   // 25+ items = 75%
    50 => 1.0,    // 50+ items = 100%
], $maxPoints);
```

### Calculator Examples

#### Proportional — Reviews count

```php
class ReviewsCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $count = $scoreable->reviews()->count();

        return $this->proportionalScore(min(1, $count / 10), $maxPoints);
    }
}
```

#### Inverse — Cancellation rate (lower is better)

```php
class CancellationRateCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $total = $scoreable->bookings()->count();
        $cancelled = $scoreable->bookings()->cancelled()->count();
        $rate = $total > 0 ? $cancelled / $total : 0;

        return $this->inverseScore($rate, 0.20, $maxPoints);
    }
}
```

#### Tiered — Gallery images

```php
class GalleryImagesCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        return $this->tieredScore($scoreable->galleryImages()->count(), [
            0 => 0.0, 3 => 0.25, 5 => 0.50, 10 => 0.75, 20 => 1.0,
        ], $maxPoints);
    }
}
```

Every calculator must return `['score' => int, 'metadata' => array]`. The helpers handle this for you.

---

## Task Groups

Organize tasks into logical groups:

```php
use Aftandilmmd\LaravelModelScores\Models\ModelScoreTaskGroup;

$group = ModelScoreTaskGroup::create([
    'key' => 'profile',
    'name' => 'Profile Completion',
    'icon' => 'user',
    'order_column' => 1,
]);

ModelScoreTask::create([
    'key' => 'profile_photo',
    'name' => 'Upload Profile Photo',
    'group_id' => $group->id,
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
]);
```

---

## Weighted Scoring

Give tasks different weights. Enable in config:

```php
'features' => ['weights' => true],
```

```php
ModelScoreTask::create([
    'key' => 'reviews',
    'name' => 'Customer Reviews',
    'calculator' => ReviewsCalculator::class,
    'type' => 'periodic',
    'max_points' => 100,
    'weight' => 1.50, // 1.5x multiplier → up to 150 points
]);
```

---

## Score Decay

Periodic task scores gradually decrease if not recalculated:

```php
'features' => ['decay' => true],
'decay' => ['strategy' => 'linear'], // or 'exponential'
```

```php
ModelScoreTask::create([
    'key' => 'returning_customers',
    'calculator' => ReturningCustomersCalculator::class,
    'type' => 'periodic',
    'max_points' => 100,
    'decay_days' => 30, // starts decaying after 30 days
]);
```

- **Linear**: 2% decrease per day after decay period
- **Exponential**: multiplied by 0.95 each day after decay period

---

## Badge System

Define badges assigned automatically based on score ranges:

```php
use Aftandilmmd\LaravelModelScores\Models\ModelScoreBadge;

ModelScoreBadge::create(['key' => 'bronze', 'name' => 'Bronze', 'min_score' => 100, 'max_score' => 299, 'color' => '#CD7F32']);
ModelScoreBadge::create(['key' => 'silver', 'name' => 'Silver', 'min_score' => 300, 'max_score' => 599, 'color' => '#C0C0C0']);
ModelScoreBadge::create(['key' => 'gold',   'name' => 'Gold',   'min_score' => 600, 'max_score' => null, 'color' => '#FFD700']);
```

`BadgeEarned` and `BadgeLost` events fire automatically when badge changes.

---

## Score Profiles

Score a model on multiple dimensions independently:

```php
ModelScoreTask::create([
    'key' => 'profile_photo',
    'calculator' => ProfilePhotoCalculator::class,
    'max_points' => 50,
    'type' => 'static',
    'profile' => 'quality',
]);

$tenant->calculateScore(profile: 'quality');
$badge = $tenant->scoreBadge(profile: 'quality');
```

---

## Event Sourcing & History

Every score change is logged as an event record:

```php
'features' => ['event_log' => true],
```

```php
// Daily totals (for charts)
$history = ModelScore::getScoreHistory($tenant, days: 30);

// Full event timeline
$timeline = ModelScore::getScoreTimeline($tenant, limit: 50);

// Filter by event type
$timeline = ModelScore::getScoreTimeline($tenant, eventType: 'badge_earned');
```

Event types: `task_score_changed`, `adjustment_added`, `adjustment_expired`, `adjustment_revoked`, `badge_earned`, `badge_lost`, `recalculated`

---

## Threshold Notifications

Fire events when scores cross configured thresholds:

```php
'thresholds' => [
    'score_rose_above' => [500, 750, 900],
    'score_dropped_below' => [200, 100],
],
```

Listen to `ScoreThresholdCrossed`:

```php
Event::listen(ScoreThresholdCrossed::class, function ($event) {
    // $event->scoreable, $event->threshold, $event->direction ('up' or 'down')
});
```

---

## Events

| Event | When |
| ----- | ---- |
| `ScoresCalculated` | After all tasks are calculated for a model |
| `TaskScoreUpdated` | When a single task score changes |
| `BadgeEarned` | When score reaches a new badge level |
| `BadgeLost` | When score drops below a badge level |
| `ScoreThresholdCrossed` | When configured threshold is crossed |
| `ManualAdjustmentApplied` | When a manual adjustment is added |

---

## Artisan Commands

```bash
# Calculate scores for all models
php artisan model-scores:calculate "App\Models\Tenant"

# Calculate for a specific model
php artisan model-scores:calculate "App\Models\Tenant" --id=1

# Only static or periodic tasks
php artisan model-scores:calculate "App\Models\Tenant" --type=static

# Use a specific profile
php artisan model-scores:calculate "App\Models\Tenant" --profile=quality

# Prune old event logs
php artisan model-scores:prune-events --days=365
```

---

## Livewire Components (Optional)

Enable in config with `model-scores.livewire.enabled = true`:

```blade
<livewire:model-scores-checklist :scoreable="$tenant" />
<livewire:model-scores-progress-bar :scoreable="$tenant" />
<livewire:model-scores-breakdown-chart :scoreable="$tenant" />
```

---

## REST API (Optional)

Enable in config with `model-scores.api.enabled = true`:

| Method | Endpoint | Description |
| ------ | -------- | ----------- |
| GET | `/api/model-scores/tasks` | List all tasks |
| GET | `/api/model-scores/{type}/{id}/breakdown` | Score breakdown |
| GET | `/api/model-scores/{type}/{id}/history` | Score history |
| POST | `/api/model-scores/{type}/{id}/adjustments` | Add adjustment |
| DELETE | `/api/model-scores/adjustments/{id}` | Revoke adjustment |

---

## Configuration Reference

All features can be toggled in `config/model-scores.php`:

```php
'features' => [
    'badges' => true,
    'event_log' => true,
    'decay' => true,
    'adjustments' => true,
    'weights' => true,
    'groups' => true,
    'thresholds' => true,
],
```

| Key | Default | Description |
| --- | ------- | ----------- |
| `score_column` | `null` | Column name on model for caching total score. `null` = compute from DB |
| `decay.strategy` | `'linear'` | `'linear'` or `'exponential'` |
| `event_log.retention_days` | `365` | Days to keep event logs |
| `api.enabled` | `false` | Enable REST API endpoints |
| `livewire.enabled` | `true` | Register Livewire components |

---

## Database Tables

The package creates 6 tables (all customizable via config):

| Table | Purpose |
| ----- | ------- |
| `model_scores_task_groups` | Task group definitions |
| `model_scores_tasks` | Task definitions with calculator FQCN |
| `model_scores_scores` | Per-model task score results |
| `model_scores_score_events` | Event-level score history |
| `model_scores_badges` | Badge/level definitions |
| `model_scores_adjustments` | Manual point adjustments |

## License

MIT License. See [LICENSE](LICENSE) for details.
