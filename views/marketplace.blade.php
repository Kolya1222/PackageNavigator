{{-- core/vendor/roilafx/packagenavigator/views/partials/marketplace.blade.php --}}
<div class="tab-pane fade show active" id="marketplace" role="tabpanel">
    <div class="container-fluid px-0">
        <div class="row g-4">
            <!-- Левая панель фильтрации -->
            <div class="col-lg-3">
                <div class="card package-card">
                    <div class="card-header bg-transparent border-bottom-0 pb-0">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-filter text-primary me-2"></i>Фильтры
                        </h5>
                    </div>
                    <div class="card-body pt-0">
                        <!-- Поиск -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-uppercase text-muted mb-2">Поиск</label>
                            <div class="input-group input-group-modern">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" 
                                       id="searchFilter" placeholder="Название или описание...">
                            </div>
                        </div>

                        <!-- Категории -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted mb-0">Категории</label>
                                <span class="badge bg-primary rounded-pill" id="categoriesCount">0</span>
                            </div>
                            <div class="filter-categories filter-section">
                                <!-- Динамически заполняется JavaScript -->
                            </div>
                        </div>

                        <!-- Теги -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted mb-0">Теги</label>
                                <span class="badge bg-primary rounded-pill" id="tagsCount">0</span>
                            </div>
                            <div class="filter-tags filter-section">
                                <!-- Динамически заполняется JavaScript -->
                            </div>
                        </div>

                        <!-- Типы -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label small fw-bold text-uppercase text-muted mb-0">Типы</label>
                                <span class="badge bg-primary rounded-pill" id="typesCount">0</span>
                            </div>
                            <div class="filter-types filter-section">
                                <!-- Динамически заполняется JavaScript -->
                            </div>
                        </div>

                        <!-- Кнопки управления -->
                        <div class="d-grid gap-2 mt-4 pt-3 border-top">
                            <button class="btn btn-outline-secondary btn-modern" id="resetFilters">
                                <i class="fas fa-redo me-2"></i>Сбросить фильтры
                            </button>
                            <div class="text-center">
                                <small class="text-muted" id="filterResults">
                                    Найдено: <span class="fw-bold text-primary">{{ count($remotePackages) }}</span> пакетов
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Правая панель с пакетами -->
            <div class="col-lg-9">
                <div class="row g-4" id="packagesContainer">
                    <!-- Только динамическое содержимое через JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * Модуль фильтрации и отображения пакетов в маркетплейсе
     * @namespace MarketplaceFilter
     */

    /**
     * Данные всех пакетов для фильтрации
     * @type {Array}
     */
    const allPackages = @json($remotePackages);

    /**
     * Получает доступные опции фильтров на основе отфильтрованных пакетов
     * @function getAvailableOptions
     * @param {Array} filteredPackages - Отфильтрованный массив пакетов
     * @returns {Object} Объект с доступными категориями, тегами, типами и их счетчиками
     */
    function getAvailableOptions(filteredPackages) {
        const availableCategories = new Set();
        const availableTags = new Set();
        const availableTypes = new Set();
        
        const categoryCounts = new Map();
        const tagCounts = new Map();
        const typeCounts = new Map();

        filteredPackages.forEach((package, index) => {
            
            // Категории
            (package.categories || []).forEach(category => {
                availableCategories.add(category);
                const currentCount = categoryCounts.get(category) || 0;
                categoryCounts.set(category, currentCount + 1);
            });
            
            // Теги
            (package.tags || []).forEach(tag => {
                availableTags.add(tag);
                const currentCount = tagCounts.get(tag) || 0;
                tagCounts.set(tag, currentCount + 1);
            });
            
            // Типы
            if (package.type) {
                const types = package.type.split('/').map(t => t.trim());
                types.forEach(type => {
                    availableTypes.add(type);
                    const currentCount = typeCounts.get(type) || 0;
                    typeCounts.set(type, currentCount + 1);
                });
            }
        });
        
        return {
            categories: Array.from(availableCategories).sort(),
            tags: Array.from(availableTags).sort(),
            types: Array.from(availableTypes).sort(),
            categoryCounts: Object.fromEntries(categoryCounts),
            tagCounts: Object.fromEntries(tagCounts),
            typeCounts: Object.fromEntries(typeCounts)
        };
    }

    /**
     * Обновляет интерфейс фильтров на основе доступных опций
     * @function updateFiltersUI
     * @param {Object} availableOptions - Доступные опции фильтрации
     * @param {boolean} resetSelection - Сбрасывать ли выбранные фильтры
     */
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
        
        // Обновляем счетчик
        document.getElementById('filterResults').innerHTML = `Найдено: <span class="fw-bold text-primary">${allPackages.length}</span> пакетов`;
    }

    /**
     * Обновляет секцию фильтра с заданными опциями
     * @function updateFilterSection
     * @param {string} selector - CSS селектор контейнера фильтра
     * @param {Array} availableItems - Доступные элементы для фильтра
     * @param {Object} counts - Счетчики для каждого элемента
     * @param {string} type - Тип фильтра (category, tag, type)
     * @param {boolean} resetSelection - Сбрасывать ли выбранные элементы
     */
    function updateFilterSection(selector, availableItems, counts, type, resetSelection = false) {
        const container = document.querySelector(selector);
        
        let html = '';
        availableItems.forEach(item => {
            const isChecked = resetSelection ? false : Array.from(container.querySelectorAll('input:checked')).map(cb => cb.value).includes(item);
            let count = counts[item];
            
            // Проверяем, что count - это число
            if (typeof count !== 'number' || isNaN(count)) {
                // Если это не число, пытаемся преобразовать или устанавливаем 0
                count = parseInt(count) || 0;
            }
            
            const itemId = `${type}_${item.replace(/[^a-zA-Z0-9]/g, '_')}`;
            
            html += `
                <div class="filter-item ${isChecked ? 'filter-item-active' : ''}">
                    <div class="form-check">
                        <input class="form-check-input ${type}-filter" type="checkbox" value="${item}" 
                            id="${itemId}" ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label small" for="${itemId}">
                            <span class="filter-text">${item}</span>
                            <span class="filter-count">${count}</span>
                        </label>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<div class="text-muted small">Нет доступных опций</div>';
        
        // Добавляем обработчики событий для новых чекбоксов
        container.querySelectorAll('input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const filterItem = this.closest('.filter-item');
                if (this.checked) {
                    filterItem.classList.add('filter-item-active');
                } else {
                    filterItem.classList.remove('filter-item-active');
                }
                filterPackages();
            });
        });
    }

    /**
     * Основная функция фильтрации пакетов
     * @function filterPackages
     */
    function filterPackages() {
        const searchText = document.getElementById('searchFilter').value.toLowerCase();
        const selectedCategories = Array.from(document.querySelectorAll('.category-filter:checked')).map(cb => cb.value);
        const selectedTags = Array.from(document.querySelectorAll('.tag-filter:checked')).map(cb => cb.value);
        const selectedTypes = Array.from(document.querySelectorAll('.type-filter:checked')).map(cb => cb.value);

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

        // Получаем доступные опции для отфильтрованных пакетов
        const availableOptions = getAvailableOptions(filteredPackages);
        
        // Обновляем интерфейс фильтров
        updateFiltersUI(availableOptions);
        
        // Обновляем отображение пакетов
        updatePackagesDisplay(filteredPackages);
    }

    /**
     * Обновляет отображение пакетов в контейнере
     * @function updatePackagesDisplay
     * @param {Array} packages - Массив пакетов для отображения
     */
    function updatePackagesDisplay(packages) {
        const container = document.getElementById('packagesContainer');
        const resultsCounter = document.getElementById('filterResults');
        
        if (packages.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3 opacity-50"></i>
                        <h5 class="mb-2">Пакеты не найдены</h5>
                        <p class="small mb-0">Попробуйте изменить параметры фильтрации</p>
                    </div>
                </div>
            `;
        } else {
            let html = '';
            packages.forEach(package => {
                html += `
                    <div class="col-xl-4 col-lg-6 col-md-6">
                        <div class="card package-card h-100 fade-in">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title text-primary mb-1">
                                            ${package.display_name || package.name}
                                        </h6>
                                    </div>
                                </div>

                                <p class="card-text small text-muted mb-3">
                                    ${package.description || 'Описание отсутствует'}
                                </p>

                                ${package.categories && package.categories.length ? `
                                    <div class="mb-2">
                                        ${package.categories.map(cat => `<span class="badge bg-primary me-1 mb-1">${cat}</span>`).join('')}
                                    </div>
                                ` : ''}

                                ${package.tags && package.tags.length ? `
                                    <div class="mb-2">
                                        ${package.tags.map(tag => `<span class="badge bg-light text-dark border me-1 mb-1 small">${tag}</span>`).join('')}
                                    </div>
                                ` : ''}
                                
                                <div class="mt-2">
                                    ${package.author ? `<div class="small text-muted">Автор: ${package.author}</div>` : ''}
                                    ${package.type ? `<div class="small text-muted">Тип: ${package.type}</div>` : ''}
                                </div>
                            </div>
                            
                            <div class="card-footer bg-transparent border-top-0 pt-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <button class="btn btn-success btn-sm install-remote-package" 
                                            data-package="${package.composer_name || package.name}">
                                        <i class="fas fa-download me-1"></i>Установить
                                    </button>
                                    <div class="btn-group" role="group">
                                        ${package.repository ? `<a href="${package.repository}" target="_blank" class="btn btn-outline-secondary btn-sm" title="GitHub"><i class="fab fa-github"></i></a>` : ''}
                                        ${package.documentation_url ? `<a href="${package.documentation_url}" target="_blank" class="btn btn-outline-info btn-sm" title="Документация"><i class="fas fa-book"></i></a>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        }
        
        resultsCounter.innerHTML = `Найдено: <span class="fw-bold text-primary">${packages.length}</span> пакетов`;
    }

    /**
     * Полностью сбрасывает все фильтры
     * @function resetAllFilters
     */
    function resetAllFilters() {
        // Сбрасываем поле поиска
        document.getElementById('searchFilter').value = '';
        
        // Полностью пересоздаем фильтры БЕЗ сохранения состояния
        const initialOptions = getAvailableOptions(allPackages);
        updateFiltersUI(initialOptions, true);
    }

    /**
     * Инициализация обработчиков событий для маркетплейса
     * @function initializeMarketplace
     */
    function initializeMarketplace() {
        // Обработчик поиска
        document.getElementById('searchFilter').addEventListener('input', filterPackages);

        // Обработчик сброса фильтров
        document.getElementById('resetFilters').addEventListener('click', function() {
            resetAllFilters();
            filterPackages();
        });

        // Инициализация - сразу показываем все пакеты
        const initialOptions = getAvailableOptions(allPackages);
        updateFiltersUI(initialOptions, true);
        updatePackagesDisplay(allPackages);
    }
    /**
     * Инициализация при загрузке DOM
     */
    document.addEventListener('DOMContentLoaded', function() {
        initializeMarketplace();
    });
</script>