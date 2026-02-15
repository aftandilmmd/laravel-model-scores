# Laravel Model Scores

Laravel modelləri üçün çevik, çoxölçülü keyfiyyət xallandırma sistemi. Xallandırma tapşırıqları təyin edin, kalkulyatorlar atayın, event sourcing ilə xal tarixçəsini izləyin, nişanları idarə edin və əl ilə xal düzəlişləri edin — hamısı təmiz və genişlənə bilən bir paket ilə.

**[English (EN)](README.md)** | **[Turkish (TR)](README.tr.md)**

## Tələblər

- PHP 8.2+
- Laravel 11 və ya 12

## Xüsusiyyətlər

- **Strategy Pattern Kalkulyatorlar** — Hər xallandırma tapşırığının öz kalkulyator sinfi var
- **Polimorfik Xallandırma** — İstənilən Eloquent modeli xallandırıla bilər (User, Tenant, Company və s.)
- **Tapşırıq Qrupları** — Tapşırıqları məntiqi qruplara ayırın (Profil, Performans və s.)
- **Çəkili Xallandırma** — Tapşırıqların fərqli çəkiləri ola bilər (0.50x-dən 2.00x-ə qədər)
- **Xal Azalması (Decay)** — Dövri tapşırıq xalları zamanla tədricən azalır
- **Nişan Sistemi** — Xal hədlərinə görə avtomatik nişan ataması
- **Event Sourcing** — Hər xal dəyişikliyi ayrı bir event qeydi olaraq saxlanılır
- **Əl ilə Düzəlişlər** — Müddətli və ya daimi bonus/cərimə xalları əlavə edin
- **Xal Profilləri** — Model başına birdən çox xal növü (quality, trust, engagement)
- **Hədd Bildirişləri** — Xallar konfiqurasiya edilmiş hədləri keçdikdə event dispatch edilir
- **Livewire Komponentləri** — Opsional checklist, irəliləyiş çubuğu və qrafik widget-ları
- **REST API** — Xalları və tapşırıqları idarə etmək üçün opsional API endpoint-ləri
- **Artisan Əmrləri** — Toplu xal hesablama, köhnə event-ləri təmizləmə

## Quraşdırma

```bash
composer require aftandilmmd/laravel-model-scores
```

Konfiqurasiya və migration-ları dərc edin:

```bash
php artisan vendor:publish --tag=model-scores-config
php artisan vendor:publish --tag=model-scores-migrations
php artisan migrate
```

## Konfiqurasiya

Paket `config/model-scores.php` vasitəsilə hərtərəfli konfiqurasiya edilə bilər:

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

## İstifadə

### 1. Modelə Trait Əlavə Edin

```php
use Aftandilmmd\LaravelModelScores\Traits\HasQualityScores;

class Tenant extends Model
{
    use HasQualityScores;
}
```

### 2. Kalkulyator Yaradın

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

### 3. Tapşırıqları Verilənlər Bazasına Əlavə Edin

```php
QualityTask::create([
    'key' => 'profile_photo',
    'name' => 'Profil Şəkli Yüklə',
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
    'weight' => 1.00,
]);
```

### 4. Xalları Hesablayın

```php
use Aftandilmmd\LaravelModelScores\Facades\ModelScores;

// Tək bir model üçün hesabla
$totalXal = ModelScores::calculateFor($tenant);

// Bütün modellər üçün hesabla
ModelScores::calculateForAll(Tenant::class);

// Və ya trait vasitəsilə
$tenant->calculateQualityScore();
```

### 5. Xalları Sorğulayın

```php
// Ətraflı dökümü alın
$breakdown = ModelScores::getBreakdown($tenant);

// Checklist elementlərini alın (sehrbaz interfeysi üçün)
$checklist = ModelScores::getChecklistItems($tenant);

// Cari nişanı alın
$badge = ModelScores::getCurrentBadge($tenant);

// Xal tarixçəsini alın (event sourcing)
$history = ModelScores::getScoreHistory($tenant, 'default', 30);

// Bütün event-lərin zaman xətti
$timeline = ModelScores::getScoreTimeline($tenant);
```

### 6. Əl ilə Düzəlişlər

```php
// Bonus xal əlavə edin (opsional son istifadə tarixi ilə)
ModelScores::addAdjustment($tenant, 20, 'bonus', 'Xoş gəldin bonusu', now()->addDays(30));

// Cərimə xalı əlavə edin
ModelScores::addAdjustment($tenant, -10, 'penalty', 'Gecikmiş ödəniş');

// Trait qısa yolları ilə
$tenant->addQualityBonus(20, 'Xoş gəldin bonusu', now()->addDays(30));
$tenant->addQualityPenalty(10, 'Gecikmiş ödəniş');

// Düzəlişi ləğv edin
ModelScores::revokeAdjustment($adjustment);
```

## BaseCalculator Köməkçi Metodları

`BaseCalculator` ümumi xallandırma nümunələri üçün köməkçi metodlar təqdim edir:

| Metod | Təsvir |
|-------|--------|
| `binaryScore($condition, $maxPoints)` | Boolean-a görə ya hamısı ya heç nə |
| `proportionalScore($ratio, $maxPoints)` | 0.0–1.0 nisbətinə görə mütənasib xal |
| `inverseScore($ratio, $threshold, $maxPoints)` | Aşağı nisbət = yüksək xal |
| `tieredScore($value, $tiers, $maxPoints)` | Pilləli hədlər və nisbətlər |
| `applyDecay($score, $decayDays, $calculatedAt)` | Zamanla tədricən xal azalması |

## Artisan Əmrləri

```bash
# Bütün modellər üçün xalları hesablayın
php artisan model-scores:calculate "App\Models\Tenant"

# Müəyyən bir model üçün hesablayın
php artisan model-scores:calculate "App\Models\Tenant" --id=1

# Yalnız statik və ya dövri tapşırıqlar
php artisan model-scores:calculate "App\Models\Tenant" --type=static

# Köhnə event qeydlərini təmizləyin
php artisan model-scores:prune-events --days=365
```

## Event-lər

| Event | Nə Zaman |
|-------|----------|
| `ScoresCalculated` | Bütün tapşırıqlar hesablandıqdan sonra |
| `TaskScoreUpdated` | Tək bir tapşırıq xalı dəyişdikdə |
| `BadgeEarned` | Xal yeni bir nişan səviyyəsinə çatdıqda |
| `BadgeLost` | Xal nişan səviyyəsinin altına düşdükdə |
| `ScoreThresholdCrossed` | Konfiqurasiya edilmiş hədd keçildikdə |
| `ManualAdjustmentApplied` | Əl ilə düzəliş əlavə edildikdə |

## Livewire Komponentləri (Opsional)

Konfiqurasiyadan `model-scores.livewire.enabled = true` ilə aktivləşdirin:

```blade
{{-- Xal checklist (sehrbaz üslubu) --}}
<livewire:model-scores-checklist :scoreable="$tenant" />

{{-- Nişanlı irəliləyiş çubuğu --}}
<livewire:model-scores-progress-bar :scoreable="$tenant" />

{{-- Dağılım qrafiki (ApexCharts) --}}
<livewire:model-scores-breakdown-chart :scoreable="$tenant" />
```

## REST API (Opsional)

Konfiqurasiyadan `model-scores.api.enabled = true` ilə aktivləşdirin:

| Metod | Endpoint | Təsvir |
|-------|----------|--------|
| GET | `/api/model-scores/tasks` | Tapşırıq siyahısı |
| GET | `/api/model-scores/{type}/{id}/breakdown` | Xal dökümü |
| GET | `/api/model-scores/{type}/{id}/history` | Xal tarixçəsi |
| POST | `/api/model-scores/{type}/{id}/adjustments` | Düzəliş əlavə et |
| DELETE | `/api/model-scores/adjustments/{id}` | Düzəlişi ləğv et |

## Verilənlər Bazası Cədvəlləri

Paket 6 cədvəl yaradır (hamısı konfiqurasiya vasitəsilə fərdiləşdirilə bilər):

- `model_scores_task_groups` — Tapşırıq qrupu tərifləri
- `model_scores_tasks` — Kalkulyator FQCN-li tapşırıq tərifləri
- `model_scores_scores` — Model başına tapşırıq xal nəticələri
- `model_scores_score_events` — Event səviyyəsində xal tarixçəsi
- `model_scores_badges` — Nişan/səviyyə tərifləri
- `model_scores_adjustments` — Əl ilə xal düzəlişləri

## Lisenziya

MIT Lisenziyası. Ətraflı məlumat üçün [LICENSE](LICENSE) faylına baxın.
