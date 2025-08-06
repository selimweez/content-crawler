<?php

namespace ContentCrawler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

class MenuCrawler
{
    private $client;
    private $userAgent;

    public function __construct()
    {
        $this->userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

        $this->client = new Client([
            'timeout' => 30,
            'verify' => false,
            'headers' => [
                'User-Agent' => $this->userAgent
            ]
        ]);
    }

    /**
     * Crawl menu data from a given URL using CSS selectors
     */
    public function crawlMenu($url, $selectors)
    {
        try {
            // Validate URL
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Geçersiz URL formatı');
            }

            // Fetch HTML content
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();

            if (empty($html)) {
                throw new \Exception('Sayfa içeriği boş: ' . $url);
            }

            // Create DOM crawler
            $crawler = new Crawler($html);

            $menuItems = [];

            // Find menu container
            $containerSelector = $selectors['container'];
            if (empty($containerSelector)) {
                throw new \Exception('Menü konteyneri seçicisi boş');
            }

            $menuContainer = $crawler->filter($containerSelector);

            if ($menuContainer->count() === 0) {
                throw new \Exception('Menü konteyneri bulunamadı: ' . $containerSelector);
            }

            // Extract menu items
            $itemSelector = $selectors['item'];
            if (empty($itemSelector)) {
                throw new \Exception('Menü öğesi seçicisi boş');
            }

            $items = $menuContainer->filter($itemSelector);

            $items->each(function (Crawler $item) use (&$menuItems, $selectors, $url, $crawler) {
                $menuItem = [
                    'name' => $this->extractText($item, $selectors['name']),
                    'description' => $this->extractText($item, $selectors['description']),
                    'price' => $this->extractText($item, $selectors['price']),
                    'image' => $this->extractImage($item, $selectors['image'], $url)
                ];

                // NotifyBee özel durumu: Modal açıklamalarını çek
                if (strpos($url, 'notifybee') !== false && empty($menuItem['description'])) {
                    $modalDescription = $this->extractModalDescription($item, $crawler);
                    if (!empty($modalDescription)) {
                        $menuItem['description'] = $modalDescription;
                    }
                }

                // Debug: Log description extraction
                if (!empty($selectors['description'])) {
                    error_log("DEBUG - Description selector: " . $selectors['description']);
                    error_log("DEBUG - Found description: " . $menuItem['description']);

                    // Try to find all matching elements for debug
                    try {
                        $descElements = $item->filter($selectors['description']);
                        error_log("DEBUG - Description elements found: " . $descElements->count());
                        if ($descElements->count() > 0) {
                            error_log("DEBUG - First description HTML: " . $descElements->first()->html());
                        }
                    } catch (\Exception $e) {
                        error_log("DEBUG - Description extraction error: " . $e->getMessage());
                    }
                }

                // Only add if we have at least name or price
                if (!empty($menuItem['name']) || !empty($menuItem['price'])) {
                    $menuItems[] = $menuItem;
                }
            });

            return [
                'success' => true,
                'data' => $menuItems,
                'count' => count($menuItems),
                'url' => $url,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (RequestException $e) {
            return [
                'success' => false,
                'error' => 'HTTP Hatası: ' . $e->getMessage(),
                'url' => $url
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url
            ];
        }
    }

    /**
     * Extract text content from element using selector
     */
    private function extractText(Crawler $element, $selector)
    {
        if (empty($selector)) return '';

        try {
            $found = $element->filter($selector);
            return $found->count() > 0 ? trim($found->text()) : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract image URL from element using selector
     */
    private function extractImage(Crawler $element, $selector, $baseUrl)
    {
        if (empty($selector)) return '';

        try {
            $found = $element->filter($selector);
            if ($found->count() === 0) return '';

            // Try to get src attribute first
            $src = $found->attr('src') ?: $found->attr('data-src') ?: '';

            // If no src found, try to extract from style attribute (background-image)
            if (empty($src)) {
                $style = $found->attr('style');
                if (!empty($style)) {
                    // Extract URL from background-image: url(...)
                    if (preg_match('/background-image\s*:\s*url\s*\(\s*["\']?([^"\']+)["\']?\s*\)/i', $style, $matches)) {
                        $src = $matches[1];
                    }
                }
            }

            // If still no src, try to find child elements with background-image
            if (empty($src)) {
                $childWithBg = $found->filter('[style*="background-image"]');
                if ($childWithBg->count() > 0) {
                    $style = $childWithBg->attr('style');
                    if (preg_match('/background-image\s*:\s*url\s*\(\s*["\']?([^"\']+)["\']?\s*\)/i', $style, $matches)) {
                        $src = $matches[1];
                    }
                }
            }

            if (empty($src)) return '';

            // Return the URL as-is (don't convert relative to absolute)
            return $src;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract modal description for NotifyBee-style sites
     */
    private function extractModalDescription(Crawler $item, Crawler $fullCrawler)
    {
        try {
            // Modal butonunu bul
            $modalButton = $item->filter('[data-bs-target^="#menuModal"]');
            if ($modalButton->count() === 0) {
                return '';
            }

            // Modal ID'sini çıkar
            $modalTarget = $modalButton->attr('data-bs-target');
            if (empty($modalTarget)) {
                return '';
            }

            // Modal'ı bul
            $modal = $fullCrawler->filter($modalTarget);
            if ($modal->count() === 0) {
                return '';
            }

            // Modal içindeki açıklamayı bul
            $description = $modal->filter('.card-header p.text-white');
            if ($description->count() > 0) {
                return trim($description->text());
            }

            // Alternatif seçiciler dene
            $altDescription = $modal->filter('.modal-body .card-header p');
            if ($altDescription->count() > 0) {
                return trim($altDescription->text());
            }

            return '';
        } catch (\Exception $e) {
            error_log("Modal description extraction error: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Discover categories from NotifyBee menu page (with subcategory support)
     */
    public function discoverNotifyBeeCategories($menuUrl, $includeSubcategories = true)
    {
        try {
            // Validate URL
            if (!filter_var($menuUrl, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Geçersiz URL formatı');
            }

            // Fetch HTML content
            $response = $this->client->get($menuUrl);
            $html = $response->getBody()->getContents();

            if (empty($html)) {
                throw new \Exception('Sayfa içeriği boş: ' . $menuUrl);
            }

            // Create DOM crawler
            $crawler = new Crawler($html);

            $categories = [];

            // NotifyBee kategori linklerini bul
            // Kategoriler genellikle li > a[href*="menudetay"] formatında
            $categoryLinks = $crawler->filter('li a[href*="menudetay"]');

            $categoryLinks->each(function (Crawler $link) use (&$categories, $includeSubcategories) {
                $href = $link->attr('href');
                $categoryName = trim($link->text());

                // Boş kategori adlarını atla
                if (empty($categoryName)) {
                    return;
                }

                // Sosyal medya paylaşım linklerini filtrele
                $socialMediaKeywords = [
                    'whatsapp', 'sms', 'twitter', 'facebook', 'telegram', 'email',
                    'paylaş', 'share', 'wa.me', 'twitter.com', 'facebook.com'
                ];

                $categoryNameLower = mb_strtolower($categoryName, 'UTF-8');
                $hrefLower = mb_strtolower($href, 'UTF-8');

                $isSocialMedia = false;
                foreach ($socialMediaKeywords as $keyword) {
                    if (strpos($categoryNameLower, $keyword) !== false || strpos($hrefLower, $keyword) !== false) {
                        $isSocialMedia = true;
                        break;
                    }
                }

                if ($isSocialMedia) {
                    return; // Sosyal medya linklerini atla
                }

                // Tam URL oluştur
                if (strpos($href, 'http') !== 0) {
                    $href = 'https://notifybee.com.tr' . $href;
                }

                // Sadece menudetay linklerini kabul et
                if (strpos($href, 'menudetay') === false) {
                    return;
                }

                // Kategori resmini bul
                $categoryImage = $this->extractCategoryImage($href);

                $categories[] = [
                    'name' => $categoryName,
                    'url' => $href,
                    'is_main_category' => true,
                    'image' => $categoryImage
                ];

                // Alt kategorileri de keşfet
                if ($includeSubcategories && $this->hasSubcategories($href)) {
                    $subcategories = $this->discoverSubcategories($href);
                    foreach ($subcategories as $subcategory) {
                        $categories[] = [
                            'name' => $categoryName . ' > ' . $subcategory['name'],
                            'url' => $subcategory['url'],
                            'is_main_category' => false,
                            'parent_category' => $categoryName,
                            'image' => $subcategory['image'] ?? ''
                        ];
                    }

                    // Small delay between subcategory discoveries
                    usleep(300000); // 0.3 seconds
                }
            });

            return [
                'success' => true,
                'categories' => $categories,
                'count' => count($categories),
                'source_url' => $menuUrl,
                'includes_subcategories' => $includeSubcategories
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'source_url' => $menuUrl
            ];
        }
    }

    /**
     * Check if a category URL has subcategories
     */
    private function hasSubcategories($categoryUrl)
    {
        try {
            $response = $this->client->get($categoryUrl);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // Alt kategoriler için farklı seçiciler dene
            $subcategorySelectors = [
                'li a[href*="menudetay"]',  // Ana seçici
                'a[href*="menudetay"]',     // Daha genel
                '.category-link',           // Özel class varsa
                '.menu-category a'          // Alternatif yapı
            ];

            $subcategoryLinks = null;
            foreach ($subcategorySelectors as $selector) {
                $links = $crawler->filter($selector);
                if ($links->count() > 0) {
                    $subcategoryLinks = $links;
                    break;
                }
            }

            if (!$subcategoryLinks || $subcategoryLinks->count() == 0) {
                return false;
            }

            // Ürün sayısını kontrol et
            $productItems = $crawler->filter('.vertical-menu-list__item');
            $productCount = $productItems->count();

            // Alt kategori linklerini analiz et
            $validSubcategoryCount = 0;
            $subcategoryLinks->each(function (Crawler $link) use (&$validSubcategoryCount, $categoryUrl) {
                $href = $link->attr('href');
                $text = trim($link->text());

                // Boş text'leri atla
                if (empty($text)) {
                    return;
                }

                // Tam URL oluştur
                if (strpos($href, 'http') !== 0) {
                    $href = 'https://notifybee.com.tr' . $href;
                }

                // Aynı URL'yi sayma (sonsuz döngü önleme)
                if ($href === $categoryUrl) {
                    return;
                }

                // Geçerli alt kategori sayısını artır
                $validSubcategoryCount++;
            });

            // Debug log
            error_log("hasSubcategories for $categoryUrl: valid_subcategories=$validSubcategoryCount, products=$productCount");

            // Karar verme mantığı - daha esnek yaklaşım
            if ($validSubcategoryCount > 0) {
                // Eğer hiç ürün yoksa, kesinlikle alt kategori sayfası
                if ($productCount == 0) {
                    return true;
                }

                // Eğer alt kategori sayısı 2 veya daha fazla ise, muhtemelen alt kategori sayfası
                if ($validSubcategoryCount >= 2) {
                    return true;
                }

                // Eğer sadece 1 alt kategori varsa ama hiç ürün yoksa
                if ($validSubcategoryCount == 1 && $productCount == 0) {
                    return true;
                }

                // Eğer ürün sayısı çok az ise (5'ten az), alt kategoriler öncelikli
                if ($productCount < 5 && $validSubcategoryCount > 0) {
                    return true;
                }

                // Eğer alt kategori sayısı ürün sayısının yarısından fazla ise
                if ($validSubcategoryCount > ($productCount / 2)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            error_log("hasSubcategories error for $categoryUrl: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Discover subcategories from a category URL
     */
    private function discoverSubcategories($categoryUrl)
    {
        try {
            $response = $this->client->get($categoryUrl);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            $subcategories = [];

            // Alt kategoriler için farklı seçiciler dene
            $subcategorySelectors = [
                'li a[href*="menudetay"]',  // Ana seçici
                'a[href*="menudetay"]',     // Daha genel
                '.category-link',           // Özel class varsa
                '.menu-category a'          // Alternatif yapı
            ];

            $subcategoryLinks = null;
            foreach ($subcategorySelectors as $selector) {
                $links = $crawler->filter($selector);
                if ($links->count() > 0) {
                    $subcategoryLinks = $links;
                    error_log("Using selector '$selector' for subcategories, found " . $links->count() . " links");
                    break;
                }
            }

            if (!$subcategoryLinks) {
                error_log("No subcategory links found for $categoryUrl");
                return [];
            }

            $subcategoryLinks->each(function (Crawler $link) use (&$subcategories, $categoryUrl) {
                $href = $link->attr('href');
                $categoryName = trim($link->text());

                if (empty($categoryName)) {
                    return;
                }

                // Sosyal medya paylaşım linklerini filtrele
                $socialMediaKeywords = [
                    'whatsapp', 'sms', 'twitter', 'facebook', 'telegram', 'email',
                    'paylaş', 'share', 'wa.me', 'twitter.com', 'facebook.com'
                ];

                $categoryNameLower = mb_strtolower($categoryName, 'UTF-8');
                $hrefLower = mb_strtolower($href, 'UTF-8');

                $isSocialMedia = false;
                foreach ($socialMediaKeywords as $keyword) {
                    if (strpos($categoryNameLower, $keyword) !== false || strpos($hrefLower, $keyword) !== false) {
                        $isSocialMedia = true;
                        break;
                    }
                }

                if ($isSocialMedia) {
                    return; // Sosyal medya linklerini atla
                }

                // Tam URL oluştur
                if (strpos($href, 'http') !== 0) {
                    $href = 'https://notifybee.com.tr' . $href;
                }

                // Aynı URL'yi tekrar eklemeyi önle (sonsuz döngü)
                if ($href === $categoryUrl) {
                    return;
                }

                // Çok kısa isimleri atla (muhtemelen hatalı)
                if (strlen($categoryName) < 3) {
                    return;
                }

                // Sadece menudetay linklerini kabul et
                if (strpos($href, 'menudetay') === false) {
                    return;
                }

                // Alt kategori resmini bul
                $subcategoryImage = $this->extractCategoryImage($href);

                $subcategories[] = [
                    'name' => $categoryName,
                    'url' => $href,
                    'image' => $subcategoryImage
                ];
            });

            // Duplicate URL'leri temizle
            $uniqueSubcategories = [];
            $seenUrls = [];

            foreach ($subcategories as $sub) {
                if (!in_array($sub['url'], $seenUrls)) {
                    $uniqueSubcategories[] = $sub;
                    $seenUrls[] = $sub['url'];
                }
            }

            // Debug log
            error_log("Subcategories found for $categoryUrl: " . count($uniqueSubcategories));
            foreach ($uniqueSubcategories as $sub) {
                error_log("  - " . $sub['name'] . " -> " . $sub['url']);
            }

            return $uniqueSubcategories;
        } catch (\Exception $e) {
            error_log("Error discovering subcategories for $categoryUrl: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Auto-discover and crawl all categories from a NotifyBee menu URL
     */
    public function crawlNotifyBeeMenuWithCategories($menuUrl, $selectors)
    {
        try {
            // First discover categories (including subcategories)
            $categoryResult = $this->discoverNotifyBeeCategories($menuUrl, true);

            if (!$categoryResult['success']) {
                return $categoryResult;
            }

            $allMenuItems = [];
            $crawlResults = [];

            // Crawl each category
            foreach ($categoryResult['categories'] as $category) {
                $categoryName = $category['name'];
                $categoryUrl = $category['url'];
                $isMainCategory = $category['is_main_category'] ?? true;

                // Eğer bu zaten keşfedilmiş bir alt kategoriyse, direkt crawl et
                if (!$isMainCategory) {
                    // Bu bir alt kategori, direkt crawl et
                    $crawlResult = $this->crawlMenu($categoryUrl, $selectors);

                    if ($crawlResult['success']) {
                        $itemsWithCategory = array_map(function($item) use ($categoryName, $categoryUrl) {
                            $item['category'] = $categoryName;
                            $item['source_url'] = $categoryUrl;
                            return $item;
                        }, $crawlResult['data']);

                        $allMenuItems = array_merge($allMenuItems, $itemsWithCategory);

                        $crawlResults[] = [
                            'category' => $categoryName,
                            'url' => $categoryUrl,
                            'count' => $crawlResult['count'],
                            'success' => true,
                            'is_subcategory' => true,
                            'pre_discovered' => true
                        ];
                    } else {
                        $crawlResults[] = [
                            'category' => $categoryName,
                            'url' => $categoryUrl,
                            'error' => $crawlResult['error'],
                            'success' => false,
                            'is_subcategory' => true,
                            'pre_discovered' => true
                        ];
                    }
                } else {
                    // Bu bir ana kategori, alt kategori kontrolü yap (ama zaten keşfedilmiş olabilir)
                    $crawlResult = $this->crawlMenu($categoryUrl, $selectors);

                    if ($crawlResult['success']) {
                        $itemsWithCategory = array_map(function($item) use ($categoryName, $categoryUrl) {
                            $item['category'] = $categoryName;
                            $item['source_url'] = $categoryUrl;
                            return $item;
                        }, $crawlResult['data']);

                        $allMenuItems = array_merge($allMenuItems, $itemsWithCategory);

                        $crawlResults[] = [
                            'category' => $categoryName,
                            'url' => $categoryUrl,
                            'count' => $crawlResult['count'],
                            'success' => true,
                            'is_main_category' => true
                        ];
                    } else {
                        $crawlResults[] = [
                            'category' => $categoryName,
                            'url' => $categoryUrl,
                            'error' => $crawlResult['error'],
                            'success' => false,
                            'is_main_category' => true
                        ];
                    }
                }

                // Small delay between categories
                usleep(500000); // 0.5 seconds
            }

            return [
                'success' => true,
                'data' => $allMenuItems,
                'count' => count($allMenuItems),
                'categories_discovered' => count($categoryResult['categories']),
                'crawl_results' => $crawlResults,
                'source_menu_url' => $menuUrl,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'source_menu_url' => $menuUrl
            ];
        }
    }

    /**
     * Extract category image from a category URL
     */
    private function extractCategoryImage($categoryUrl)
    {
        try {
            $response = $this->client->get($categoryUrl);
            $html = $response->getBody()->getContents();
            $crawler = new Crawler($html);

            // NotifyBee'de kategori resimleri için farklı seçiciler dene
            $imageSelectors = [
                '.food-background div[style*="background-image"]', // Ana seçici
                '.category-image img',                             // Alternatif 1
                '.menu-category-image',                           // Alternatif 2
                'img[src*="upload"]',                            // Genel upload resmi
                '.card-img-top',                                 // Bootstrap card resmi
                'img[alt*="kategori"]',                          // Alt text'te kategori geçen
                'img[alt*="menu"]'                               // Alt text'te menu geçen
            ];

            foreach ($imageSelectors as $selector) {
                $imageElements = $crawler->filter($selector);

                if ($imageElements->count() > 0) {
                    $firstImage = $imageElements->first();

                    // Background image style'dan URL çıkar
                    if (strpos($selector, 'background-image') !== false) {
                        $style = $firstImage->attr('style');
                        if (preg_match('/background-image:\s*url\(["\']?([^"\']+)["\']?\)/', $style, $matches)) {
                            $imageUrl = $matches[1];
                            if (strpos($imageUrl, 'http') !== 0) {
                                $imageUrl = 'https://notifybee.com.tr' . $imageUrl;
                            }
                            return $imageUrl;
                        }
                    } else {
                        // Normal img src'den URL al
                        $imageUrl = $firstImage->attr('src');
                        if (!empty($imageUrl)) {
                            if (strpos($imageUrl, 'http') !== 0) {
                                $imageUrl = 'https://notifybee.com.tr' . $imageUrl;
                            }
                            return $imageUrl;
                        }
                    }
                }
            }

            // Eğer kategori sayfasında resim bulunamazsa, ilk ürün resmini al
            $productImages = $crawler->filter('.food-background div[style*="background-image"]');
            if ($productImages->count() > 0) {
                $style = $productImages->first()->attr('style');
                if (preg_match('/background-image:\s*url\(["\']?([^"\']+)["\']?\)/', $style, $matches)) {
                    $imageUrl = $matches[1];
                    if (strpos($imageUrl, 'http') !== 0) {
                        $imageUrl = 'https://notifybee.com.tr' . $imageUrl;
                    }
                    return $imageUrl;
                }
            }

            return ''; // Resim bulunamadı
        } catch (\Exception $e) {
            error_log("Category image extraction error for $categoryUrl: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Download and save category images, return ZIP file path
     */
    public function downloadCategoryImages($categories, $zipFileName = null)
    {
        try {
            if (empty($categories)) {
                return ['success' => false, 'error' => 'Kategori listesi boş'];
            }

            // ZIP dosya adı oluştur
            if (!$zipFileName) {
                $zipFileName = 'category_images_' . date('Y-m-d_H-i-s') . '.zip';
            }

            $zipPath = __DIR__ . '/../downloads/' . $zipFileName;

            // Downloads klasörünü oluştur
            $downloadsDir = dirname($zipPath);
            if (!is_dir($downloadsDir)) {
                mkdir($downloadsDir, 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                return ['success' => false, 'error' => 'ZIP dosyası oluşturulamadı'];
            }

            $downloadedCount = 0;
            $totalCount = 0;

            foreach ($categories as $category) {
                $totalCount++;

                if (empty($category['image'])) {
                    continue;
                }

                try {
                    // Resmi indir
                    $imageResponse = $this->client->get($category['image']);
                    $imageData = $imageResponse->getBody()->getContents();

                    if (!empty($imageData)) {
                        // Dosya uzantısını belirle
                        $imageUrl = $category['image'];
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (empty($extension)) {
                            $extension = 'jpg'; // Default
                        }

                        // Güvenli dosya adı oluştur
                        $safeFileName = $this->createSafeFileName($category['name']) . '.' . $extension;

                        // ZIP'e ekle
                        $zip->addFromString($safeFileName, $imageData);
                        $downloadedCount++;

                        error_log("Downloaded image: " . $category['name'] . " -> " . $safeFileName);
                    }
                } catch (\Exception $e) {
                    error_log("Image download error for " . $category['name'] . ": " . $e->getMessage());
                    continue;
                }

                // Small delay between downloads
                usleep(200000); // 0.2 seconds
            }

            $zip->close();

            return [
                'success' => true,
                'zip_path' => $zipPath,
                'zip_filename' => $zipFileName,
                'downloaded_count' => $downloadedCount,
                'total_count' => $totalCount,
                'zip_size' => filesize($zipPath)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create safe filename from category name
     */
    private function createSafeFileName($categoryName)
    {
        // Türkçe karakterleri değiştir
        $turkishChars = ['ç', 'ğ', 'ı', 'ö', 'ş', 'ü', 'Ç', 'Ğ', 'I', 'İ', 'Ö', 'Ş', 'Ü'];
        $englishChars = ['c', 'g', 'i', 'o', 's', 'u', 'C', 'G', 'I', 'I', 'O', 'S', 'U'];
        $categoryName = str_replace($turkishChars, $englishChars, $categoryName);

        // Güvenli karakterlere dönüştür
        $categoryName = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $categoryName);
        $categoryName = preg_replace('/\s+/', '_', trim($categoryName));
        $categoryName = substr($categoryName, 0, 50); // Max 50 karakter

        return $categoryName;
    }

    /**
     * Test selectors on a URL to see what data can be extracted
     */
    public function testSelectors($url, $selectors)
    {
        try {
            // Fetch HTML content
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();

            if (empty($html)) {
                return ['success' => false, 'error' => 'Sayfa içeriği boş'];
            }

            // Create DOM crawler
            $crawler = new Crawler($html);

            $results = [];

            foreach ($selectors as $key => $selector) {
                if (empty($selector)) continue;

                try {
                    $elements = $crawler->filter($selector);
                    $results[$key] = [
                        'selector' => $selector,
                        'found_count' => $elements->count(),
                        'sample_data' => []
                    ];

                    // Get first 3 samples
                    $sampleCount = min(3, $elements->count());
                    for ($i = 0; $i < $sampleCount; $i++) {
                        $element = $elements->eq($i);
                        $results[$key]['sample_data'][] = [
                            'text' => trim($element->text()),
                            'html' => $element->html()
                        ];
                    }
                } catch (\Exception $e) {
                    $results[$key] = [
                        'selector' => $selector,
                        'found_count' => 0,
                        'error' => $e->getMessage(),
                        'sample_data' => []
                    ];
                }
            }

            return ['success' => true, 'results' => $results];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
