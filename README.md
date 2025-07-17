# Menu Content Crawler

Modern bir PHP tabanlı web scraper uygulaması. Restaurant menülerini ve yemek listelerini web sitelerinden otomatik olarak çeker ve farklı formatlarda export eder.

## Özellikler

- 🕷️ **Güçlü Web Scraping**: CSS seçiciler ile hassas veri çekme
- 🎯 **Akıllı Seçici Testi**: Seçicileri test etme ve önizleme
- 📊 **Çoklu Export Formatları**: JSON, CSV, Excel
- 🖥️ **Modern Web Arayüzü**: Bootstrap 5 ile responsive tasarım
- ⚡ **Hızlı ve Güvenilir**: Guzzle HTTP client ile optimize edilmiş istekler
- 🔧 **Kolay Konfigürasyon**: Popüler platformlar için hazır ayarlar

## Kurulum

### Gereksinimler

- PHP 7.4 veya üzeri
- Composer
- cURL extension
- DOM extension

### Adımlar

1. **Projeyi klonlayın:**
```bash
git clone <repository-url>
cd content-crawler
```

2. **Bağımlılıkları yükleyin:**
```bash
composer install
```

3. **Gerekli dizinleri oluşturun:**
```bash
mkdir -p exports logs assets/js
```

4. **Web sunucusunu başlatın:**
```bash
composer start
# veya
php -S localhost:8000
```

5. **Tarayıcıda açın:**
```
http://localhost:8000
```

## Kullanım

### 1. Temel Kullanım

1. **URL Girin**: Crawl etmek istediğiniz menü sayfasının URL'sini girin
2. **CSS Seçicileri Ayarlayın**: 
   - Menü konteyneri (örn: `.menu-container`)
   - Menü öğesi (örn: `.menu-item`)
   - Ürün adı (örn: `.product-name`)
   - Açıklama (örn: `.description`)
   - Fiyat (örn: `.price`)
   - Resim (örn: `img`)
3. **Test Edin**: "Seçicileri Test Et" butonu ile seçicilerinizi doğrulayın
4. **Veri Çekin**: "Veri Çek" butonu ile scraping işlemini başlatın
5. **Export Edin**: JSON, CSV veya Excel formatında veriyi indirin

### 2. CSS Seçici Örnekleri

```css
/* Menü konteyneri */
.menu-container
#menu-list
.food-items

/* Menü öğesi */
.menu-item
.food-item
.dish
li

/* Ürün adı */
.product-name
.dish-title
h3
.item-name

/* Açıklama */
.description
.product-desc
p
.details

/* Fiyat */
.price
.cost
.amount
.product-price

/* Resim */
img
.product-image img
.dish-photo img
```

### 3. Popüler Platformlar

Uygulama aşağıdaki platformlar için otomatik seçici önerileri sunar:

- **Yemeksepeti**
- **Getir**
- **Zomato**
- **Generic** (genel web siteleri)

## API Kullanımı

### Programatik Kullanım

```php
<?php
require_once 'vendor/autoload.php';

use ContentCrawler\MenuCrawler;
use ContentCrawler\DataExporter;

$crawler = new MenuCrawler();
$exporter = new DataExporter();

// Menü verilerini çek
$selectors = [
    'container' => '.menu-container',
    'item' => '.menu-item',
    'name' => '.product-name',
    'description' => '.description',
    'price' => '.price',
    'image' => 'img'
];

$result = $crawler->crawlMenu('https://example.com/menu', $selectors);

if ($result['success']) {
    // JSON olarak export et
    $jsonExport = $exporter->exportToJson($result['data']);
    
    // Dosyaya kaydet
    $exporter->saveToFile($jsonExport, 'exports');
    
    echo "Başarılı! {$result['count']} öğe çekildi.\n";
} else {
    echo "Hata: {$result['error']}\n";
}
?>
```

## Konfigürasyon

`config.php` dosyasında aşağıdaki ayarları yapabilirsiniz:

- **Crawler ayarları**: Timeout, user agent, SSL doğrulama
- **Export ayarları**: Dosya formatları, dizin ayarları
- **Güvenlik ayarları**: İzin verilen/yasaklı domainler
- **Loglama ayarları**: Log seviyesi, dosya boyutu

## Sorun Giderme

### Yaygın Sorunlar

1. **"Sayfa yüklenemedi" hatası**:
   - URL'nin doğru olduğundan emin olun
   - SSL sertifikası sorunları için `verify_ssl: false` ayarını kontrol edin

2. **"Menü konteyneri bulunamadı" hatası**:
   - CSS seçicilerinizi kontrol edin
   - "Seçicileri Test Et" özelliğini kullanın

3. **Boş veri çekiliyor**:
   - Seçicilerin doğru elementleri hedeflediğinden emin olun
   - JavaScript ile yüklenen içerik olabilir (bu durumda başka araçlar gerekir)

### Debug Modu

`config.php` dosyasında `debug: true` ayarını yaparak detaylı hata mesajları alabilirsiniz.

## Güvenlik

- Rate limiting ile aşırı istek koruması
- Domain kısıtlamaları
- Input validation
- XSS koruması

## Lisans

MIT License

## Katkıda Bulunma

1. Fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit edin (`git commit -m 'Add amazing feature'`)
4. Push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## Destek

Sorunlarınız için GitHub Issues kullanabilirsiniz.
