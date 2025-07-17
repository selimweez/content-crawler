<?php
require_once 'vendor/autoload.php';

use ContentCrawler\MenuCrawler;
use ContentCrawler\DataExporter;

session_start();

$crawler = new MenuCrawler();
$exporter = new DataExporter();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'test_selectors':
            $url = $_POST['url'] ?? '';
            $selectors = [
                'container' => $_POST['container_selector'] ?? '',
                'item' => $_POST['item_selector'] ?? '',
                'name' => $_POST['name_selector'] ?? '',
                'description' => $_POST['description_selector'] ?? '',
                'price' => $_POST['price_selector'] ?? '',
                'image' => $_POST['image_selector'] ?? ''
            ];

            $result = $crawler->testSelectors($url, $selectors);
            echo json_encode($result);
            exit;

        case 'crawl_menu':
            $url = $_POST['url'] ?? '';
            $selectors = [
                'container' => $_POST['container_selector'] ?? '',
                'item' => $_POST['item_selector'] ?? '',
                'name' => $_POST['name_selector'] ?? '',
                'description' => $_POST['description_selector'] ?? '',
                'price' => $_POST['price_selector'] ?? '',
                'image' => $_POST['image_selector'] ?? ''
            ];

            $result = $crawler->crawlMenu($url, $selectors);

            if ($result['success']) {
                // Initialize session array if not exists
                if (!isset($_SESSION['last_crawl_data'])) {
                    $_SESSION['last_crawl_data'] = [];
                }

                // Add category and source_url to each item
                $category = $_POST['category'] ?? 'Kategori Yok';
                $itemsWithCategory = array_map(function($item) use ($category, $url) {
                    $item['category'] = $category;
                    $item['source_url'] = $url;
                    return $item;
                }, $result['data']);

                // Append to existing data instead of replacing
                $_SESSION['last_crawl_data'] = array_merge($_SESSION['last_crawl_data'], $itemsWithCategory);
            }

            echo json_encode($result);
            exit;

        case 'export_data':
            $format = $_POST['format'] ?? 'json';
            $data = $_SESSION['last_crawl_data'] ?? [];

            if (empty($data)) {
                echo json_encode(['success' => false, 'error' => 'Önce veri çekmelisiniz']);
                exit;
            }

            switch ($format) {
                case 'json':
                    $result = $exporter->exportToJson($data);
                    break;
                case 'csv':
                    $result = $exporter->exportToCsv($data);
                    break;
                case 'excel':
                    $result = $exporter->exportToExcel($data);
                    break;
                default:
                    echo json_encode(['success' => false, 'error' => 'Geçersiz format']);
                    exit;
            }

            if ($result['success']) {
                // Save to session for download
                $_SESSION['export_data'] = $result;
                echo json_encode(['success' => true, 'filename' => $result['filename']]);
            } else {
                echo json_encode($result);
            }
            exit;

        case 'clear_session':
            // Clear previous crawl data
            $_SESSION['last_crawl_data'] = [];
            echo json_encode(['success' => true]);
            exit;
    }
}

// Handle file download
if (isset($_GET['download']) && isset($_SESSION['export_data'])) {
    $exporter->downloadFile($_SESSION['export_data']);
    unset($_SESSION['export_data']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Content Crawler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .selector-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .test-results {
            max-height: 300px;
            overflow-y: auto;
        }
        .menu-item {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .loading {
            display: none;
        }
        .loading.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-spider text-primary"></i>
                    Menu Content Crawler
                </h1>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cog"></i> Crawler Ayarları</h5>
                    </div>
                    <div class="card-body">
                        <form id="crawlerForm">
                            <div class="mb-3">
                                <label class="form-label">Website URL'leri ve Kategoriler</label>
                                <div id="urlContainer">
                                    <div class="url-group mb-2">
                                        <div class="row">
                                            <div class="col-7">
                                                <input type="url" class="form-control url-input" placeholder="https://example.com/menu" required>
                                            </div>
                                            <div class="col-4">
                                                <input type="text" class="form-control category-input" placeholder="Kategori adı">
                                            </div>
                                            <div class="col-1">
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeUrlGroup(this)" title="Sil">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addUrlGroup()">
                                    <i class="fas fa-plus"></i> URL Ekle
                                </button>
                            </div>

                            <div class="selector-group">
                                <h6><i class="fas fa-bullseye"></i> CSS Seçiciler</h6>

                                <div class="mb-3">
                                    <label for="container_selector" class="form-label">Menü Konteyneri</label>
                                    <input type="text" class="form-control" id="container_selector"
                                           placeholder=".menu-container, #menu, .food-list">
                                </div>

                                <div class="mb-3">
                                    <label for="item_selector" class="form-label">Menü Öğesi</label>
                                    <input type="text" class="form-control" id="item_selector"
                                           placeholder=".menu-item, .food-item, li">
                                </div>

                                <div class="mb-3">
                                    <label for="name_selector" class="form-label">Ürün Adı</label>
                                    <input type="text" class="form-control" id="name_selector"
                                           placeholder=".name, .title, h3">
                                </div>

                                <div class="mb-3">
                                    <label for="description_selector" class="form-label">Açıklama</label>
                                    <input type="text" class="form-control" id="description_selector"
                                           placeholder=".description, .desc, p">
                                </div>

                                <div class="mb-3">
                                    <label for="price_selector" class="form-label">Fiyat</label>
                                    <input type="text" class="form-control" id="price_selector"
                                           placeholder=".price, .cost, .amount">
                                </div>

                                <div class="mb-3">
                                    <label for="image_selector" class="form-label">Resim</label>
                                    <input type="text" class="form-control" id="image_selector"
                                           placeholder="img, .image img, .photo">
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <button type="button" class="btn btn-success btn-sm w-100" onclick="saveSelectors()">
                                            <i class="fas fa-save"></i> Kaydet
                                        </button>
                                    </div>
                                    <div class="col-6">
                                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="clearSelectors()">
                                            <i class="fas fa-trash"></i> Temizle
                                        </button>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-outline-primary" onclick="testSelectors(event)">
                                    <i class="fas fa-vial"></i> Seçicileri Test Et
                                    <span class="loading spinner-border spinner-border-sm ms-2"></span>
                                </button>
                                <button type="button" class="btn btn-primary" onclick="crawlMenu(event)">
                                    <i class="fas fa-play"></i> Veri Çek
                                    <span class="loading spinner-border spinner-border-sm ms-2"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-bar"></i> Sonuçlar</h5>
                    </div>
                    <div class="card-body">
                        <div id="results"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-database"></i> Çekilen Veriler</h5>
                        <div id="exportButtons" style="display: none;">
                            <button class="btn btn-success btn-sm me-2" onclick="exportData('json')">
                                <i class="fas fa-file-code"></i> JSON
                            </button>
                            <button class="btn btn-info btn-sm me-2" onclick="exportData('csv')">
                                <i class="fas fa-file-csv"></i> CSV
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="exportData('excel')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="crawledData"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/crawler.js"></script>
</body>
</html>
