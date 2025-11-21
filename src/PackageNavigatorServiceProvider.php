<?php namespace roilafx\PackageNavigator;

use EvolutionCMS\ServiceProvider;

class PackageNavigatorServiceProvider extends ServiceProvider
{
    protected $namespace = 'PackageNavigator';

    public function register()
    {
        $this->app->singleton(Services\PackageManagerService::class, function ($app) {
            return new Services\PackageManagerService($app);
        });
        $this->app->registerRoutingModule(
            'Package Navigator',
            __DIR__ . '/../routes/inner_routes.php',
            'fa fa-boxes'
        );
        $this->publishes([
            __DIR__ . '/../publishable/assets'  => MODX_BASE_PATH . 'assets',
        ]);
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../views', $this->namespace);
    }
}