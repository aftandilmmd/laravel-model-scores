# Laravel Model Scores

Laravel modelleri için esnek, çok boyutlu kalite puanlama sistemi. Puanlama görevleri tanımlayın, hesaplayıcılar atayın, event sourcing ile puan geçmişini takip edin, rozetleri yönetin ve manuel puan ayarlamaları yapın — hepsi temiz ve genişletilebilir bir paket ile.

**[English (EN)](README.md)** | **[Azerbaijani (AZ)](README.az.md)**

## Gereksinimler

- PHP 8.2+
- Laravel 11 veya 12

## Özellikler

- **Strategy Pattern Hesaplayıcılar** — Her puanlama görevi kendi hesaplayıcı sınıfına sahip
- **Polimorfik Puanlama** — Herhangi bir Eloquent modeli puanlanabilir (User, Tenant, Company vb.)
- **Görev Grupları** — Görevleri mantıksal gruplara ayırın (Profil, Performans vb.)
- **Ağırlıklı Puanlama** — Görevler farklı ağırlıklara sahip olabilir (0.50x ile 2.00x arası)
- **Puan Azalması (Decay)** — Periyodik görev puanları zamanla kademeli olarak azalır
- **Rozet Sistemi** — Puan eşiklerine göre otomatik rozet ataması
- **Event Sourcing** — Her puan değişikliği ayrı bir event kaydı olarak tutulur
- **Manuel Ayarlamalar** — Süreli veya kalıcı bonus/ceza puanları ekleyin
- **Puan Profilleri** — Model başına birden fazla puan tipi (quality, trust, engagement)
- **Eşik Bildirimleri** — Puanlar yapılandırılmış eşikleri geçtiğinde event dispatch edilir
- **Livewire Bileşenleri** — Opsiyonel checklist, ilerleme çubuğu ve grafik widget'ları
- **REST API** — Puanları ve görevleri yönetmek için opsiyonel API endpoint'leri
- **Artisan Komutları** — Toplu puan hesaplama, eski event'leri temizleme

## Kurulum

```bash
composer require aftandilmmd/laravel-model-scores
```

Yapılandırma ve migration'ları yayınlayın:

```bash
php artisan vendor:publish --tag=model-scores-config
php artisan vendor:publish --tag=model-scores-migrations
php artisan migrate
```

## Yapılandırma

Paket `config/model-scores.php` üzerinden kapsamlı şekilde yapılandırılabilir:

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

## Kullanım

### 1. Modele Trait Ekleyin

```php
use Aftandilmmd\LaravelModelScores\Traits\HasQualityScores;

class Tenant extends Model
{
    use HasQualityScores;
}
```

### 2. Hesaplayıcı Oluşturun

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

### 3. Görevleri Veritabanına Ekleyin

```php
QualityTask::create([
    'key' => 'profile_photo',
    'name' => 'Profil Fotoğrafı Yükle',
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
    'weight' => 1.00,
]);
```

### 4. Puanları Hesaplayın

```php
use Aftandilmmd\LaravelModelScores\Facades\ModelScores;

// Tek bir model için hesapla
$toplamPuan = ModelScores::calculateFor($tenant);

// Tüm modeller için hesapla
ModelScores::calculateForAll(Tenant::class);

// Veya trait üzerinden
$tenant->calculateQualityScore();
```

### 5. Puanları Sorgulayın

```php
// Detaylı dökümü al
$breakdown = ModelScores::getBreakdown($tenant);

// Checklist öğelerini al (sihirbaz arayüzü için)
$checklist = ModelScores::getChecklistItems($tenant);

// Mevcut rozeti al
$badge = ModelScores::getCurrentBadge($tenant);

// Puan geçmişini al (event sourcing)
$history = ModelScores::getScoreHistory($tenant, 'default', 30);

// Tüm event'lerin zaman çizelgesi
$timeline = ModelScores::getScoreTimeline($tenant);
```

### 6. Manuel Ayarlamalar

```php
// Bonus puan ekle (opsiyonel son kullanma ile)
ModelScores::addAdjustment($tenant, 20, 'bonus', 'Hoşgeldin bonusu', now()->addDays(30));

// Ceza puanı ekle
ModelScores::addAdjustment($tenant, -10, 'penalty', 'Geç ödeme');

// Trait kısayolları ile
$tenant->addQualityBonus(20, 'Hoşgeldin bonusu', now()->addDays(30));
$tenant->addQualityPenalty(10, 'Geç ödeme');

// Ayarlamayı iptal et
ModelScores::revokeAdjustment($adjustment);
```

## BaseCalculator Yardımcı Metodları

`BaseCalculator` yaygın puanlama kalıpları için yardımcı metodlar sunar:

| Metod | Açıklama |
|-------|----------|
| `binaryScore($condition, $maxPoints)` | Boolean'a göre ya hep ya hiç |
| `proportionalScore($ratio, $maxPoints)` | 0.0–1.0 oranına göre orantılı puan |
| `inverseScore($ratio, $threshold, $maxPoints)` | Düşük oran = yüksek puan |
| `tieredScore($value, $tiers, $maxPoints)` | Kademeli eşikler ve oranlar |
| `applyDecay($score, $decayDays, $calculatedAt)` | Zamanla kademeli puan azalması |

## Artisan Komutları

```bash
# Tüm modeller için puanları hesapla
php artisan model-scores:calculate "App\Models\Tenant"

# Belirli bir model için hesapla
php artisan model-scores:calculate "App\Models\Tenant" --id=1

# Sadece statik veya periyodik görevler
php artisan model-scores:calculate "App\Models\Tenant" --type=static

# Eski event kayıtlarını temizle
php artisan model-scores:prune-events --days=365
```

## Event'ler

| Event | Ne Zaman |
|-------|----------|
| `ScoresCalculated` | Tüm görevler hesaplandıktan sonra |
| `TaskScoreUpdated` | Tek bir görev puanı değiştiğinde |
| `BadgeEarned` | Puan yeni bir rozet seviyesine ulaştığında |
| `BadgeLost` | Puan rozet seviyesinin altına düştüğünde |
| `ScoreThresholdCrossed` | Yapılandırılmış eşik geçildiğinde |
| `ManualAdjustmentApplied` | Manuel ayarlama eklendiğinde |

## Livewire Bileşenleri (Opsiyonel)

Config'den `model-scores.livewire.enabled = true` ile aktifleştirin:

```blade
{{-- Puan checklist (sihirbaz tarzı) --}}
<livewire:model-scores-checklist :scoreable="$tenant" />

{{-- Rozetli ilerleme çubuğu --}}
<livewire:model-scores-progress-bar :scoreable="$tenant" />

{{-- Dağılım grafiği (ApexCharts) --}}
<livewire:model-scores-breakdown-chart :scoreable="$tenant" />
```

## REST API (Opsiyonel)

Config'den `model-scores.api.enabled = true` ile aktifleştirin:

| Metod | Endpoint | Açıklama |
|-------|----------|----------|
| GET | `/api/model-scores/tasks` | Görev listesi |
| GET | `/api/model-scores/{type}/{id}/breakdown` | Puan dökümü |
| GET | `/api/model-scores/{type}/{id}/history` | Puan geçmişi |
| POST | `/api/model-scores/{type}/{id}/adjustments` | Ayarlama ekle |
| DELETE | `/api/model-scores/adjustments/{id}` | Ayarlamayı iptal et |

## Veritabanı Tabloları

Paket 6 tablo oluşturur (hepsi config üzerinden özelleştirilebilir):

- `model_scores_task_groups` — Görev grubu tanımları
- `model_scores_tasks` — Hesaplayıcı FQCN'li görev tanımları
- `model_scores_scores` — Model başına görev puan sonuçları
- `model_scores_score_events` — Event seviyesinde puan geçmişi
- `model_scores_badges` — Rozet/seviye tanımları
- `model_scores_adjustments` — Manuel puan ayarlamaları

## Lisans

MIT Lisansı. Detaylar için [LICENSE](LICENSE) dosyasına bakın.
