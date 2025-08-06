<?php
require_once 'vendor/autoload.php';

use ContentCrawler\MenuCrawler;

$crawler = new MenuCrawler();

// Test URLs
$testUrls = [
    'Local Test' => 'http://localhost/content-crawler/test-notifybee.html',
    'Real NotifyBee' => 'https://notifybee.com.tr/menudetay?menu=18444&id=2524'
];

// NotifyBee seçicileri
$selectors = [
    'container' => '.arabas',
    'item' => '.vertical-menu-list__item',
    'name' => 'h6',
    'description' => '.col-8 p', // Kısa açıklama
    'price' => '.text-orange',
    'image' => '.food-background div[style*="background-image"]'
];

echo "<h1>Modal Crawler Test</h1>\n";
echo "<h3>Seçiciler:</h3>\n";
echo "<pre>" . print_r($selectors, true) . "</pre>\n";

foreach ($testUrls as $testName => $testUrl) {
    echo "<hr><h2>$testName: $testUrl</h2>\n";

    // Test seçicileri
    echo "<h3>Seçici Test Sonuçları:</h3>\n";
    $testResult = $crawler->testSelectors($testUrl, $selectors);

    if ($testResult['success']) {
        echo "<div style='color: green;'>✓ Test başarılı!</div>\n";
        echo "<pre>" . print_r($testResult['results'], true) . "</pre>\n";
    } else {
        echo "<div style='color: red;'>✗ Test başarısız: " . $testResult['error'] . "</div>\n";
    }

    // Crawl menu
    echo "<h3>Menu Crawling Sonuçları:</h3>\n";
    $crawlResult = $crawler->crawlMenu($testUrl, $selectors);

    if ($crawlResult['success']) {
        echo "<div style='color: green;'>✓ Crawling başarılı! " . $crawlResult['count'] . " item bulundu.</div>\n";
        echo "<h4>Bulunan Items:</h4>\n";

        foreach ($crawlResult['data'] as $index => $item) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
            echo "<h5>Item " . ($index + 1) . "</h5>\n";
            echo "<strong>İsim:</strong> " . htmlspecialchars($item['name']) . "<br>\n";
            echo "<strong>Açıklama:</strong> " . htmlspecialchars($item['description']) . "<br>\n";
            echo "<strong>Fiyat:</strong> " . htmlspecialchars($item['price']) . "<br>\n";
            echo "<strong>Resim:</strong> " . htmlspecialchars($item['image']) . "<br>\n";
            echo "</div>\n";
        }
    } else {
        echo "<div style='color: red;'>✗ Crawling başarısız: " . $crawlResult['error'] . "</div>\n";
    }

    // Modal açıklama testi için özel test
    echo "<h3>Modal Açıklama Özel Test:</h3>\n";

    // Modal seçicili test
    $modalSelectors = $selectors;
    $modalSelectors['description'] = '.modal .card-header p.text-white';

    echo "<h4>Modal seçicisi ile test:</h4>\n";
    $modalResult = $crawler->crawlMenu($testUrl, $modalSelectors);

    if ($modalResult['success']) {
        echo "<div style='color: green;'>✓ Modal test başarılı! " . $modalResult['count'] . " item bulundu.</div>\n";

        foreach ($modalResult['data'] as $index => $item) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px; background: #f0f0f0;'>\n";
            echo "<h5>Modal Item " . ($index + 1) . "</h5>\n";
            echo "<strong>İsim:</strong> " . htmlspecialchars($item['name']) . "<br>\n";
            echo "<strong>Modal Açıklama:</strong> " . htmlspecialchars($item['description']) . "<br>\n";
            echo "<strong>Fiyat:</strong> " . htmlspecialchars($item['price']) . "<br>\n";
            echo "</div>\n";
        }
    } else {
        echo "<div style='color: red;'>✗ Modal test başarısız: " . $modalResult['error'] . "</div>\n";
    }
}
?>
