<?php

// Configuration file for Menu Crawler

return [
    // Application settings
    'app' => [
        'name' => 'Menu Content Crawler',
        'version' => '1.0.0',
        'timezone' => 'Europe/Istanbul',
        'debug' => true
    ],
    
    // Crawler settings
    'crawler' => [
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'max_redirects' => 5,
        'verify_ssl' => false,
        'delay_between_requests' => 1, // seconds
        'max_items_per_crawl' => 1000
    ],
    
    // Export settings
    'export' => [
        'directory' => 'exports',
        'max_file_size' => '50MB',
        'allowed_formats' => ['json', 'csv', 'excel'],
        'filename_prefix' => 'menu_data_'
    ],
    
    // Common CSS selectors for popular platforms
    'presets' => [
        'yemeksepeti' => [
            'container' => '.restaurant-menu, .menu-category',
            'item' => '.menu-item, .product-item',
            'name' => '.product-name, .item-name, h3',
            'description' => '.product-description, .item-description',
            'price' => '.price, .product-price',
            'image' => '.product-image img, .item-image img'
        ],
        'getir' => [
            'container' => '.product-list, .menu-container',
            'item' => '.product-card, .menu-item',
            'name' => '.product-title, .item-title',
            'description' => '.product-description',
            'price' => '.product-price, .price',
            'image' => '.product-image img'
        ],
        'zomato' => [
            'container' => '.menu-container, .dish-container',
            'item' => '.menu-item, .dish-item',
            'name' => '.dish-name, .item-name',
            'description' => '.dish-description',
            'price' => '.dish-price, .price',
            'image' => '.dish-image img'
        ],
        'generic' => [
            'container' => '.menu, .food-menu, .restaurant-menu',
            'item' => '.menu-item, .food-item, .dish, li',
            'name' => '.name, .title, .dish-name, h3, h4',
            'description' => '.description, .desc, .details, p',
            'price' => '.price, .cost, .amount, .money',
            'image' => 'img, .image img, .photo img'
        ]
    ],
    
    // Logging settings
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => 'logs/crawler.log',
        'max_file_size' => '10MB',
        'max_files' => 5
    ],
    
    // Security settings
    'security' => [
        'allowed_domains' => [], // Empty array means all domains allowed
        'blocked_domains' => ['localhost', '127.0.0.1'],
        'max_url_length' => 2048,
        'rate_limit' => [
            'enabled' => true,
            'max_requests_per_minute' => 60,
            'max_requests_per_hour' => 1000
        ]
    ]
];
