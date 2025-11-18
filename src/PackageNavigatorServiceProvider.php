<?php namespace roilafx\PackageNavigator;

use EvolutionCMS\ServiceProvider;

class PackageNavigatorServiceProvider extends ServiceProvider
{
    protected $namespace = 'PackageNavigator';

    public function register()
    {
        // Регистрируем сервис
        $this->app->singleton(Services\PackageManagerService::class, function ($app) {
            return new Services\PackageManagerService($app);
        });

        // Регистрируем маршруты для админки
        $this->app->registerRoutingModule(
            'Package Navigator',
            __DIR__ . '/../routes/inner_routes.php',
            'fa fa-boxes'
        );
    }

    public function boot()
    {
        // Загружаем views
        $this->loadViewsFrom(__DIR__ . '/../views', $this->namespace);
    }
}