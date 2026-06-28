<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\EnsureEnvKitWebUIEnabled;
use Simtabi\Laranail\EnvKit\WebUI\Livewire\EnvKitPanelComponent;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

final class EnvKitWebUIServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/env-kit-webui')
            ->hasConfigFile('env-kit-webui');
    }

    public function packageRegistered(): void
    {
        // Singleton so consumer-registered theme adapters persist for the request.
        $this->app->singleton(ThemeManager::class);
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'env-kit-webui');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'env-kit-webui');

        $config = $this->app->make(Repository::class);
        $this->registerRoutes($config, 'route.prefix', 'route.middleware', 'api/v1/env-kit', ['api'], 'api.php');
        $this->registerRoutes($config, 'route.web_prefix', 'route.web_middleware', 'env-kit', ['web'], 'web.php');

        // The reactive panel is optional — registered only when Livewire is present.
        if (class_exists(Livewire::class)) {
            Livewire::component('env-kit-panel', EnvKitPanelComponent::class);
        }
    }

    /**
     * @param  list<string>  $fallbackMiddleware
     */
    private function registerRoutes(
        Repository $config,
        string $prefixKey,
        string $middlewareKey,
        string $fallbackPrefix,
        array $fallbackMiddleware,
        string $routeFile,
    ): void {
        $prefix = $config->get("env-kit-webui.{$prefixKey}", $fallbackPrefix);
        $middleware = $config->get("env-kit-webui.{$middlewareKey}", $fallbackMiddleware);

        Route::group([
            'prefix' => is_string($prefix) ? $prefix : $fallbackPrefix,
            // The enabled-gate runs first on every request, so toggling config at
            // runtime takes effect without re-registering routes.
            'middleware' => array_merge(
                [EnsureEnvKitWebUIEnabled::class],
                is_array($middleware) ? array_values($middleware) : $fallbackMiddleware,
            ),
        ], function () use ($routeFile): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/'.$routeFile);
        });
    }
}
