@foreach($packages as $package)
<div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100">
        <div class="card-body">
            <h5 class="card-title">{{ $package['display_name'] ?? $package['name'] }}</h5>
            <h6 class="card-subtitle mb-2 text-muted small">{{ $package['composer_name'] ?? $package['name'] }}</h6>
            <p class="card-text">{{ $package['description'] ?? 'Описание отсутствует' }}</p>
            
            @if(isset($package['categories']) && is_array($package['categories']))
            <div class="mb-2">
                @foreach($package['categories'] as $category)
                <span class="badge bg-primary me-1 mb-1">{{ $category }}</span>
                @endforeach
            </div>
            @endif
            
            @if(isset($package['tags']) && is_array($package['tags']))
            <div class="mb-2">
                @foreach($package['tags'] as $tag)
                <span class="badge bg-light text-dark border me-1 mb-1 small">{{ $tag }}</span>
                @endforeach
            </div>
            @endif
            
            <div class="text-muted small">
                @if(isset($package['author']))
                <div>Автор: {{ $package['author'] }}</div>
                @endif
                @if(isset($package['type']))
                <div>Тип: {{ $package['type'] }}</div>
                @endif
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-success btn-sm install-remote-package" 
                    data-package="{{ $package['composer_name'] ?? $package['name'] }}">
                <i class="fa fa-download"></i> Установить
            </button>
            @if(isset($package['repository']))
            <a href="{{ $package['repository'] }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="GitHub">
                <i class="fa fa-github"></i>
            </a>
            @endif
            @if(isset($package['documentation_url']))
            <a href="{{ $package['documentation_url'] }}" target="_blank" class="btn btn-outline-info btn-sm" title="Документация">
                <i class="fa fa-book"></i>
            </a>
            @endif
        </div>
    </div>
</div>
@endforeach