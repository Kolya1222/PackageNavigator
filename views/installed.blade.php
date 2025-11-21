<div class="container-fluid py-3">
    <!-- Install Package Forms -->
    <div class="card package-card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-rocket me-2 text-primary"></i> Установка пакетов</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Composer Install -->
                <div class="col-md-6">
                    <div class="h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                <i class="fas fa-download text-primary fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Установка через Composer</h6>
                                <p class="text-muted small mb-0">Установите пакет из репозитория Packagist</p>
                            </div>
                        </div>
                        
                        <form id="installForm" class="package-form">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Имя пакета</label>
                                <input type="text" class="form-control" id="packageName" 
                                    placeholder="vendor/package-name" required>
                                <div class="form-text">Например: evolution-cms/example</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Версия</label>
                                <input type="text" class="form-control" id="packageVersion" 
                                    placeholder="*" value="*">
                                <div class="form-text">Оставьте * для последней версии</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-modern" id="installBtn">
                                <i class="fas fa-download me-2"></i> Установить пакет
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Archive Upload -->
                <div class="col-md-6">
                    <div class="h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success bg-opacity-10 p-2 rounded me-3">
                                <i class="fas fa-upload text-success fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-1">Загрузка архива</h6>
                                <p class="text-muted small mb-0">Установите пакет из локального архива</p>
                            </div>
                        </div>

                        <form id="uploadForm" class="package-form" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Файл архива</label>
                                <input type="file" class="form-control" id="moduleArchive" 
                                    accept=".zip,.tar.gz" required>
                                <div class="form-text">Поддерживаемые форматы: ZIP, TAR.GZ</div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 btn-modern" id="uploadBtn">
                                <i class="fas fa-upload me-2"></i> Загрузить и установить
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="mt-4 pt-3 border-top">
                <h6 class="mb-3"><i class="fas fa-bolt me-2 text-warning"></i>Быстрые действия</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-primary btn-sm" onclick="document.getElementById('packageName').value = 'roilafx/constructorevo'">
                        <i class="fas fa-magic me-1"></i>Пример пакета
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('packageVersion').value = 'dev-main'">
                        <i class="fas fa-code-branch me-1"></i>Dev версия
                    </button>
                    <button class="btn btn-outline-info btn-sm" onclick="document.getElementById('packageVersion').value = '^1.0'">
                        <i class="fas fa-tag me-1"></i>Версия 1.0+
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Installed Packages List -->
    <div class="card package-card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-boxes me-2"></i> Установленные пакеты</h5>
                <small class="text-muted">Composer: {{ is_string($composerVersion) ? $composerVersion : 'Unknown' }}</small>
            </div>
            <div class="text-end">
                <span class="badge bg-primary rounded-pill">{{ is_array($packages) ? count($packages) : 0 }} пакетов</span>
            </div>
        </div>
        <div class="card-body">
            @if(!is_array($packages) || empty($packages))
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                    <p class="mb-2">Пакеты не установлены</p>
                    <small class="text-muted">Используйте форму выше для установки первого пакета</small>
                </div>
            @else
                <div id="packagesList">
                    @foreach($packages as $package)
                        @if(is_array($package) && isset($package['name']) && isset($package['version']))
                            <div class="package-item border-bottom py-3 fade-in">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <strong class="text-primary package-name" style="cursor: pointer;" 
                                                data-package="{{ $package['name'] }}">
                                            <i class="fas fa-cube me-2 text-muted"></i>
                                            {{ $package['name'] }}
                                            <i class="fas fa-chevron-down small ms-1"></i>
                                        </strong>
                                        <br>
                                        <span class="text-muted small">
                                            <i class="fas fa-tag me-1"></i>Версия: {{ $package['version'] }}
                                            @if(isset($package['description']) && $package['description'])
                                            &middot; {{ Str::limit($package['description'], 50) }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary update-package" 
                                                data-package="{{ $package['name'] }}"
                                                title="Обновить пакет">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger remove-package" 
                                                data-package="{{ $package['name'] }}"
                                                title="Удалить пакет">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Package Details -->
                                <div id="details-{{ str_replace('/', '-', $package['name']) }}" class="package-details mt-2" style="display: none;">
                                    <div class="bg-light p-3 rounded">
                                        @if(isset($package['description']) && $package['description'])
                                            <p class="mb-2"><strong>Описание:</strong> {{ $package['description'] }}</p>
                                        @endif
                                        
                                        @if(isset($package['providers']) && is_array($package['providers']) && count($package['providers']) > 0)
                                            <p class="mb-2"><strong>Service Providers:</strong></p>
                                            <ul class="small mb-0">
                                                @foreach($package['providers'] as $provider)
                                                    <li><code class="bg-white px-2 py-1 rounded">{{ $provider }}</code></li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted small mb-0">Service providers не найдены</p>
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

<script>
/**
 * Локальные обработчики форм для установленных пакетов
 * @namespace InstalledPackagesHandlers
 */
document.addEventListener('DOMContentLoaded', function() {
    /**
     * Обработчик формы установки через Composer
     */
    const installForm = document.getElementById('installForm');
    if (installForm) {
        installForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const packageName = document.getElementById('packageName').value.trim();
            const version = document.getElementById('packageVersion').value.trim() || '*';
            const installBtn = document.getElementById('installBtn');
            
            await PackageNavigator.installPackage(packageName, version, installBtn);
        });
    }

    /**
     * Обработчик формы загрузки архива
     */
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const fileInput = document.getElementById('moduleArchive');
            const uploadBtn = document.getElementById('uploadBtn');

            // Проверяем наличие выбранного файла
            if (!fileInput.files.length) {
                PackageNavigator.showAlert('Пожалуйста, выберите файл архива', 'warning');
                return;
            }

            // Показываем состояние загрузки
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="loading-spinner me-2"></span>Загрузка...';

            try {
                const formData = new FormData();
                formData.append('_token', PackageNavigator.getCsrfToken());
                formData.append('archive', fileInput.files[0]);
                // Выполняем запрос на загрузку архива
                const response = await fetch("{{ route('packagenavigator.upload') }}", {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    PackageNavigator.showAlert(`Модуль "${result.package}" успешно установлен из архива!`, 'success');
                    fileInput.value = '';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    PackageNavigator.showAlert(`Ошибка установки: ${result.error}`, 'danger');
                }
            } catch (error) {
                PackageNavigator.showAlert(`Ошибка загрузки: ${error.message}`, 'danger');
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Загрузить и установить';
            }
        });
    }
});
</script>