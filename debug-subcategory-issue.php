<?php
require_once 'vendor/autoload.php';

use ContentCrawler\MenuCrawler;

$crawler = new MenuCrawler();

// Test URL'leri
$mainMenuUrl = 'https://notifybee.com.tr/menu?id=1584';
$categoryUrl = 'https://notifybee.com.tr/menudetay?menu=20213&id=1584';

echo "<h1>Alt Kategori Debug Test</h1>\n";

// 1. Ana menüden kategorileri bul
echo "<h2>1. Ana Menü Kategorileri</h2>\n";
echo "<p>URL: $mainMenuUrl</p>\n";

$mainCategories = $crawler->discoverNotifyBeeCategories($mainMenuUrl);

if ($mainCategories['success']) {
    echo "<div style='color: green;'>✓ Ana kategoriler bulundu: " . $mainCategories['count'] . "</div>\n";
    
    foreach ($mainCategories['categories'] as $index => $category) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>\n";
        echo "<h3>" . ($index + 1) . ". " . htmlspecialchars($category['name']) . "</h3>\n";
        echo "<p><strong>URL:</strong> <a href='" . htmlspecialchars($category['url']) . "' target='_blank'>" . htmlspecialchars($category['url']) . "</a></p>\n";
        
        // Bu kategoriyi detaylı analiz et
        echo "<h4>Bu Kategorinin Detaylı Analizi:</h4>\n";
        
        try {
            // Manuel olarak sayfayı çek ve analiz et
            $client = new \GuzzleHttp\Client(['verify' => false]);
            $response = $client->get($category['url']);
            $html = $response->getBody()->getContents();
            
            // DOM analizi
            $domCrawler = new \Symfony\Component\DomCrawler\Crawler($html);
            
            // Farklı seçicilerle test et
            $selectors = [
                'li a[href*="menudetay"]' => 'li a[href*="menudetay"]',
                'a[href*="menudetay"]' => 'a[href*="menudetay"]',
                '.vertical-menu-list__item' => '.vertical-menu-list__item (ürünler)'
            ];
            
            foreach ($selectors as $selector => $description) {
                $elements = $domCrawler->filter($selector);
                echo "<p><strong>$description:</strong> " . $elements->count() . " element bulundu</p>\n";
                
                if ($elements->count() > 0 && $elements->count() <= 10) {
                    echo "<ul>\n";
                    $elements->each(function($element) {
                        $text = trim($element->text());
                        $href = $element->attr('href');
                        if (!empty($text) && strlen($text) < 100) {
                            echo "<li>" . htmlspecialchars($text) . " -> " . htmlspecialchars($href) . "</li>\n";
                        }
                    });
                    echo "</ul>\n";
                }
            }
            
            // Reflection ile private method'ları test et
            $reflection = new ReflectionClass($crawler);
            
            $hasSubcategoriesMethod = $reflection->getMethod('hasSubcategories');
            $hasSubcategoriesMethod->setAccessible(true);
            
            $discoverSubcategoriesMethod = $reflection->getMethod('discoverSubcategories');
            $discoverSubcategoriesMethod->setAccessible(true);
            
            $hasSubcategories = $hasSubcategoriesMethod->invoke($crawler, $category['url']);
            echo "<p><strong>hasSubcategories() sonucu:</strong> " . ($hasSubcategories ? 'TRUE (Alt kategori var)' : 'FALSE (Alt kategori yok)') . "</p>\n";
            
            if ($hasSubcategories) {
                $subcategories = $discoverSubcategoriesMethod->invoke($crawler, $category['url']);
                echo "<p><strong>discoverSubcategories() sonucu:</strong> " . count($subcategories) . " alt kategori bulundu</p>\n";
                
                if (!empty($subcategories)) {
                    echo "<ul>\n";
                    foreach ($subcategories as $sub) {
                        echo "<li><strong>" . htmlspecialchars($sub['name']) . "</strong> -> <a href='" . htmlspecialchars($sub['url']) . "' target='_blank'>" . htmlspecialchars($sub['url']) . "</a></li>\n";
                    }
                    echo "</ul>\n";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>\n";
        }
        
        echo "</div>\n";
        
        // Sadece ilk 2 kategoriyi test et (çok uzun olmasın)
        if ($index >= 1) {
            echo "<p><em>... (diğer kategoriler atlandı)</em></p>\n";
            break;
        }
    }
} else {
    echo "<div style='color: red;'>✗ Ana kategori keşfi başarısız: " . $mainCategories['error'] . "</div>\n";
}

// 2. Spesifik kategori URL'sini test et
echo "<hr><h2>2. Spesifik Kategori URL Testi</h2>\n";
echo "<p>URL: $categoryUrl</p>\n";

try {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $response = $client->get($categoryUrl);
    $html = $response->getBody()->getContents();
    
    $domCrawler = new \Symfony\Component\DomCrawler\Crawler($html);
    
    echo "<h3>Bu sayfadaki elementler:</h3>\n";
    
    $selectors = [
        'li a[href*="menudetay"]' => 'li a[href*="menudetay"]',
        'a[href*="menudetay"]' => 'a[href*="menudetay"]',
        '.vertical-menu-list__item' => '.vertical-menu-list__item (ürünler)',
        'li' => 'li (tüm liste elemanları)'
    ];
    
    foreach ($selectors as $selector => $description) {
        $elements = $domCrawler->filter($selector);
        echo "<h4>$description: " . $elements->count() . " element</h4>\n";
        
        if ($elements->count() > 0 && $elements->count() <= 15) {
            echo "<ul>\n";
            $elements->each(function($element) {
                $text = trim($element->text());
                $href = $element->attr('href');
                if (!empty($text) && strlen($text) < 150) {
                    echo "<li>" . htmlspecialchars(substr($text, 0, 100)) . " -> " . htmlspecialchars($href) . "</li>\n";
                }
            });
            echo "</ul>\n";
        }
    }
    
    // Crawler method'larını test et
    $reflection = new ReflectionClass($crawler);
    
    $hasSubcategoriesMethod = $reflection->getMethod('hasSubcategories');
    $hasSubcategoriesMethod->setAccessible(true);
    
    $discoverSubcategoriesMethod = $reflection->getMethod('discoverSubcategories');
    $discoverSubcategoriesMethod->setAccessible(true);
    
    $hasSubcategories = $hasSubcategoriesMethod->invoke($crawler, $categoryUrl);
    echo "<p><strong>hasSubcategories() sonucu:</strong> " . ($hasSubcategories ? 'TRUE (Alt kategori var)' : 'FALSE (Alt kategori yok)') . "</p>\n";
    
    $subcategories = $discoverSubcategoriesMethod->invoke($crawler, $categoryUrl);
    echo "<p><strong>discoverSubcategories() sonucu:</strong> " . count($subcategories) . " alt kategori bulundu</p>\n";
    
    if (!empty($subcategories)) {
        echo "<ul>\n";
        foreach ($subcategories as $sub) {
            echo "<li><strong>" . htmlspecialchars($sub['name']) . "</strong> -> <a href='" . htmlspecialchars($sub['url']) . "' target='_blank'>" . htmlspecialchars($sub['url']) . "</a></li>\n";
        }
        echo "</ul>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Hata: " . $e->getMessage() . "</p>\n";
}

echo "<hr><p><em>Debug test tamamlandı: " . date('Y-m-d H:i:s') . "</em></p>\n";
?>
