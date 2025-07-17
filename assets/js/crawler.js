// Menu Crawler JavaScript Functions

// LocalStorage keys
const STORAGE_KEYS = {
    SELECTORS: 'crawler_selectors',
    LAST_URL: 'crawler_last_url'
};

function showLoading(button) {
    const loading = button.querySelector('.loading');
    if (loading) {
        loading.classList.add('show');
        button.disabled = true;
    }
}

function hideLoading(button) {
    const loading = button.querySelector('.loading');
    if (loading) {
        loading.classList.remove('show');
        button.disabled = false;
    }
}

// Add new URL group
function addUrlGroup() {
    const container = document.getElementById('urlContainer');
    const newGroup = document.createElement('div');
    newGroup.className = 'url-group mb-2';
    newGroup.innerHTML = `
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
    `;
    container.appendChild(newGroup);
}

// Remove URL group
function removeUrlGroup(button) {
    const urlGroups = document.querySelectorAll('.url-group');
    if (urlGroups.length > 1) {
        button.closest('.url-group').remove();
    } else {
        showError('En az bir URL gerekli!');
    }
}

// Get all URLs and categories
function getUrlsAndCategories() {
    const urlGroups = document.querySelectorAll('.url-group');
    const urlsAndCategories = [];

    urlGroups.forEach(group => {
        const url = group.querySelector('.url-input').value.trim();
        const category = group.querySelector('.category-input').value.trim() || 'Kategori Yok';

        if (url) {
            urlsAndCategories.push({ url, category });
        }
    });

    return urlsAndCategories;
}

// Save selectors to localStorage
function saveSelectors(showMessage = true) {
    const urlsAndCategories = getUrlsAndCategories();

    const selectors = {
        urls_and_categories: urlsAndCategories,
        container_selector: document.getElementById('container_selector').value,
        item_selector: document.getElementById('item_selector').value,
        name_selector: document.getElementById('name_selector').value,
        description_selector: document.getElementById('description_selector').value,
        price_selector: document.getElementById('price_selector').value,
        image_selector: document.getElementById('image_selector').value,
        saved_at: new Date().toISOString()
    };

    localStorage.setItem(STORAGE_KEYS.SELECTORS, JSON.stringify(selectors));

    if (showMessage) {
        showSuccess('Seçiciler kaydedildi! 💾');
    }
}

// Load selectors from localStorage
function loadSelectors() {
    try {
        const saved = localStorage.getItem(STORAGE_KEYS.SELECTORS);
        if (saved) {
            const selectors = JSON.parse(saved);

            // Load URLs and categories
            if (selectors.urls_and_categories && selectors.urls_and_categories.length > 0) {
                const container = document.getElementById('urlContainer');
                container.innerHTML = ''; // Clear existing

                selectors.urls_and_categories.forEach((item) => {
                    const newGroup = document.createElement('div');
                    newGroup.className = 'url-group mb-2';
                    newGroup.innerHTML = `
                        <div class="row">
                            <div class="col-7">
                                <input type="url" class="form-control url-input" value="${item.url}" required>
                            </div>
                            <div class="col-4">
                                <input type="text" class="form-control category-input" value="${item.category || ''}">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeUrlGroup(this)" title="Sil">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    container.appendChild(newGroup);
                });
            } else if (selectors.url) {
                // Backward compatibility - old single URL format
                const container = document.getElementById('urlContainer');
                container.innerHTML = `
                    <div class="url-group mb-2">
                        <div class="row">
                            <div class="col-7">
                                <input type="url" class="form-control url-input" value="${selectors.url}" required>
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
                `;
            }

            // Load CSS selectors
            document.getElementById('container_selector').value = selectors.container_selector || '';
            document.getElementById('item_selector').value = selectors.item_selector || '';
            document.getElementById('name_selector').value = selectors.name_selector || '';
            document.getElementById('description_selector').value = selectors.description_selector || '';
            document.getElementById('price_selector').value = selectors.price_selector || '';
            document.getElementById('image_selector').value = selectors.image_selector || '';

            // Show when it was saved
            if (selectors.saved_at) {
                const savedDate = new Date(selectors.saved_at).toLocaleString('tr-TR');
                showInfo(`Kaydedilmiş seçiciler yüklendi (${savedDate}) - ${selectors.urls_and_categories ? selectors.urls_and_categories.length : 1} URL 📂`);
            }
        }
    } catch (error) {
        console.error('Seçiciler yüklenirken hata:', error);
        showError('Kaydedilmiş seçiciler yüklenirken hata oluştu: ' + error.message);
    }
}



// Clear saved selectors
function clearSelectors() {
    if (confirm('Kaydedilmiş seçicileri silmek istediğinizden emin misiniz?')) {
        localStorage.removeItem(STORAGE_KEYS.SELECTORS);

        // Clear URLs - keep only one empty group
        const container = document.getElementById('urlContainer');
        container.innerHTML = `
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
        `;

        // Clear CSS selectors
        document.getElementById('container_selector').value = '';
        document.getElementById('item_selector').value = '';
        document.getElementById('name_selector').value = '';
        document.getElementById('description_selector').value = '';
        document.getElementById('price_selector').value = '';
        document.getElementById('image_selector').value = '';

        showSuccess('Seçiciler temizlendi! 🗑️');
    }
}

function testSelectors(event) {
    const button = event.target;
    showLoading(button);

    // Auto-save selectors before testing (silently)
    saveSelectors(false);

    const urlsAndCategories = getUrlsAndCategories();

    if (urlsAndCategories.length === 0) {
        hideLoading(button);
        showError('En az bir URL girmelisiniz!');
        return;
    }

    // Test only the first URL for now
    const firstUrl = urlsAndCategories[0];

    const formData = new FormData();
    formData.append('action', 'test_selectors');
    formData.append('url', firstUrl.url);
    formData.append('container_selector', document.getElementById('container_selector').value);
    formData.append('item_selector', document.getElementById('item_selector').value);
    formData.append('name_selector', document.getElementById('name_selector').value);
    formData.append('description_selector', document.getElementById('description_selector').value);
    formData.append('price_selector', document.getElementById('price_selector').value);
    formData.append('image_selector', document.getElementById('image_selector').value);

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading(button);
        displayTestResults(data, firstUrl.category);
    })
    .catch(error => {
        hideLoading(button);
        showError('Test sırasında hata oluştu: ' + error.message);
    });
}

function crawlMenu(event) {
    const button = event.target;
    showLoading(button);

    // Auto-save selectors before crawling (silently)
    saveSelectors(false);

    const urlsAndCategories = getUrlsAndCategories();

    if (urlsAndCategories.length === 0) {
        hideLoading(button);
        showError('En az bir URL girmelisiniz!');
        return;
    }

    // Crawl multiple URLs sequentially
    crawlMultipleUrls(urlsAndCategories, button);
}

async function crawlMultipleUrls(urlsAndCategories, button) {
    const allResults = [];
    let totalCount = 0;

    try {
        // Clear previous session data before starting
        await clearSessionData();

        for (let i = 0; i < urlsAndCategories.length; i++) {
            const { url, category } = urlsAndCategories[i];

            // Update progress
            showInfo(`İşleniyor: ${category} (${i + 1}/${urlsAndCategories.length}) 🔄`);

            const formData = new FormData();
            formData.append('action', 'crawl_menu');
            formData.append('url', url);
            formData.append('category', category); // Send category to PHP
            formData.append('container_selector', document.getElementById('container_selector').value);
            formData.append('item_selector', document.getElementById('item_selector').value);
            formData.append('name_selector', document.getElementById('name_selector').value);
            formData.append('description_selector', document.getElementById('description_selector').value);
            formData.append('price_selector', document.getElementById('price_selector').value);
            formData.append('image_selector', document.getElementById('image_selector').value);

            const response = await fetch('index.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Add category to each item for display
                const itemsWithCategory = data.data.map(item => ({
                    ...item,
                    category: category,
                    source_url: url
                }));

                allResults.push(...itemsWithCategory);
                totalCount += data.count;
            } else {
                console.error(`Hata (${category}):`, data.error);
                showError(`Hata (${category}): ${data.error}`);
                // Continue with other URLs even if one fails
            }

            // Small delay between requests
            await new Promise(resolve => setTimeout(resolve, 500));
        }

        hideLoading(button);

        // Display combined results
        const combinedData = {
            success: true,
            data: allResults,
            count: totalCount,
            urls: urlsAndCategories.length,
            timestamp: new Date().toISOString()
        };

        displayCrawledData(combinedData);
        document.getElementById('exportButtons').style.display = 'block';

    } catch (error) {
        hideLoading(button);
        showError('Crawling sırasında hata oluştu: ' + error.message);
    }
}

// Clear session data before starting new crawl
async function clearSessionData() {
    const formData = new FormData();
    formData.append('action', 'clear_session');

    try {
        await fetch('index.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Session temizlenirken hata:', error);
    }
}

function exportData(format) {
    const formData = new FormData();
    formData.append('action', 'export_data');
    formData.append('format', format);

    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Trigger download
            window.location.href = 'index.php?download=1';
            showSuccess(`${format.toUpperCase()} dosyası başarıyla oluşturuldu: ${data.filename}`);
        } else {
            showError(data.error);
        }
    })
    .catch(error => {
        showError('Export sırasında hata oluştu: ' + error.message);
    });
}

function displayTestResults(data) {
    const resultsDiv = document.getElementById('results');

    if (!data.success) {
        resultsDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Hata:</strong> ${data.error}
            </div>
        `;
        return;
    }

    let html = '<div class="alert alert-success"><i class="fas fa-check"></i> Test başarılı!</div>';
    html += '<div class="test-results">';

    for (const [key, result] of Object.entries(data.results)) {
        html += `
            <div class="mb-3">
                <h6><i class="fas fa-tag"></i> ${key.charAt(0).toUpperCase() + key.slice(1)}</h6>
                <small class="text-muted">Seçici: <code>${result.selector}</code></small><br>
                <small class="text-success">Bulunan: ${result.found_count} element</small>

                ${result.sample_data.length > 0 ? `
                    <div class="mt-2">
                        <strong>Örnek veriler:</strong>
                        ${result.sample_data.map((sample, index) => `
                            <div class="border rounded p-2 mt-1 small">
                                <strong>#${index + 1}:</strong> ${sample.text || 'Boş'}
                            </div>
                        `).join('')}
                    </div>
                ` : ''}
            </div>
        `;
    }

    html += '</div>';
    resultsDiv.innerHTML = html;
}

function displayCrawledData(data) {
    const dataDiv = document.getElementById('crawledData');

    if (!data.success) {
        dataDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Hata:</strong> ${data.error}
            </div>
        `;
        return;
    }

    let html = `
        <div class="alert alert-success">
            <i class="fas fa-check"></i>
            <strong>Başarılı!</strong> ${data.count} menü öğesi çekildi.
            ${data.urls ? `<br><small>Toplam URL: ${data.urls}</small>` : ''}
            ${data.url ? `<br><small>URL: ${data.url}</small>` : ''}
            <br><small>Zaman: ${new Date(data.timestamp).toLocaleString('tr-TR')}</small>
        </div>
    `;

    if (data.data.length > 0) {
        // Group by category if available
        const groupedData = {};
        data.data.forEach(item => {
            const category = item.category || 'Kategori Yok';
            if (!groupedData[category]) {
                groupedData[category] = [];
            }
            groupedData[category].push(item);
        });

        // Display by categories
        Object.keys(groupedData).forEach(category => {
            html += `
                <div class="mb-4">
                    <h5 class="text-secondary border-bottom pb-2">
                        <i class="fas fa-tag"></i> ${category}
                        <span class="badge bg-secondary ms-2">${groupedData[category].length}</span>
                    </h5>
                    <div class="row">
            `;

            groupedData[category].forEach(item => {
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="menu-item">
                            ${item.image ? `
                                <img src="${item.image}" class="img-fluid rounded mb-2"
                                     style="max-height: 150px; width: 100%; object-fit: cover;"
                                     onerror="this.style.display='none'">
                            ` : ''}

                            <h6 class="text-primary">${item.name || 'İsimsiz'}</h6>

                            ${item.description ? `
                                <p class="text-muted small">${item.description}</p>
                            ` : ''}

                            ${item.price ? `
                                <div class="text-success fw-bold">${item.price}</div>
                            ` : ''}

                            ${item.source_url ? `
                                <div class="text-muted small mt-2">
                                    <i class="fas fa-link"></i>
                                    <a href="${item.source_url}" target="_blank" class="text-decoration-none">
                                        ${new URL(item.source_url).hostname}
                                    </a>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });
    }

    dataDiv.innerHTML = html;
}

function showError(message) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Hata:</strong> ${message}
        </div>
    `;
}

function showSuccess(message) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = `
        <div class="alert alert-success">
            <i class="fas fa-check"></i>
            ${message}
        </div>
    `;
}

function showInfo(message) {
    const resultsDiv = document.getElementById('results');
    resultsDiv.innerHTML = `
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            ${message}
        </div>
    `;
}

// Auto-fill common selectors based on URL (for dynamic URL inputs)
function autoFillSelectors(url) {
    const urlLower = url.toLowerCase();

    // Common patterns for different restaurant platforms
    if (urlLower.includes('yemeksepeti') || urlLower.includes('getir')) {
        document.getElementById('container_selector').value = '.restaurant-menu, .menu-category';
        document.getElementById('item_selector').value = '.menu-item, .product-item';
        document.getElementById('name_selector').value = '.product-name, .item-name, h3';
        document.getElementById('description_selector').value = '.product-description, .item-description';
        document.getElementById('price_selector').value = '.price, .product-price';
        document.getElementById('image_selector').value = '.product-image img, .item-image img';
    } else if (urlLower.includes('zomato')) {
        document.getElementById('container_selector').value = '.menu-container';
        document.getElementById('item_selector').value = '.menu-item';
        document.getElementById('name_selector').value = '.item-name';
        document.getElementById('description_selector').value = '.item-description';
        document.getElementById('price_selector').value = '.item-price';
        document.getElementById('image_selector').value = '.item-image img';
    } else if (urlLower.includes('notifybee')) {
        document.getElementById('container_selector').value = '.arabas';
        document.getElementById('item_selector').value = '.vertical-menu-list__item';
        document.getElementById('name_selector').value = 'h6';
        document.getElementById('description_selector').value = 'p';
        document.getElementById('price_selector').value = '.float-start';
        document.getElementById('image_selector').value = '.food-background div[style*="background-image"]';
    }
}

// Debug function to check localStorage
function debugLocalStorage() {
    const saved = localStorage.getItem(STORAGE_KEYS.SELECTORS);
    console.log('LocalStorage data:', saved);
    if (saved) {
        try {
            const parsed = JSON.parse(saved);
            console.log('Parsed data:', parsed);
            console.log('URLs count:', parsed.urls_and_categories ? parsed.urls_and_categories.length : 0);
        } catch (e) {
            console.error('Parse error:', e);
        }
    }
}

// Auto-save selectors when they change
document.addEventListener('DOMContentLoaded', function() {
    // Debug localStorage on page load
    console.log('Page loaded, checking localStorage...');
    debugLocalStorage();

    // Load saved selectors on page load
    loadSelectors();

    // Auto-save when selectors change
    const selectorInputs = [
        'container_selector', 'item_selector',
        'name_selector', 'description_selector', 'price_selector', 'image_selector'
    ];

    selectorInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('input', function() {
                // Debounce auto-save (wait 1 second after user stops typing)
                clearTimeout(this.saveTimeout);
                this.saveTimeout = setTimeout(() => {
                    saveSelectors(false); // Silent auto-save
                }, 1000);
            });
        }
    });

    // Add event listeners for URL inputs (they are dynamic)
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('url-input') || e.target.classList.contains('category-input')) {
            clearTimeout(window.urlSaveTimeout);
            window.urlSaveTimeout = setTimeout(() => {
                saveSelectors(false); // Silent auto-save
            }, 1000);
        }
    });

    // Add blur event listener for auto-filling selectors
    document.addEventListener('blur', function(e) {
        if (e.target.classList.contains('url-input')) {
            const url = e.target.value.trim();
            if (url) {
                autoFillSelectors(url);
            }
        }
    }, true);
});
