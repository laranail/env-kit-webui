<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Providers;

use Livewire\Livewire;
use Illuminate\Http\Request;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\EnvKit\WebUI\Doctor\Checks;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;
use Simtabi\Laranail\EnvKit\WebUI\Livewire\EnvKitPanelComponent;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\EnvKitSecurityHeaders;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\EnsureEnvKitWebUIAccess;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\RequireEnvKitWebUIToken;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AboutSectionDefinition;

final class EnvKitWebUIServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/env-kit-webui')
            ->hasConfigFile('env-kit-webui')
            ->hasAboutSection(
                AboutSectionDefinition::make('Env Kit Web UI')
                    ->field('Version', fn (): string => (string) InstalledVersions::getPrettyVersion('laranail/env-kit-webui'))
                    ->field('Enabled', fn (): bool => (bool) config('laranail.env-kit-webui.enabled', false)),
            )
            ->hasDoctorChecks(Checks::all());
    }

    public function packageRegistered(): void
    {
        // Singleton so consumer-registered theme adapters persist for the request.
        $this->app->singleton(ThemeManager::class);
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'laranail-env-kit-webui');
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), 'laranail-env-kit-webui');

        $this->registerThrottle();

        $config = $this->app->make(Repository::class);
        // The lockdown guards are PREPENDED by the package (not in the overridable
        // config list), so they can't be accidentally dropped. The API additionally
        // gets the throttle + the optional secret-token gate.
        $this->registerRoutes($config, 'route.prefix', 'route.middleware', 'api/v1/env-kit', ['api'], 'api.php', [
            EnsureEnvKitWebUIAccess::class, EnvKitSecurityHeaders::class, 'throttle:env-kit', RequireEnvKitWebUIToken::class,
        ]);
        $this->registerRoutes($config, 'route.web_prefix', 'route.web_middleware', 'env-kit', ['web'], 'web.php', [
            EnsureEnvKitWebUIAccess::class, EnvKitSecurityHeaders::class,
        ]);

        // The reactive panel is optional — registered only when Livewire is present.
        if (class_exists(Livewire::class)) {
            Livewire::component('env-kit-panel', EnvKitPanelComponent::class);
        }
    }

    private function registerThrottle(): void
    {
        RateLimiter::for('env-kit', function (Request $request): Limit {
            if (! config('laranail.env-kit-webui.throttle.enabled', true)) {
                return Limit::none();
            }

            $configured = config('laranail.env-kit-webui.throttle.per_minute', 30);
            $max = is_numeric($configured) ? (int) $configured : 30;
            $id = $request->user()?->getAuthIdentifier();
            $key = is_scalar($id) ? (string) $id : (string) $request->ip();

            return Limit::perMinute(max(1, $max))->by('env-kit:' . $key);
        });
    }

    /**
     * @param list<string> $fallbackMiddleware
     * @param list<string> $prepend
     */
    private function registerRoutes(
        Repository $config,
        string $prefixKey,
        string $middlewareKey,
        string $fallbackPrefix,
        array $fallbackMiddleware,
        string $routeFile,
        array $prepend,
    ): void {
        $prefix = $config->get("laranail.env-kit-webui.{$prefixKey}", $fallbackPrefix);
        $middleware = $config->get("laranail.env-kit-webui.{$middlewareKey}", $fallbackMiddleware);

        Route::group([
            'prefix'     => is_string($prefix) ? $prefix : $fallbackPrefix,
            'middleware' => array_merge(
                $prepend,
                is_array($middleware) ? array_values($middleware) : $fallbackMiddleware,
            ),
        ], function () use ($routeFile): void {
            $this->loadRoutesFrom($this->packagePath('routes/' . $routeFile));
        });
    }
}
