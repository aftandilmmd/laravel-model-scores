# Laravel Model Scores

Laravel modelleri için esnek bir puanlama sistemi. Herhangi bir Eloquent modeline puan, ceza ve rozet ekleyin — kurulum gerektirmez. Hazır olduğunuzda hesaplayıcılar, görev grupları, puan azalması, event sourcing ve daha fazlasıyla derinleşin.

**[English (EN)](README.md)** | **[Azerbaijani (AZ)](README.az.md)**

## Neden Bu Paket?

Çoğu Laravel uygulaması bir noktada bir şeyleri puanlamak, sıralamak veya derecelendirmek zorunda kalır — bir satıcının güvenilirliği, bir kullanıcının profil tamlığı, bir ilanın kalitesi. Birkaç `if` ifadesiyle başlarsınız, sonra ağırlıklar eklersiniz, sonra geçmiş takibi gerekir, sonra birisi rozet ister. Kısa sürede puanlama mantığı kod tabanınıza dağılmış, denetim izi olmayan ve tutarsız bir hale gelir.

Laravel Model Scores bunu yapısal bir şekilde çözer. Her puanlama kriterini izole bir hesaplayıcı sınıf olarak tanımlayın, mantıksal gruplara ayırın, ağırlıklar atayın ve gerisini pakete bırakın — rozet geçişleri, event sourcing, puan azalması ve toplu yeniden hesaplama dahil.

**Yaygın kullanım alanları:**

- **Pazar yeri kalite puanları** — Satıcıları profil tamlığı, yanıt oranları, yorumlar ve teslimat metriklerine göre puanlayın. Airbnb Superhost veya Etsy Star Seller mantığı.
- **Profil tamamlama** — Kullanıcıları checklist ve ilerleme çubuğuyla profillerini tamamlamaya yönlendirin. Her eksik alan bir puanlama görevidir.
- **Oyunlaştırma ve sadakat seviyeleri** — Etkileşim, satın alma veya içerik üretimine puan verin. Puan aralıklarına göre Bronz/Gümüş/Altın rozetleri otomatik atayın.
- **Uyumluluk puanlama** — Kuruluşları güvenlik denetimleri, mevzuat uyumu veya süreç tamamlama durumuna göre puanlayın. Decay özelliği güncelliğini yitiren uyumluluğu zamanla düşürür.
- **İçerik ve ilan kalitesi** — Ürünleri veya makaleleri veri tamlığı, görsel sayısı ve açıklama kalitesine göre puanlayın. Puanları arama sıralamasında kullanın.

**Ne zaman kullanmalısınız:**

- Birden fazla bağımsız puanlama kriteriniz var
- Kriterler farklı mantık kullanıyor (boolean kontrol, orantılı metrik, ters oran, kademeli eşik)
- Puan değişikliklerinin denetim izine ihtiyacınız var
- Rozetlerin veya seviyelerin otomatik güncellenmesini istiyorsunuz
- Bazı metriklerin yenilenmezse zamanla azalması gerekiyor

**Ne zaman muhtemelen gerekmez:**

- Tek bir tamsayı sayacı yeterli (sadece bir kolon kullanın)
- Sadece kullanıcıların verdiği yıldız puanlarına ihtiyacınız var (bir yorum paketi kullanın)
- "Puanınız" geçmiş kaydı gerektirmeyen tek bir hesaplanan değer

## Gereksinimler

- PHP 8.2+
- Laravel 11 veya 12

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

## Hızlı Başlangıç

### 1. Trait Ekleyin

```php
use Aftandilmmd\LaravelModelScores\Traits\HasModelScores;

class Tenant extends Model
{
    use HasModelScores;
}
```

### 2. Puan Ekleyin ve Çıkarın

```php
// Bonus puan ekle (opsiyonel son kullanma ile)
$tenant->addScoreBonus(20, 'Hoşgeldin bonusu', now()->addDays(30));

// Ceza puanı ekle
$tenant->addScorePenalty(10, 'Geç ödeme');

// Toplam puanı oku
$total = ModelScore::getTotalScore($tenant);
```

### 3. Mevcut Rozeti Al

```php
$badge = $tenant->scoreBadge();
// $badge->name, $badge->color, $badge->icon
```

### 4. Puan Geçmişini Görüntüle

```php
$history = $tenant->scoreHistory(days: 30);
// [{ date, total, previous_total, change }, ...]
```

Temel kullanım bu kadar — bonus/ceza, rozetler, geçmiş. Hesaplayıcı veya görev tanımlamaya gerek yok.

### Facade Kullanımı

`ModelScore` facade'ı aynı yetenekleri artı toplu işlemleri sunar:

```php
use Aftandilmmd\LaravelModelScores\Facades\ModelScore;

// Facade ile ayarlama ekle
ModelScore::addAdjustment($tenant, 20, 'bonus', 'Hoşgeldin bonusu', now()->addDays(30));

// Ayarlamayı iptal et
ModelScore::revokeAdjustment($adjustment);

// Ayarlamaları sorgula
$active = ModelScore::getActiveAdjustments($tenant);
$total = ModelScore::getAdjustmentsTotal($tenant);

// Tüm mevcut rozetleri al
$badges = ModelScore::getAvailableBadges();
```

### Puanı Modelde Önbelleğe Alma (Opsiyonel)

Varsayılan olarak toplam puan her okumada veritabanından hesaplanır. Daha hızlı erişim için modelinizin tablosunda önbelleğe alabilirsiniz:

1. Migration'ınıza kolon ekleyin:

```php
$table->unsignedInteger('model_score')->default(0);
```

2. Config'de belirtin:

```php
// config/model-scores.php
'score_column' => 'model_score',
```

Artık `$tenant->model_score` her zaman mevcut ve otomatik güncellenir.

---

## Gelişmiş Kullanım

Aşağıdakilerin hepsi opsiyoneldir — ihtiyacınız olanı kullanın.

### Hesaplayıcı Sistemi

Manuel bonus/ceza yerine, modellerinizi otomatik değerlendiren **hesaplayıcılı puanlama görevleri** tanımlayın.

#### Hesaplayıcı Oluşturun

Her puanlama kriteri kendi hesaplayıcı sınıfına sahiptir:

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

#### Görevleri Kaydedin

Puanlanan kriterleri seeder veya migration ile tanımlayın:

```php
use Aftandilmmd\LaravelModelScores\Models\ModelScoreTask;

ModelScoreTask::create([
    'key' => 'profile_photo',
    'name' => 'Profil Fotoğrafı Yükle',
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
]);
```

#### Puanları Hesaplayın

```php
// Bir model için tüm görevleri hesapla
$tenant->calculateScore();

// Sadece statik veya periyodik görevler
$tenant->calculateScore('static');

// Tüm modeller için hesapla (100'lük gruplar halinde)
ModelScore::calculateForAll(Tenant::class);

// Özel sorgu ile
ModelScore::calculateForAll(Tenant::class, query: Tenant::where('is_active', true));
```

#### Sonuçları Sorgulayın

```php
// Görev bazlı detaylı döküm
$breakdown = $tenant->scoreBreakdown();

// Checklist öğeleri (sihirbaz arayüzü için)
$checklist = $tenant->scoreChecklist();
```

### Hesaplayıcı Yardımcıları

`BaseCalculator` yaygın puanlama kalıpları için yardımcı metodlar sunar:

```php
// Ya hep ya hiç (boolean kontrol)
return $this->binaryScore($condition, $maxPoints);

// Orantılı (0.0–1.0 oran)
return $this->proportionalScore($ratio, $maxPoints);

// Ters orantı (düşük oran = yüksek puan)
return $this->inverseScore($ratio, $threshold, $maxPoints);

// Kademeli eşikler
return $this->tieredScore($value, [
    0 => 0.0, 5 => 0.25, 10 => 0.50, 25 => 0.75, 50 => 1.0,
], $maxPoints);
```

#### Hesaplayıcı Örnekleri

**Orantılı — Yorum sayısı**

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

**Ters Orantı — İptal oranı (düşük daha iyi)**

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

**Kademeli — Galeri görsel sayısı**

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

Her hesaplayıcı `['score' => int, 'metadata' => array]` döndürmelidir. Yardımcı metodlar bunu sizin için halleder.

---

### Görev Grupları

Görevleri mantıksal gruplara ayırın:

```php
use Aftandilmmd\LaravelModelScores\Models\ModelScoreTaskGroup;

$group = ModelScoreTaskGroup::create([
    'key' => 'profile',
    'name' => 'Profil Tamamlama',
    'icon' => 'user',
    'order_column' => 1,
]);

ModelScoreTask::create([
    'key' => 'profile_photo',
    'name' => 'Profil Fotoğrafı Yükle',
    'group_id' => $group->id,
    'calculator' => ProfilePhotoCalculator::class,
    'type' => 'static',
    'max_points' => 50,
]);
```

---

### Ağırlıklı Puanlama

Görevlere farklı ağırlıklar verin:

```php
'features' => ['weights' => true],
```

```php
ModelScoreTask::create([
    'key' => 'reviews',
    'name' => 'Müşteri Yorumları',
    'calculator' => ReviewsCalculator::class,
    'type' => 'periodic',
    'max_points' => 100,
    'weight' => 1.50, // 1.5x çarpan → en fazla 150 puan
]);
```

---

### Puan Azalması (Decay)

Periyodik görev puanları yeniden hesaplanmazsa zamanla azalır:

```php
'features' => ['decay' => true],
'decay' => ['strategy' => 'linear'], // veya 'exponential'
```

```php
ModelScoreTask::create([
    'key' => 'returning_customers',
    'calculator' => ReturningCustomersCalculator::class,
    'type' => 'periodic',
    'max_points' => 100,
    'decay_days' => 30, // 30 gün sonra azalmaya başlar
]);
```

- **Linear**: Azalma süresinden sonra günde %2 azalır
- **Exponential**: Azalma süresinden sonra her gün 0.95 ile çarpılır

---

### Rozet Sistemi

Puan aralıklarına göre otomatik atanan rozetler:

```php
use Aftandilmmd\LaravelModelScores\Models\ModelScoreBadge;

ModelScoreBadge::create(['key' => 'bronze', 'name' => 'Bronz', 'min_score' => 100, 'max_score' => 299, 'color' => '#CD7F32']);
ModelScoreBadge::create(['key' => 'silver', 'name' => 'Gümüş', 'min_score' => 300, 'max_score' => 599, 'color' => '#C0C0C0']);
ModelScoreBadge::create(['key' => 'gold',   'name' => 'Altın', 'min_score' => 600, 'max_score' => null, 'color' => '#FFD700']);
```

Puan hesaplaması sırasında rozet değiştiğinde `BadgeEarned` ve `BadgeLost` event'leri otomatik tetiklenir.

---

### Puan Profilleri

Bir modeli birden fazla boyutta bağımsız olarak puanlayın:

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

### Event Sourcing ve Geçmiş

Her puan değişikliği event kaydı olarak tutulur:

```php
'features' => ['event_log' => true],
```

```php
// Günlük toplamlar (grafikler için)
$history = ModelScore::getScoreHistory($tenant, days: 30);

// Tam event zaman çizelgesi
$timeline = ModelScore::getScoreTimeline($tenant, limit: 50);

// Event tipine göre filtrele
$timeline = ModelScore::getScoreTimeline($tenant, eventType: 'badge_earned');
```

Event tipleri: `task_score_changed`, `adjustment_added`, `adjustment_expired`, `adjustment_revoked`, `badge_earned`, `badge_lost`, `recalculated`

---

### Eşik Bildirimleri

Puanlar yapılandırılmış eşikleri geçtiğinde event tetikleyin:

```php
'thresholds' => [
    'score_rose_above' => [500, 750, 900],
    'score_dropped_below' => [200, 100],
],
```

```php
Event::listen(ScoreThresholdCrossed::class, function ($event) {
    // $event->scoreable, $event->threshold, $event->direction ('up' veya 'down')
});
```

---

## Event'ler

| Event | Ne Zaman |
| ----- | -------- |
| `ScoresCalculated` | Bir model için tüm görevler hesaplandıktan sonra |
| `TaskScoreUpdated` | Tek bir görev puanı değiştiğinde |
| `BadgeEarned` | Puan yeni bir rozet seviyesine ulaştığında |
| `BadgeLost` | Puan rozet seviyesinin altına düştüğünde |
| `ScoreThresholdCrossed` | Yapılandırılmış eşik geçildiğinde |
| `ManualAdjustmentApplied` | Manuel ayarlama eklendiğinde |

---

## Artisan Komutları

```bash
# Tüm modeller için puanları hesapla
php artisan model-scores:calculate "App\Models\Tenant"

# Belirli bir model için hesapla
php artisan model-scores:calculate "App\Models\Tenant" --id=1

# Sadece statik veya periyodik görevler
php artisan model-scores:calculate "App\Models\Tenant" --type=static

# Belirli bir profil kullan
php artisan model-scores:calculate "App\Models\Tenant" --profile=quality

# Eski event kayıtlarını temizle
php artisan model-scores:prune-events --days=365
```

---

## Livewire Bileşenleri (Opsiyonel)

Config'den `model-scores.livewire.enabled = true` ile aktifleştirin:

```blade
<livewire:model-scores-checklist :scoreable="$tenant" />
<livewire:model-scores-progress-bar :scoreable="$tenant" />
<livewire:model-scores-breakdown-chart :scoreable="$tenant" />
```

---

## REST API (Opsiyonel)

Config'den `model-scores.api.enabled = true` ile aktifleştirin:

| Metod | Endpoint | Açıklama |
| ----- | -------- | -------- |
| GET | `/api/model-scores/tasks` | Görev listesi |
| GET | `/api/model-scores/{type}/{id}/breakdown` | Puan dökümü |
| GET | `/api/model-scores/{type}/{id}/history` | Puan geçmişi |
| POST | `/api/model-scores/{type}/{id}/adjustments` | Ayarlama ekle |
| DELETE | `/api/model-scores/adjustments/{id}` | Ayarlamayı iptal et |

---

## Yapılandırma Referansı

Tüm özellikler `config/model-scores.php`'den açılıp kapatılabilir:

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

| Anahtar | Varsayılan | Açıklama |
| ------- | ---------- | -------- |
| `score_column` | `null` | Modelde toplam puanı önbelleğe alan kolon. `null` = DB'den hesapla |
| `decay.strategy` | `'linear'` | `'linear'` veya `'exponential'` |
| `event_log.retention_days` | `365` | Event kayıtlarını saklama süresi (gün) |
| `api.enabled` | `false` | REST API endpoint'lerini aktifleştir |
| `livewire.enabled` | `true` | Livewire bileşenlerini kaydet |

---

## Veritabanı Tabloları

Paket 6 tablo oluşturur (hepsi config üzerinden özelleştirilebilir):

| Tablo | Amaç |
| ----- | ---- |
| `model_scores_task_groups` | Görev grubu tanımları |
| `model_scores_tasks` | Hesaplayıcı FQCN'li görev tanımları |
| `model_scores_scores` | Model başına görev puan sonuçları |
| `model_scores_score_events` | Event seviyesinde puan geçmişi |
| `model_scores_badges` | Rozet/seviye tanımları |
| `model_scores_adjustments` | Manuel puan ayarlamaları |

## Lisans

MIT Lisansı. Detaylar için [LICENSE](LICENSE) dosyasına bakın.
