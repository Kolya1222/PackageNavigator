<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Package Navigator</title>
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="{{ MODX_BASE_URL }}assets/modules/packagenavigator/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ MODX_BASE_URL }}assets/modules/packagenavigator/fontawesome-7.1.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .package-card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            background: white;
            margin-bottom: 1.5rem;
        }

        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .nav-tabs-modern {
            border-bottom: 2px solid #e9ecef;
        }

        .nav-tabs-modern .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
        }

        .nav-tabs-modern .nav-link:hover {
            color: #495057;
            background-color: transparent;
        }

        .nav-tabs-modern .nav-link.active {
            color: #667eea;
            background-color: transparent;
            border: none;
            border-bottom: 3px solid #667eea;
        }

        .btn-modern {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
        }

        .alert-modern {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
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

        <!-- Alert Containers -->
        <div class="row mb-4">
            <div class="col-12">
                <div id="alertContainer"></div>
                <div id="alertContainerMarketplace"></div>
            </div>
        </div>

        <!-- Main Content -->
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
                    <!-- Installed Packages Tab -->
                    <div class="tab-pane fade show active" id="installed" role="tabpanel">
                        @include('PackageNavigator::installed')
                    </div>

                    <!-- Marketplace Tab -->
                    <div class="tab-pane fade" id="marketplace" role="tabpanel">
                        @include('PackageNavigator::marketplace')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & JavaScript -->
    <script src="{{ MODX_BASE_URL }}assets/modules/packagenavigator/js/bootstrap.bundle.min.js"></script>

    <script>
    // Modern Package Navigator with enhanced features
    window.PackageNavigator = {
        // Initialize the application
        init: function() {
            this.bindEvents();
            this.showAlert('Package Navigator успешно загружен!', 'success', 'alertContainer');
        },

        // Show alert message
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
            
            // Add to beginning of container
            alertContainer.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) alert.remove();
            }, 5000);
        },

        // Get CSRF token
        getCsrfToken: function() {
            return document.querySelector('meta[name="csrf-token"]')?.content || 
                   document.querySelector('input[name="_token"]')?.value;
        },

        // AJAX request helper
        ajaxRequest: async function(url, data = {}, method = 'POST') {
            const formData = new FormData();
            formData.append('_token', this.getCsrfToken());
            
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

        // Package name validation
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

        // Install package (common method)
        installPackage: async function(packageName, version = '*', buttonElement = null) {
            if (!this.validatePackageName(packageName)) {
                return false;
            }

            const originalHtml = buttonElement ? buttonElement.innerHTML : null;
        
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
                if (buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalHtml;
                }
            }
        },

        // Remove package (common method)
        removePackage: async function(packageName, buttonElement = null) {
            if (!confirm(`Вы уверены, что хотите удалить пакет "${packageName}"?\n\nБудут удалены все связанные service providers.`)) {
                return false;
            }

            const originalHtml = buttonElement ? buttonElement.innerHTML : null;
        
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
                if (buttonElement) {
                    buttonElement.disabled = false;
                    buttonElement.innerHTML = originalHtml;
                }
            }
        },

        // Toggle package details
        togglePackageDetails: function(packageName, event) {
            const detailsId = 'details-' + packageName.replace(/\//g, '-');
            const detailsElement = document.getElementById(detailsId);
        
            if (!detailsElement) {
                console.error('Package details element not found:', detailsId);
                return;
            }
        
            const icon = event.currentTarget.querySelector('.fa');
        
            if (detailsElement.style.display === 'none' || !detailsElement.style.display) {
                detailsElement.style.display = 'block';
                if (icon) icon.className = 'fa fa-chevron-up small';
            } else {
                detailsElement.style.display = 'none';
                if (icon) icon.className = 'fa fa-chevron-down small';
            }
        },

        // Bind event listeners
        bindEvents: function() {
            // Package details toggle
            document.addEventListener('click', function(e) {
                if (e.target.closest('.package-name')) {
                    const packageNameElement = e.target.closest('.package-name');
                    const packageName = packageNameElement.dataset.package;
                    PackageNavigator.togglePackageDetails(packageName, e);
                }
            
                // Remove package buttons
                if (e.target.closest('.remove-package')) {
                    const btn = e.target.closest('.remove-package');
                    PackageNavigator.removePackage(btn.dataset.package, btn);
                }
            
                // Install remote package buttons
                if (e.target.closest('.install-remote-package')) {
                    const btn = e.target.closest('.install-remote-package');
                    PackageNavigator.installPackage(btn.dataset.package, '*', btn);
                }
            });
        }
    };

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        PackageNavigator.init();
    });
    </script>

    @stack('scripts')
</body>
</html>