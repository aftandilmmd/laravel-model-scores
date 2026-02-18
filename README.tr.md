# Laravel Model Scores

![Business Score](screenshots/business-score.png)

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

### Hesaplayıcı Referansı

`BaseCalculator` yaygın puanlama kalıpları için dört yardımcı metod sunar. Her yardımcı aynı yapıyı döndürür:

```php
['score' => int, 'metadata' => array]
```

Herhangi bir yardımcıya özel `$metadata` da geçirebilirsiniz — puan ile birlikte hata ayıklama veya görüntüleme amacıyla saklanır.

---

#### `binaryScore` — Ya Hep Ya Hiç

Koşul sağlandığında tam puan, aksi halde sıfır verir. "Profil fotoğrafı var mı" veya "e-posta doğrulanmış mı" gibi evet/hayır kontrolleri için kullanın.

```php
$this->binaryScore(bool $condition, int $maxPoints, array $metadata = []): array
```

| Parametre | Tip | Açıklama |
| --------- | --- | -------- |
| `$condition` | `bool` | Değerlendirilecek kontrol |
| `$maxPoints` | `int` | `true` olduğunda verilen puan |

**Formül:** `$condition ? $maxPoints : 0`

**Örnek — Host kimlik doğrulaması (Airbnb Superhost):**

```php
class HostIdentityVerifiedCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        return $this->binaryScore(
            ! empty($scoreable->identity_verified_at),
            $maxPoints,
            ['verified' => ! empty($scoreable->identity_verified_at)]
        );
    }
}
```

| Senaryo | maxPoints | Sonuç |
| ------- | --------- | ----- |
| Doğrulanmış | 50 | `['score' => 50, 'metadata' => ['verified' => true]]` |
| Doğrulanmamış | 50 | `['score' => 0, 'metadata' => ['verified' => false]]` |

---

#### `proportionalScore` — Doğrusal Oran

0.0 ile 1.0 arasındaki orana göre puan verir. Oran otomatik olarak sınırlanır — 0'ın altı 0, 1'in üstü 1 olur. Bir hedefe kadar "çok olan daha iyi" durumları için kullanın.

```php
$this->proportionalScore(float $ratio, int $maxPoints, array $metadata = []): array
```

| Parametre | Tip | Açıklama |
| --------- | --- | -------- |
| `$ratio` | `float` | 0.0–1.0 arası değer (otomatik sınırlanır) |
| `$maxPoints` | `int` | Elde edilebilecek maksimum puan |

**Formül:** `round(clamp($ratio, 0, 1) * $maxPoints)`

**Örnek — Host yanıt oranı (Airbnb Superhost için %90+ gerekli):**

```php
class ResponseRateCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $total = $scoreable->inquiries()->where('created_at', '>=', now()->subYear())->count();
        $responded = $scoreable->inquiries()->where('created_at', '>=', now()->subYear())
            ->whereNotNull('responded_at')->count();

        $rate = $total > 0 ? $responded / $total : 1.0;

        return $this->proportionalScore($rate, $maxPoints, [
            'total_inquiries' => $total,
            'responded' => $responded,
            'response_rate' => round($rate * 100, 1),
        ]);
    }
}
```

| Yanıt Oranı | Oran | maxPoints | Puan |
| ------------ | ---- | --------- | ---- |
| %100 | 1.0 | 100 | 100 |
| %90 | 0.9 | 100 | 90 |
| %50 | 0.5 | 100 | 50 |
| %0 | 0.0 | 100 | 0 |

---

#### `inverseScore` — Düşük Olan Daha İyi

Oran düşük olduğunda yüksek puan verir. Puan, oranın 0 olduğu noktada `$maxPoints`'ten, oranın eşiğe ulaştığı noktada 0'a doğrusal olarak azalır. İptal oranı veya şikayet oranı gibi azın daha iyi olduğu metrikler için kullanın.

```php
$this->inverseScore(float $ratio, float $threshold, int $maxPoints, array $metadata = []): array
```

| Parametre | Tip | Açıklama |
| --------- | --- | -------- |
| `$ratio` | `float` | Mevcut oran (ör. %15 için 0.15) |
| `$threshold` | `float` | Puanın sıfır olacağı oran (ör. %20 için 0.20) |
| `$maxPoints` | `int` | Oran 0 olduğunda verilen puan |

**Formül:** `ratio >= threshold ? 0 : round((1 - ratio / threshold) * maxPoints)`

**Örnek — Host iptal oranı (Airbnb Superhost için <%1 gerekli):**

```php
class CancellationRateCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $total = $scoreable->reservations()->where('check_in', '>=', now()->subYear())->count();
        $cancelled = $scoreable->reservations()->where('check_in', '>=', now()->subYear())
            ->where('cancelled_by', 'host')->count();

        $rate = $total > 0 ? $cancelled / $total : 0;

        return $this->inverseScore($rate, 0.05, $maxPoints, [
            'total_reservations' => $total,
            'cancelled_by_host' => $cancelled,
            'cancellation_rate' => round($rate * 100, 2),
        ]);
    }
}
```

| İptal Oranı | Eşik | maxPoints | Puan |
| ----------- | ---- | --------- | ---- |
| %0 (0.00) | 0.05 | 100 | 100 |
| %1 (0.01) | 0.05 | 100 | 80 |
| %2.5 (0.025) | 0.05 | 100 | 50 |
| %4 (0.04) | 0.05 | 100 | 20 |
| %5+ (0.05) | 0.05 | 100 | 0 |

---

#### `tieredScore` — Kademeli Eşikler

Değerin hangi kademeye düştüğüne göre puan verir. Her kademe bir minimum değeri bir orana (0.0–1.0) eşler ve eşleşen en yüksek kademenin oranı `proportionalScore` ile kullanılır. "En az 5 fotoğraf yükle = %50" gibi adım bazlı puanlama için kullanın.

```php
$this->tieredScore(float $value, array $tiers, int $maxPoints, array $metadata = []): array
```

| Parametre | Tip | Açıklama |
| --------- | --- | -------- |
| `$value` | `float` | Ölçülen değer (ör. görsel sayısı) |
| `$tiers` | `array` | `[eşik => oran]` çiftleri, ör. `[0 => 0.0, 5 => 0.5, 10 => 1.0]` |
| `$maxPoints` | `int` | Elde edilebilecek maksimum puan |

**Formül:** `$value >= threshold` olan en yüksek kademeyi bul, oranını al, sonra `round(oran * maxPoints)`.

**Örnek — İlan fotoğrafları (Airbnb 20+ fotoğraf önerir):**

```php
class ListingPhotosCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        return $this->tieredScore($scoreable->photos()->count(), [
            0  => 0.0,   // Fotoğraf yok
            1  => 0.15,  // En az bir tane — ilan görünür
            5  => 0.35,  // Temel alan kapsamı
            10 => 0.60,  // İyi — her oda gösterilmiş
            15 => 0.80,  // Detaylı — olanaklar ve çevre
            20 => 1.0,   // Profesyonel seviye ilan
        ], $maxPoints);
    }
}
```

| Fotoğraf | Eşleşen Kademe | Oran | maxPoints | Puan |
| -------- | -------------- | ---- | --------- | ---- |
| 0 | `0 => 0.0` | 0.0 | 80 | 0 |
| 3 | `1 => 0.15` | 0.15 | 80 | 12 |
| 7 | `5 => 0.35` | 0.35 | 80 | 28 |
| 12 | `10 => 0.60` | 0.60 | 80 | 48 |
| 25 | `20 => 1.0` | 1.0 | 80 | 80 |

---

#### Doğru Hesaplayıcı Tipini Seçme

| Tip | En İyi Kullanım | Örnek |
| --- | --------------- | ----- |
| **Binary** | Evet/hayır koşulları | Kimlik doğrulanmış, e-posta onaylanmış, profil fotoğrafı yüklenmiş |
| **Proportional** | Hedefli "çok olan daha iyi" metrikler | Yanıt oranı (hedef %100), yorum puanı (hedef 4.8) |
| **Inverse** | Kesim noktalı "az olan daha iyi" metrikler | Host iptal oranı, şikayet oranı, iade oranı |
| **Tiered** | Adım bazlı başarılar | İlan fotoğrafları, olanak sayısı, tamamlanan konaklamalar |

#### Yardımcıları Birleştirme

Tek bir hesaplayıcı içinde doğru yardımcıyı seçmek için mantık kullanabilirsiniz:

```php
class ListingDescriptionCalculator extends BaseCalculator
{
    public function calculate(Model $scoreable, int $maxPoints, array $taskMetadata = []): array
    {
        $description = $scoreable->description ?? '';
        $length = mb_strlen(strip_tags($description));

        // Açıklama yok = binary başarısız
        if ($length === 0) {
            return $this->binaryScore(false, $maxPoints);
        }

        // Açıklama kalite kademelerine göre puanla
        return $this->tieredScore($length, [
            1   => 0.20,  // Bir şeyler var — hiç yoktan iyi
            50  => 0.40,  // Kısa — temelleri kapsıyor
            150 => 0.70,  // Detaylı — olanaklar ve kurallar belirtilmiş
            400 => 1.0,   // Kapsamlı — mahalle, ipuçları vs.
        ], $maxPoints);
    }
}
```

#### Metadata Kullanımı

Tüm yardımcılar opsiyonel `$metadata` dizisi kabul eder. Hata ayıklama bilgileri, ara değerler veya görüntüleme verileri saklamak için kullanın:

```php
return $this->proportionalScore($rate, $maxPoints, [
    'total_inquiries' => $total,
    'responded' => $responded,
    'response_rate' => 85.0,
]);
// Döndürür: ['score' => 85, 'metadata' => ['total_inquiries' => 20, 'responded' => 17, 'response_rate' => 85.0]]
```

Metadata, `model_scores_scores` tablosunda saklanır ve `$host->scoreBreakdown()` ile erişilebilir.

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

![Tasks List](screenshots/tasks-list.png)

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
