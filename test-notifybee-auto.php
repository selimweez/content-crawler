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

echo "<h1>NotifyBee Otomatik Kategori Keşfi Test</h1>\n";
echo "<h2>Menu URL: $menuUrl</h2>\n";

// 1. Kategori keşfi
echo "<hr><h3>1. Kategori Keşfi</h3>\n";
$categoryResult = $crawler->discoverNotifyBeeCategories($menuUrl);

if ($categoryResult['success']) {
    echo "<div style='color: green;'>✓ Kategori keşfi başarılı! " . $categoryResult['count'] . " kategori bulundu.</div>\n";
    
    echo "<h4>Bulunan Kategoriler:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>No</th><th>Kategori Adı</th><th>URL</th></tr>\n";
    
    foreach ($categoryResult['categories'] as $index => $category) {
        echo "<tr>\n";
        echo "<td>" . ($index + 1) . "</td>\n";
        echo "<td>" . htmlspecialchars($category['name']) . "</td>\n";
        echo "<td><a href='" . htmlspecialchars($category['url']) . "' target='_blank'>" . htmlspecialchars($category['url']) . "</a></td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
} else {
    echo "<div style='color: red;'>✗ Kategori keşfi başarısız: " . $categoryResult['error'] . "</div>\n";
}

// 2. Otomatik crawl (tüm kategoriler)
echo "<hr><h3>2. Otomatik Crawl (Tüm Kategoriler)</h3>\n";
$crawlResult = $crawler->crawlNotifyBeeMenuWithCategories($menuUrl, $selectors);

if ($crawlResult['success']) {
    echo "<div style='color: green;'>✓ Otomatik crawl başarılı!</div>\n";
    echo "<ul>\n";
    echo "<li><strong>Toplam Item:</strong> " . $crawlResult['count'] . "</li>\n";
    echo "<li><strong>Keşfedilen Kategori:</strong> " . $crawlResult['categories_discovered'] . "</li>\n";
    echo "<li><strong>Kaynak Menu URL:</strong> " . $crawlResult['source_menu_url'] . "</li>\n";
    echo "<li><strong>Zaman:</strong> " . $crawlResult['timestamp'] . "</li>\n";
    echo "</ul>\n";
    
    // Crawl sonuçları
    echo "<h4>Kategori Crawl Sonuçları:</h4>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>Kategori</th><th>URL</th><th>Durum</th><th>Item Sayısı</th><th>Hata</th></tr>\n";
    
    foreach ($crawlResult['crawl_results'] as $result) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($result['category']) . "</td>\n";
        echo "<td><a href='" . htmlspecialchars($result['url']) . "' target='_blank'>" . htmlspecialchars($result['url']) . "</a></td>\n";
        echo "<td>" . ($result['success'] ? '<span style="color: green;">✓ Başarılı</span>' : '<span style="color: red;">✗ Başarısız</span>') . "</td>\n";
        echo "<td>" . ($result['success'] ? $result['count'] : '-') . "</td>\n";
        echo "<td>" . ($result['success'] ? '-' : htmlspecialchars($result['error'])) . "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // İlk birkaç item'ı göster
    echo "<h4>Örnek Items (İlk 5):</h4>\n";
    $sampleItems = array_slice($crawlResult['data'], 0, 5);
    
    foreach ($sampleItems as $index => $item) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px; background: #f9f9f9;'>\n";
        echo "<h5>Item " . ($index + 1) . " - " . htmlspecialchars($item['category']) . "</h5>\n";
        echo "<strong>İsim:</strong> " . htmlspecialchars($item['name']) . "<br>\n";
        echo "<strong>Açıklama:</strong> " . htmlspecialchars($item['description']) . "<br>\n";
        echo "<strong>Fiyat:</strong> " . htmlspecialchars($item['price']) . "<br>\n";
        echo "<strong>Resim:</strong> " . htmlspecialchars($item['image']) . "<br>\n";
        echo "<strong>Kaynak URL:</strong> <a href='" . htmlspecialchars($item['source_url']) . "' target='_blank'>" . htmlspecialchars($item['source_url']) . "</a><br>\n";
        echo "</div>\n";
    }
    
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
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($category) . "</td>\n";
        echo "<td>" . $count . "</td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
} else {
    echo "<div style='color: red;'>✗ Otomatik crawl başarısız: " . $crawlResult['error'] . "</div>\n";
}

echo "<hr><p><em>Test tamamlandı: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
