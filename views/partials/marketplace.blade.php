<div id="alertContainerMarketplace"></div>

<div class="row">
    <!-- Левая панель фильтрации -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fa fa-filter"></i> Фильтры</h6>
            </div>
            <div class="card-body">
                <!-- Поиск -->
                <div class="mb-3">
                    <label class="form-label small fw-bold">Поиск</label>
                    <input type="text" class="form-control form-control-sm" id="searchFilter" placeholder="Название или описание...">
                </div>

                <!-- Категории -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold mb-0">Категории</label>
                        <span class="badge bg-secondary" id="categoriesCount"></span>
                    </div>
                    <div class="filter-categories" style="max-height: 200px; overflow-y: auto;">
                        <!-- Динамически заполняется JavaScript -->
                    </div>
                </div>

                <!-- Теги -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold mb-0">Теги</label>
                        <span class="badge bg-secondary" id="tagsCount"></span>
                    </div>
                    <div class="filter-tags" style="max-height: 200px; overflow-y: auto;">
                        <!-- Динамически заполняется JavaScript -->
                    </div>
                </div>

                <!-- Типы -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold mb-0">Типы</label>
                        <span class="badge bg-secondary" id="typesCount"></span>
                    </div>
                    <div class="filter-types" style="max-height: 150px; overflow-y: auto;">
                        <!-- Динамически заполняется JavaScript -->
                    </div>
                </div>

                <!-- Кнопки управления -->
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-secondary btn-sm" id="resetFilters">
                        <i class="fa fa-refresh"></i> Сбросить фильтры
                    </button>
                    <div class="small text-muted text-center" id="filterResults">
                        Найдено: {{ count($remotePackages) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Правая панель с пакетами -->
    <div class="col-md-9">
        <div class="row" id="packagesContainer">
            @include('PackageNavigator::partials.packages_grid', ['packages' => $remotePackages])
        </div>
    </div>
</div>

<script>
// Данные всех пакетов для фильтрации
const allPackages = @json($remotePackages);

// Функция для получения доступных опций на основе текущих фильтров
function getAvailableOptions(filteredPackages) {
    const availableCategories = new Set();
    const availableTags = new Set();
    const availableTypes = new Set();
    
    const categoryCounts = {};
    const tagCounts = {};
    const typeCounts = {};

    filteredPackages.forEach(package => {
        // Категории
        (package.categories || []).forEach(category => {
            availableCategories.add(category);
            categoryCounts[category] = (categoryCounts[category] || 0) + 1;
        });
        
        // Теги
        (package.tags || []).forEach(tag => {
            availableTags.add(tag);
            tagCounts[tag] = (tagCounts[tag] || 0) + 1;
        });
        
        // Типы
        if (package.type) {
            const types = package.type.split('/').map(t => t.trim());
            types.forEach(type => {
                availableTypes.add(type);
                typeCounts[type] = (typeCounts[type] || 0) + 1;
            });
        }
    });

    return {
        categories: Array.from(availableCategories).sort(),
        tags: Array.from(availableTags).sort(),
        types: Array.from(availableTypes).sort(),
        categoryCounts,
        tagCounts,
        typeCounts
    };
}

// Обновление интерфейса фильтров
function updateFiltersUI(availableOptions, resetSelection = false) {
    // Обновляем категории
    updateFilterSection('.filter-categories', availableOptions.categories, availableOptions.categoryCounts, 'category', resetSelection);
    
    // Обновляем теги
    updateFilterSection('.filter-tags', availableOptions.tags, availableOptions.tagCounts, 'tag', resetSelection);
    
    // Обновляем типы
    updateFilterSection('.filter-types', availableOptions.types, availableOptions.typeCounts, 'type', resetSelection);
    
    // Обновляем счетчики
    document.getElementById('categoriesCount').textContent = availableOptions.categories.length;
    document.getElementById('tagsCount').textContent = availableOptions.tags.length;
    document.getElementById('typesCount').textContent = availableOptions.types.length;
    
    // Показываем все пакеты
    updatePackagesDisplay(allPackages);
    
    // Обновляем счетчик
    document.getElementById('filterResults').textContent = `Найдено: ${allPackages.length}`;
}

// Обновление конкретной секции фильтров
function updateFilterSection(selector, availableItems, counts, type, resetSelection = false) {
    const container = document.querySelector(selector);
    
    let html = '';
    availableItems.forEach(item => {
        const isChecked = resetSelection ? false : Array.from(container.querySelectorAll('input:checked')).map(cb => cb.value).includes(item);
        const count = counts[item] || 0;
        const itemId = `${type}_${item.replace(/[^a-zA-Z0-9]/g, '_')}`;
        
        html += `
            <div class="form-check">
                <input class="form-check-input ${type}-filter" type="checkbox" value="${item}" 
                       id="${itemId}" ${isChecked ? 'checked' : ''}>
                <label class="form-check-label small" for="${itemId}">
                    ${item} <span class="text-muted">(${count})</span>
                </label>
            </div>
        `;
    });
    
    container.innerHTML = html || '<div class="text-muted small">Нет доступных опций</div>';
    
    // Добавляем обработчики событий для новых чекбоксов
    container.querySelectorAll('input').forEach(checkbox => {
        checkbox.addEventListener('change', filterPackages);
    });
}

// Основная функция фильтрации
function filterPackages() {
    console.log('Filtering packages...');
    
    const searchText = document.getElementById('searchFilter').value.toLowerCase();
    const selectedCategories = Array.from(document.querySelectorAll('.category-filter:checked')).map(cb => cb.value);
    const selectedTags = Array.from(document.querySelectorAll('.tag-filter:checked')).map(cb => cb.value);
    const selectedTypes = Array.from(document.querySelectorAll('.type-filter:checked')).map(cb => cb.value);

    console.log('Selected categories:', selectedCategories);
    console.log('Selected tags:', selectedTags);
    console.log('Selected types:', selectedTypes);

    const filteredPackages = allPackages.filter(package => {
        // Поиск по названию и описанию
        if (searchText) {
            const nameMatch = package.name?.toLowerCase().includes(searchText) || false;
            const descMatch = package.description?.toLowerCase().includes(searchText) || false;
            if (!nameMatch && !descMatch) {
                return false;
            }
        }

        // Фильтр по категориям
        if (selectedCategories.length > 0) {
            const packageCategories = package.categories || [];
            if (!selectedCategories.some(cat => packageCategories.includes(cat))) {
                return false;
            }
        }

        // Фильтр по тегам
        if (selectedTags.length > 0) {
            const packageTags = package.tags || [];
            if (!selectedTags.some(tag => packageTags.includes(tag))) {
                return false;
            }
        }

        // Фильтр по типам
        if (selectedTypes.length > 0) {
            const packageType = package.type || '';
            const packageTypes = packageType.split('/').map(t => t.trim());
            if (!selectedTypes.some(type => packageTypes.includes(type))) {
                return false;
            }
        }

        return true;
    });

    console.log('Filtered packages count:', filteredPackages.length);

    // Получаем доступные опции для отфильтрованных пакетов
    const availableOptions = getAvailableOptions(filteredPackages);
    
    // Обновляем интерфейс фильтров
    updateFiltersUI(availableOptions);
    
    // Обновляем отображение пакетов
    updatePackagesDisplay(filteredPackages);
}

// Обновление отображения пакетов
function updatePackagesDisplay(packages) {
    const container = document.getElementById('packagesContainer');
    const resultsCounter = document.getElementById('filterResults');
    
    console.log('Updating packages display:', packages.length);
    
    if (packages.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    <i class="fa fa-search fa-3x mb-3"></i>
                    <p>Пакеты не найдены</p>
                    <small>Попробуйте изменить параметры фильтрации</small>
                </div>
            </div>
        `;
    } else {
        let html = '';
        packages.forEach(package => {
            const categoriesHtml = package.categories && package.categories.length 
                ? `<div class="mb-2">${package.categories.map(cat => `<span class="badge bg-primary me-1 mb-1">${cat}</span>`).join('')}</div>`
                : '';

            const tagsHtml = package.tags && package.tags.length 
                ? `<div class="mb-2">${package.tags.map(tag => `<span class="badge bg-light text-dark border me-1 mb-1 small">${tag}</span>`).join('')}</div>`
                : '';

            const authorHtml = package.author ? `<div>Автор: ${package.author}</div>` : '';
            const typeHtml = package.type ? `<div>Тип: ${package.type}</div>` : '';

            html += `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title">${package.display_name || package.name}</h5>
                            <h6 class="card-subtitle mb-2 text-muted small">${package.composer_name || package.name}</h6>
                            <p class="card-text">${package.description || 'Описание отсутствует'}</p>
                            ${categoriesHtml}
                            ${tagsHtml}
                            <div class="text-muted small">
                                ${authorHtml}
                                ${typeHtml}
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-success btn-sm install-remote-package" 
                                    data-package="${package.composer_name || package.name}">
                                <i class="fa fa-download"></i> Установить
                            </button>
                            ${package.repository ? `<a href="${package.repository}" target="_blank" class="btn btn-outline-secondary btn-sm" title="GitHub"><i class="fa fa-github"></i></a>` : ''}
                            ${package.documentation_url ? `<a href="${package.documentation_url}" target="_blank" class="btn btn-outline-info btn-sm" title="Документация"><i class="fa fa-book"></i></a>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        container.innerHTML = html;
    }
    
    resultsCounter.textContent = `Найдено: ${packages.length}`;
}

// Обработчики событий
document.getElementById('searchFilter').addEventListener('input', filterPackages);

document.getElementById('resetFilters').addEventListener('click', function() {
    resetAllFilters();
});

// Функция полного сброса фильтров
function resetAllFilters() {
    console.log('Resetting all filters...');
    
    // Сбрасываем поле поиска
    document.getElementById('searchFilter').value = '';
    
    // Полностью пересоздаем фильтры БЕЗ сохранения состояния
    const initialOptions = getAvailableOptions(allPackages);
    updateFiltersUI(initialOptions, true); // true = сбросить выбор
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing filters...');
    // При инициализации тоже сбрасываем выбор
    const initialOptions = getAvailableOptions(allPackages);
    updateFiltersUI(initialOptions, true);
});

// Функции для установки пакетов
async function installRemotePackage(packageName) {
    const installBtn = document.querySelector(`.install-remote-package[data-package="${packageName}"]`);
    const originalHtml = installBtn.innerHTML;
    
    installBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    installBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('_token', getCsrfToken());
        formData.append('package', packageName);

        const response = await fetch("{{ route('packagenavigator.install-remote') }}", {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            showAlert(`Пакет "${packageName}" успешно установлен!`, 'success', 'alertContainerMarketplace');
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert(`Ошибка установки: ${result.error}`, 'danger', 'alertContainerMarketplace');
            installBtn.innerHTML = originalHtml;
            installBtn.disabled = false;
        }
    } catch (error) {
        showAlert(`Ошибка сети: ${error.message}`, 'danger', 'alertContainerMarketplace');
        installBtn.innerHTML = originalHtml;
        installBtn.disabled = false;
    }
}

// Обработчик для динамически созданных кнопок установки
document.addEventListener('click', function(e) {
    if (e.target.closest('.install-remote-package')) {
        const btn = e.target.closest('.install-remote-package');
        installRemotePackage(btn.dataset.package);
    }
});

// Вспомогательные функции
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || 
           document.querySelector('input[name="_token"]')?.value;
}

function showAlert(message, type = 'info', containerId = 'alertContainer') {
    const alertContainer = document.getElementById(containerId);
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHtml;
    
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) alert.remove();
    }, 5000);
}
</script>