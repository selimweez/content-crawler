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

            $items->each(function (Crawler $item) use (&$menuItems, $selectors, $url) {
                $menuItem = [
                    'name' => $this->extractText($item, $selectors['name']),
                    'description' => $this->extractText($item, $selectors['description']),
                    'price' => $this->extractText($item, $selectors['price']),
                    'image' => $this->extractImage($item, $selectors['image'], $url)
                ];

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
