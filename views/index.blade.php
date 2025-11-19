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
    <!-- Вкладка установленных пакетов -->
    <div class="tab-page" id="tabInstalled">
        <h2 class="tab">Установленные пакеты</h2>
        <script type="text/javascript">tpModule.addTabPage(document.getElementById('tabInstalled'));</script>
        @include('PackageNavigator::partials.installed')
    </div>

    <!-- Вкладка магазина -->
    <div class="tab-page" id="tabMarketplace">
        <h2 class="tab">Магазин дополнений</h2>
        <script type="text/javascript">tpModule.addTabPage(document.getElementById('tabMarketplace'));</script>
        @include('PackageNavigator::partials.marketplace')
    </div>
@endsection

@push('scripts')
<script>
    // Общие функции JavaScript
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

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 
               document.querySelector('input[name="_token"]')?.value;
    }

    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        showAlert('Package Navigator загружен!', 'info', 'alertContainer');
    });
</script>
@endpush