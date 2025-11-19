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
            <!-- Форма через Composer -->
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

            <!-- Форма загрузки архива -->
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
            removeBtn.innerHTML = originalHtml;
            removeBtn.disabled = false;
        }
    } catch (error) {
        showAlert(`Ошибка сети: ${error.message}`, 'danger');
        removeBtn.innerHTML = originalHtml;
        removeBtn.disabled = false;
    }
}
</script>