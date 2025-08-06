<?php
require_once 'vendor/autoload.php';

use ContentCrawler\MenuCrawler;

$crawler = new MenuCrawler();

// Test NotifyBee menu URL
$menuUrl = 'https://notifybee.com.tr/menu?id=401';

// NotifyBee seçicileri
$selectors = [
    'container' => '.arabas',
    'item' => '.vertical-menu-list__item',
    'name' => 'h6',
    'description' => '.modal .card-header p.text-white',
    'price' => '.text-orange',
    'image' => '.food-background div[style*="background-image"]'
];

echo "<h1>NotifyBee Alt Kategori Desteği Test</h1>\n";
echo "<h2>Menu URL: $menuUrl</h2>\n";

// 1. Kategori keşfi
echo "<hr><h3>1. Kategori Keşfi</h3>\n";
$categoryResult = $crawler->discoverNotifyBeeCategories($menuUrl);

if ($categoryResult['success']) {
    echo "<div style='color: green;'>✓ Kategori keşfi başarılı! " . $categoryResult['count'] . " kategori bulundu.</div>\n";
    
    echo "<h4>Bulunan Kategoriler:</h4>\n";
    foreach ($categoryResult['categories'] as $index => $category) {
        echo "<div style='border: 1px solid #ddd; margin: 5px; padding: 10px;'>\n";
        echo "<strong>" . ($index + 1) . ". " . htmlspecialchars($category['name']) . "</strong><br>\n";
        echo "<a href='" . htmlspecialchars($category['url']) . "' target='_blank'>" . htmlspecialchars($category['url']) . "</a><br>\n";
        
        // Alt kategori kontrolü
        echo "<em>Alt kategori kontrolü yapılıyor...</em><br>\n";
        
        // Bu kısmı reflection ile test edelim
        $reflection = new ReflectionClass($crawler);
        $hasSubcategoriesMethod = $reflection->getMethod('hasSubcategories');
        $hasSubcategoriesMethod->setAccessible(true);
        
        $discoverSubcategoriesMethod = $reflection->getMethod('discoverSubcategories');
        $discoverSubcategoriesMethod->setAccessible(true);
        
        $hasSubcategories = $hasSubcategoriesMethod->invoke($crawler, $category['url']);
        
        if ($hasSubcategories) {
            echo "<span style='color: orange;'>⚠ Alt kategoriler tespit edildi!</span><br>\n";
            
            $subcategories = $discoverSubcategoriesMethod->invoke($crawler, $category['url']);
            if (!empty($subcategories)) {
                echo "<ul>\n";
                foreach ($subcategories as $subcategory) {
                    echo "<li>" . htmlspecialchars($subcategory['name']) . " - <a href='" . htmlspecialchars($subcategory['url']) . "' target='_blank'>Link</a></li>\n";
                }
                echo "</ul>\n";
            }
        } else {
            echo "<span style='color: green;'>✓ Direkt ürünler var</span><br>\n";
        }
        
        echo "</div>\n";
    }
} else {
    echo "<div style='color: red;'>✗ Kategori keşfi başarısız: " . $categoryResult['error'] . "</div>\n";
}

// 2. Alt kategori destekli otomatik crawl
echo "<hr><h3>2. Alt Kategori Destekli Otomatik Crawl</h3>\n";
$crawlResult = $crawler->crawlNotifyBeeMenuWithCategories($menuUrl, $selectors);

if ($crawlResult['success']) {
    echo "<div style='color: green;'>✓ Alt kategori destekli crawl başarılı!</div>\n";
    echo "<ul>\n";
    echo "<li><strong>Toplam Item:</strong> " . $crawlResult['count'] . "</li>\n";
    echo "<li><strong>Keşfedilen Kategori:</strong> " . $crawlResult['categories_discovered'] . "</li>\n";
    echo "<li><strong>Kaynak Menu URL:</strong> " . $crawlResult['source_menu_url'] . "</li>\n";
    echo "<li><strong>Zaman:</strong> " . $crawlResult['timestamp'] . "</li>\n";
    echo "</ul>\n";
    
    // Crawl sonuçları
    echo "<h4>Detaylı Crawl Sonuçları:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Kategori</th><th>Tip</th><th>URL</th><th>Durum</th><th>Item Sayısı</th><th>Hata</th></tr>\n";
    
    foreach ($crawlResult['crawl_results'] as $result) {
        $isSubcategory = isset($result['is_subcategory']) && $result['is_subcategory'];
        $categoryType = $isSubcategory ? 'Alt Kategori' : 'Ana Kategori';
        $categoryStyle = $isSubcategory ? 'background-color: #f0f8ff;' : '';
        
        echo "<tr style='$categoryStyle'>\n";
        echo "<td>" . htmlspecialchars($result['category']) . "</td>\n";
        echo "<td>" . $categoryType . "</td>\n";
        echo "<td><a href='" . htmlspecialchars($result['url']) . "' target='_blank'>" . htmlspecialchars($result['url']) . "</a></td>\n";
        echo "<td>" . ($result['success'] ? '<span style="color: green;">✓ Başarılı</span>' : '<span style="color: red;">✗ Başarısız</span>') . "</td>\n";
        echo "<td>" . ($result['success'] ? $result['count'] : '-') . "</td>\n";
        echo "<td>" . ($result['success'] ? '-' : htmlspecialchars($result['error'])) . "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Kategori bazında özet
    echo "<h4>Kategori Bazında Özet:</h4>\n";
    $categoryStats = [];
    foreach ($crawlResult['data'] as $item) {
        $category = $item['category'];
        if (!isset($categoryStats[$category])) {
            $categoryStats[$category] = 0;
        }
        $categoryStats[$category]++;
    }
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Kategori</th><th>Item Sayısı</th></tr>\n";
    
    foreach ($categoryStats as $category => $count) {
        $isSubcategory = strpos($category, ' > ') !== false;
        $categoryStyle = $isSubcategory ? 'background-color: #f0f8ff;' : '';
        
        echo "<tr style='$categoryStyle'>\n";
        echo "<td>" . htmlspecialchars($category) . "</td>\n";
        echo "<td>" . $count . "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
} else {
    echo "<div style='color: red;'>✗ Alt kategori destekli crawl başarısız: " . $crawlResult['error'] . "</div>\n";
}

echo "<hr><p><em>Test tamamlandı: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
