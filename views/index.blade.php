@extends('PackageNavigator::app')
@push('styles')
<style>
.alert {
    position: relative;
    padding: 12px 16px;
    margin-bottom: 16px;
    border: 1px solid;
    border-radius: 3px;
    font-size: 13px;
    line-height: 1.4;
}

.alert-info {
    color: #004085;
    background-color: #cce7ff;
    border-color: #b3d9ff;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-warning {
    color: #856404;
    background-color: #fff3cd;
    border-color: #ffeaa7;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-dismissible {
    padding-right: 40px;
}

.btn-close {
    position: absolute;
    top: 50%;
    right: 12px;
    transform: translateY(-50%);
    padding: 0;
    background: none;
    border: 0;
    font-size: 18px;
    line-height: 1;
    color: inherit;
    opacity: 0.6;
    cursor: pointer;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-close:hover {
    opacity: 1;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 2px;
}

.alertContainer .btn, button:not(.btn), input[type=button]:not(.btn), input[type=submit]:not(.btn) {
    padding: 0px;
}

.btn-close::before {
    content: "×";
    display: block;
    font-size: 18px;
    line-height: 1;
    margin-top: -1px;
}

.package-details {
    transition: all 0.3s ease-in-out;
    max-height: 0;
    overflow: hidden;
}

.package-details[style*="display: block"] {
    max-height: 500px;
}
</style>
@endpush
@section('buttons')
<div id="actions">
    <div class="btn-group">
        <a href="javascript:;" class="btn btn-secondary" onclick="location.reload();">
            <i class="fa fa-refresh"></i><span> Обновить</span>
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="tab-page" id="tab_main">
    <h2 class="tab">Package Navigator - Управление пакетами Evolution CMS</h2>
    <script type="text/javascript">
        tpModule.addTabPage(document.getElementById('tab_main'));
    </script>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="container-fluid py-3">
        <!-- System Alerts -->
        <div id="alertContainer"></div>

        <!-- Install Package Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fa fa-download"></i> Установить новый пакет</h5>
            </div>
            <div class="card-body">
                <!-- Существующая форма через Composer -->
                <form id="installForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-9">
                            <input type="text" class="form-control" id="packageName" 
                                placeholder="vendor/package-name (например: evolution-cms/example)" required>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" id="packageVersion" 
                                placeholder="Версия" value="*">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100" id="installBtn">
                                <i class="fa fa-download"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Разделитель -->
                <div class="text-center my-3">
                    <span class="bg-light px-3 text-muted">ИЛИ</span>
                </div>

                <!-- Новая форма загрузки архива -->
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-9">
                            <input type="file" class="form-control" id="moduleArchive" 
                                accept=".zip,.tar.gz" required>
                            <div class="form-text">Поддерживаемые форматы: ZIP, TAR.GZ</div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success w-100" id="uploadBtn">
                                <i class="fa fa-upload"></i> Загрузить архив
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Installed Packages -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-list"></i> Установленные пакеты</h5>
                <div>
                    <span class="badge bg-primary me-2">{{ is_array($packages) ? count($packages) : 0 }} пакетов</span>
                    <span class="text-muted small">
                        Composer: {{ is_string($composerVersion) ? $composerVersion : 'Unknown' }}<br>
                    </span>
                </div>
            </div>
            <div class="card-body">
                @if(!is_array($packages) || empty($packages))
                    <div class="text-center text-muted py-4">
                        <i class="fa fa-inbox fa-3x mb-3"></i>
                        <p>Пакеты не установлены.</p>
                    </div>
                @else
                    <div id="packagesList">
                        @foreach($packages as $package)
                            @if(is_array($package) && isset($package['name']) && isset($package['version']))
                                <div class="package-item border-bottom py-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="flex-grow-1">
                                            <strong class="text-primary package-name" style="cursor: pointer;" 
                                                    data-package="{{ $package['name'] }}">
                                                {{ $package['name'] }}
                                                <i class="fa fa-chevron-down small"></i>
                                            </strong>
                                            <br>
                                            <span class="text-muted small">
                                                Версия: {{ $package['version'] }}
                                            </span>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-danger remove-package" 
                                                    data-package="{{ $package['name'] }}"
                                                    title="Удалить пакет">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Детальная информация о пакете -->
                                    <div id="details-{{ str_replace('/', '-', $package['name']) }}" class="package-details mt-2" style="display: none;">
                                        <div class="bg-light p-3 rounded">
                                            @if(isset($package['description']) && $package['description'])
                                                <p><strong>Описание:</strong> {{ $package['description'] }}</p>
                                            @endif
                                            
                                            @if(isset($package['providers']) && is_array($package['providers']) && count($package['providers']) > 0)
                                                <p><strong>Service Providers:</strong></p>
                                                <ul class="small">
                                                    @foreach($package['providers'] as $provider)
                                                        <li><code>{{ $provider }}</code></li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-muted small">Service providers не найдены</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Функция для показа/скрытия деталей пакета
    function togglePackageDetails(packageName, event) {
        const detailsId = 'details-' + packageName.replace(/\//g, '-');
        const detailsElement = document.getElementById(detailsId);
        
        if (!detailsElement) {
            console.error('Element not found:', detailsId);
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
    }

    // Функция для показа уведомлений
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alertContainer');
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
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }

    // Получить CSRF токен
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 
               document.querySelector('input[name="_token"]')?.value;
    }

    // Установка пакета
    document.getElementById('installForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await installPackage();
    });

    async function installPackage() {
        const packageName = document.getElementById('packageName').value.trim();
        const version = document.getElementById('packageVersion').value.trim() || '*';
        const installBtn = document.getElementById('installBtn');

        if (!packageName) {
            showAlert('Пожалуйста, введите название пакета', 'warning');
            return;
        }

        // Проверка формата package name
        if (!packageName.includes('/')) {
            showAlert('Некорректный формат пакета. Используйте: vendor/package-name', 'warning');
            return;
        }

        installBtn.disabled = true;
        installBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

        try {
            const formData = new FormData();
            formData.append('_token', getCsrfToken());
            formData.append('package', packageName);
            formData.append('version', version);

            const response = await fetch("{{ route('packagenavigator.install') }}", {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                showAlert(`Пакет "${packageName}" успешно установлен!`, 'success');
                document.getElementById('installForm').reset();
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(`Ошибка установки: ${result.error}`, 'danger');
                if (result.output) {
                    console.log('Composer output:', result.output);
                }
            }
        } catch (error) {
            showAlert(`Ошибка сети: ${error.message}`, 'danger');
        } finally {
            installBtn.disabled = false;
            installBtn.innerHTML = '<i class="fa fa-download"></i>';
        }
    }

    // Загрузка архива
    document.getElementById('uploadForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        await uploadModule();
    });

    async function uploadModule() {
        const fileInput = document.getElementById('moduleArchive');
        const uploadBtn = document.getElementById('uploadBtn');

        if (!fileInput.files.length) {
            showAlert('Пожалуйста, выберите файл архива', 'warning');
            return;
        }

        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Загрузка...';

        try {
            const formData = new FormData();
            formData.append('_token', getCsrfToken());
            formData.append('archive', fileInput.files[0]);

            const response = await fetch("{{ route('packagenavigator.upload') }}", {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                showAlert(`Модуль "${result.package}" успешно установлен из архива!`, 'success');
                fileInput.value = '';
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(`Ошибка установки: ${result.error}`, 'danger');
            }
        } catch (error) {
            showAlert(`Ошибка загрузки: ${error.message}`, 'danger');
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fa fa-upload"></i> Загрузить архив';
        }
    }

    // Удаление пакетов
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-package')) {
            const btn = e.target.closest('.remove-package');
            removePackage(btn.dataset.package);
        }
        
        // Обработка кликов по названиям пакетов
        if (e.target.closest('.package-name')) {
            const packageNameElement = e.target.closest('.package-name');
            const packageName = packageNameElement.dataset.package;
            togglePackageDetails(packageName, e);
        }
    });

    async function removePackage(packageName) {
        if (!confirm(`Вы уверены, что хотите удалить пакет "${packageName}"?\n\nБудут удалены все связанные service providers.`)) {
            return;
        }

        // Находим кнопку удаления и блокируем её
        const removeBtn = document.querySelector(`.remove-package[data-package="${packageName}"]`);
        const originalHtml = removeBtn.innerHTML;
        removeBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
        removeBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('_token', getCsrfToken());
            formData.append('package', packageName);

            const response = await fetch("{{ route('packagenavigator.remove') }}", {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                let message = `Пакет "${packageName}" успешно удален!`;
                if (result.removed_files && result.removed_files.length > 0) {
                    message += `<br>Удаленные файлы: ${result.removed_files.join(', ')}`;
                }
                showAlert(message, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert(`Ошибка удаления: ${result.error}`, 'danger');
                // Восстанавливаем кнопку при ошибке
                removeBtn.innerHTML = originalHtml;
                removeBtn.disabled = false;
            }
        } catch (error) {
            showAlert(`Ошибка сети: ${error.message}`, 'danger');
            // Восстанавливаем кнопку при ошибке
            removeBtn.innerHTML = originalHtml;
            removeBtn.disabled = false;
        }
    }

    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        showAlert('Package Navigator загружен!', 'info');
    });
</script>
@endpush