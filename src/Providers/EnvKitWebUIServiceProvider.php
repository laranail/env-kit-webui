<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Providers;

use Livewire\Livewire;
use Illuminate\Http\Request;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\EnvKit\WebUI\Doctor\Checks;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;
use Simtabi\Laranail\EnvKit\WebUI\Support\RegisteredNames;
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
        $this->resolveBareRouteNames();

        $config = $this->app->make(Repository::class);
        // The lockdown guards are PREPENDED by the package (not in the overridable
        // config list), so they can't be accidentally dropped. The API additionally
        // gets the throttle + the optional secret-token gate.
        $this->registerRoutes($config, 'route.prefix', 'route.middleware', 'api/v1/env-kit', ['api'], 'api.php', [
            EnsureEnvKitWebUIAccess::class, EnvKitSecurityHeaders::class, 'throttle:' . RegisteredNames::LIMITER, RequireEnvKitWebUIToken::class,
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
        $limit = static function (Request $request): Limit {
            if (! config('laranail.env-kit-webui.throttle.enabled', true)) {
                return Limit::none();
            }

            $configured = config('laranail.env-kit-webui.throttle.per_minute', 30);
            $max = is_numeric($configured) ? (int) $configured : 30;
            $id = $request->user()?->getAuthIdentifier();
            $key = is_scalar($id) ? (string) $id : (string) $request->ip();

            // The bucket key keeps its original `env-kit:` prefix on purpose, so
            // the scoped limiter and the deprecated alias count the same requests.
            return Limit::perMinute(max(1, $max))->by('env-kit:' . $key);
        };

        RateLimiter::for(RegisteredNames::LIMITER, $limit);

        /*
         * @deprecated Since 0.1 — the bare `env-kit` limiter is kept so a host
         * route written as `throttle:env-kit` still works. It delegates to the
         * same limit and logs a warning naming the replacement. Earliest
         * removal: the next minor after 0.1.
         */
        RateLimiter::for(RegisteredNames::LEGACY_LIMITER, static function (Request $request) use ($limit): Limit {
            Log::warning(sprintf(
                '[laranail/env-kit-webui] The rate limiter "%s" is deprecated; use "throttle:%s" instead.',
                RegisteredNames::LEGACY_LIMITER,
                RegisteredNames::LIMITER,
            ));

            return $limit($request);
        });
    }

    /**
     * Keep the bare `env-kit.*` route names this package used to register
     * resolving through `route()`, as deprecated aliases of the scoped names.
     *
     * The hook is consulted only when a name is not found, so a host route
     * genuinely named `env-kit.*` still wins. Laravel holds one resolver, so
     * any resolver registered before this one is chained rather than replaced.
     * `Route::has()` does not consult the hook — ask for the scoped name.
     *
     * @deprecated Since 0.1 for the bare names it serves. Earliest removal:
     *             the next minor after 0.1.
     */
    private function resolveBareRouteNames(): void
    {
        $url = $this->app->make(UrlGenerator::class);

        /** @var callable|null $previous */
        $previous = (fn (): mixed => $this->missingNamedRouteResolver)->call($url);

        URL::resolveMissingNamedRoutesUsing(
            static function (string $name, mixed $parameters, ?bool $absolute) use ($previous): ?string {
                $scoped = RegisteredNames::scopedRouteFor($name);

                if ($scoped !== null && Route::has($scoped)) {
                    Log::warning(sprintf(
                        '[laranail/env-kit-webui] The route name "%s" is deprecated; use "%s" instead.',
                        $name,
                        $scoped,
                    ));

                    return URL::route($scoped, $parameters ?? [], $absolute ?? true);
                }

                if (is_callable($previous)) {
                    $resolved = $previous($name, $parameters, $absolute);

                    return is_string($resolved) ? $resolved : null;
                }

                return null;
            },
        );
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
