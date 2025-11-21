<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Package Navigator</title>
    <link rel="stylesheet" href="{{ MODX_BASE_URL }}assets/modules/packagenavigator/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ MODX_BASE_URL }}assets/modules/packagenavigator/fontawesome-7.1.0/css/all.min.css">
    <link rel="stylesheet" href="{{ MODX_BASE_URL }}assets/modules/packagenavigator/css/main.css">
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="fas fa-boxes me-2 text-primary"></i>
                            Package Navigator
                        </h1>
                        <p class="text-muted mb-0">Управление пакетами и дополнениями</p>
                    </div>
                    <button class="btn btn-secondary btn-modern" onclick="location.reload();">
                        <i class="fas fa-sync-alt me-2"></i>Обновить
                    </button>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-12">
                <div id="alertContainer"></div>
                <div id="alertContainerMarketplace"></div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs nav-tabs-modern mb-4" id="packageTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="installed-tab" data-bs-toggle="tab" 
                                data-bs-target="#installed" type="button" role="tab">
                            <i class="fas fa-list me-2"></i>Установленные пакеты
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="marketplace-tab" data-bs-toggle="tab" 
                                data-bs-target="#marketplace" type="button" role="tab">
                            <i class="fas fa-store me-2"></i>Магазин дополнений
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="packageTabsContent">
                    <div class="tab-pane fade show active" id="installed" role="tabpanel">
                        @include('PackageNavigator::installed')
                    </div>
                    <div class="tab-pane fade" id="marketplace" role="tabpanel">
                        @include('PackageNavigator::marketplace')
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ MODX_BASE_URL }}assets/modules/packagenavigator/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * Основной объект PackageNavigator для управления пакетами
         * @namespace PackageNavigator
         */
        window.PackageNavigator = {
            /**
             * Инициализация PackageNavigator
             */
            init: function() {
                this.bindEvents();
                this.showAlert('Package Navigator успешно загружен!', 'success', 'alertContainer');
            },

            /**
             * Показывает уведомление пользователю
             * @param {string} message - Текст сообщения
             * @param {string} type - Тип уведомления (success, danger, warning, info)
             * @param {string} containerId - ID контейнера для уведомления
             */
            showAlert: function(message, type = 'info', containerId = 'alertContainer') {
                const alertContainer = document.getElementById(containerId);
                if (!alertContainer) {
                    console.warn('Alert container not found:', containerId);
                    return;
                }
                const alertId = 'alert-' + Date.now();
                const alertHtml = `
                    <div id="${alertId}" class="alert alert-${type} alert-modern alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                    </div>
                `;
                alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
                
                // Автоматическое скрытие уведомления через 5 секунд
                setTimeout(() => {
                    const alert = document.getElementById(alertId);
                    if (alert) alert.remove();
                }, 5000);
            },

            /**
             * Получает CSRF токен из мета-тега
             * @returns {string} CSRF токен
             */
            getCsrfToken: function() {
                return document.querySelector('meta[name="csrf-token"]')?.content || 
                    document.querySelector('input[name="_token"]')?.value;
            },

            /**
             * Выполняет AJAX запрос к серверу
             * @param {string} url - URL для запроса
             * @param {Object} data - Данные для отправки
             * @param {string} method - HTTP метод (POST, GET, etc.)
             * @returns {Promise<Object>} Promise с ответом сервера
             * @throws {Error} Если запрос не удался
             */
            ajaxRequest: async function(url, data = {}, method = 'POST') {
                const formData = new FormData();
                formData.append('_token', this.getCsrfToken());
                
                // Добавляем все данные в FormData
                for (const [key, value] of Object.entries(data)) {
                    formData.append(key, value);
                }
            
                try {
                    const response = await fetch(url, {
                        method: method,
                        body: formData
                    });
                
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                
                    return await response.json();
                } catch (error) {
                    console.error('AJAX request failed:', error);
                    throw error;
                }
            },

            /**
             * Проверяет корректность имени пакета
             * @param {string} packageName - Имя пакета для проверки
             * @returns {boolean} true если имя корректно
             */
            validatePackageName: function(packageName) {
                if (!packageName) {
                    this.showAlert('Пожалуйста, введите название пакета', 'warning');
                    return false;
                }

                if (!packageName.includes('/')) {
                    this.showAlert('Некорректный формат пакета. Используйте: vendor/package-name', 'warning');
                    return false;
                }

                return true;
            },

            /**
             * Устанавливает пакет через Composer
             * @param {string} packageName - Имя пакета для установки
             * @param {string} version - Версия пакета (по умолчанию '*')
             * @param {HTMLElement} buttonElement - Элемент кнопки для отображения состояния
             * @returns {Promise<boolean>} Promise с результатом установки
             */
            installPackage: async function(packageName, version = '*', buttonElement = null) {
                if (!this.validatePackageName(packageName)) {
                    return false;
                }

                const originalHtml = buttonElement ? buttonElement.innerHTML : null;
            
                // Показываем состояние загрузки на кнопке
                if (buttonElement) {
                    buttonElement.disabled = true;
                    buttonElement.innerHTML = '<span class="loading-spinner me-2"></span>Установка...';
                }

                try {
                    const result = await this.ajaxRequest(
                        "{{ route('packagenavigator.install') }}",
                        { package: packageName, version: version }
                    );
                
                    if (result.success) {
                        this.showAlert(`Пакет "${packageName}" успешно установлен!`, 'success');
                        setTimeout(() => location.reload(), 2000);
                        return true;
                    } else {
                        this.showAlert(`Ошибка установки: ${result.error}`, 'danger');
                        return false;
                    }
                } catch (error) {
                    this.showAlert(`Ошибка сети: ${error.message}`, 'danger');
                    return false;
                } finally {
                    // Восстанавливаем исходное состояние кнопки
                    if (buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.innerHTML = originalHtml;
                    }
                }
            },

            /**
             * Обновляет пакет до последней версии
             * @param {string} packageName - Имя пакета для обновления
             * @param {HTMLElement} buttonElement - Элемент кнопки для отображения состояния
             * @returns {Promise<boolean>} Promise с результатом обновления
             */
            updatePackage: async function(packageName, buttonElement = null) {
                if (!this.validatePackageName(packageName)) {
                    return false;
                }

                const originalHtml = buttonElement ? buttonElement.innerHTML : null;

                if (buttonElement) {
                    buttonElement.disabled = true;
                    buttonElement.innerHTML = '<span class="loading-spinner me-2"></span>Обновление...';
                }

                try {
                    const result = await this.ajaxRequest(
                        "{{ route('packagenavigator.update') }}",
                        { package: packageName }
                    );

                    if (result.success) {
                        this.showAlert(`Пакет "${packageName}" успешно обновлен!`, 'success');
                        setTimeout(() => location.reload(), 2000);
                        return true;
                    } else {
                        this.showAlert(`Ошибка обновления: ${result.error}`, 'danger');
                        return false;
                    }
                } catch (error) {
                    this.showAlert(`Ошибка сети: ${error.message}`, 'danger');
                    return false;
                } finally {
                    if (buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.innerHTML = originalHtml;
                    }
                }
            },

            /**
             * Удаляет установленный пакет
             * @param {string} packageName - Имя пакета для удаления
             * @param {HTMLElement} buttonElement - Элемент кнопки для отображения состояния
             * @returns {Promise<boolean>} Promise с результатом удаления
             */
            removePackage: async function(packageName, buttonElement = null) {
                if (!confirm(`Вы уверены, что хотите удалить пакет "${packageName}"?\n\nБудут удалены все связанные service providers.`)) {
                    return false;
                }

                const originalHtml = buttonElement ? buttonElement.innerHTML : null;
            
                // Показываем состояние загрузки на кнопке
                if (buttonElement) {
                    buttonElement.disabled = true;
                    buttonElement.innerHTML = '<span class="loading-spinner me-2"></span>Удаление...';
                }

                try {
                    const result = await this.ajaxRequest(
                        "{{ route('packagenavigator.remove') }}",
                        { package: packageName }
                    );
                
                    if (result.success) {
                        let message = `Пакет "${packageName}" успешно удален!`;
                        if (result.removed_files && result.removed_files.length > 0) {
                            message += `<br>Удаленные файлы: ${result.removed_files.join(', ')}`;
                        }
                        this.showAlert(message, 'success');
                        setTimeout(() => location.reload(), 1000);
                        return true;
                    } else {
                        this.showAlert(`Ошибка удаления: ${result.error}`, 'danger');
                        return false;
                    }
                } catch (error) {
                    this.showAlert(`Ошибка сети: ${error.message}`, 'danger');
                    return false;
                } finally {
                    // Восстанавливаем исходное состояние кнопки
                    if (buttonElement) {
                        buttonElement.disabled = false;
                        buttonElement.innerHTML = originalHtml;
                    }
                }
            },

            /**
             * Переключает отображение детальной информации о пакете
             * @param {string} packageName - Имя пакета
             * @param {Event} event - Событие клика
             */
            togglePackageDetails: function(packageName, event) {
                const detailsId = 'details-' + packageName.replace(/\//g, '-');
                const detailsElement = document.getElementById(detailsId);
            
                if (!detailsElement) {
                    console.error('Package details element not found:', detailsId);
                    return;
                }
            
                const icon = event.currentTarget.querySelector('.fa');
            
                // Переключаем видимость деталей и иконку
                if (detailsElement.style.display === 'none' || !detailsElement.style.display) {
                    detailsElement.style.display = 'block';
                    if (icon) icon.className = 'fa fa-chevron-up small';
                } else {
                    detailsElement.style.display = 'none';
                    if (icon) icon.className = 'fa fa-chevron-down small';
                }
            },

            /**
             * Привязывает обработчики событий к элементам
             */
            bindEvents: function() {
                document.addEventListener('click', function(e) {
                    // Обработка клика по имени пакета для показа деталей
                    if (e.target.closest('.package-name')) {
                        const packageNameElement = e.target.closest('.package-name');
                        const packageName = packageNameElement.dataset.package;
                        PackageNavigator.togglePackageDetails(packageName, e);
                    }

                    // Обработка обновления пакета
                    if (e.target.closest('.update-package')) {
                        const btn = e.target.closest('.update-package');
                        PackageNavigator.updatePackage(btn.dataset.package, btn);
                    }

                    // Обработка удаления пакета
                    if (e.target.closest('.remove-package')) {
                        const btn = e.target.closest('.remove-package');
                        PackageNavigator.removePackage(btn.dataset.package, btn);
                    }
                    
                    // Обработка установки пакета из магазина
                    if (e.target.closest('.install-remote-package')) {
                        const btn = e.target.closest('.install-remote-package');
                        PackageNavigator.installPackage(btn.dataset.package, '*', btn);
                    }
                });
            }
        };

        /**
         * Инициализация при загрузке DOM
         */
        document.addEventListener('DOMContentLoaded', function() {
            PackageNavigator.init();
        });
    </script>

    @stack('scripts')
</body>
</html>