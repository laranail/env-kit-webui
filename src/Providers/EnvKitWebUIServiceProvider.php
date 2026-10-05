<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Providers;

use Livewire\Livewire;
use Livewire\Component;
use Illuminate\Http\Request;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\EnvKit\WebUI\Doctor\Checks;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;
use Simtabi\Laranail\EnvKit\WebUI\Support\RegisteredNames;
use Simtabi\Laranail\Package\Tools\Support\NamespaceForms;
use Simtabi\Laranail\Package\Tools\Enums\DeprecationNotice;
use Simtabi\Laranail\EnvKit\WebUI\Support\DeprecationNotices;
use Simtabi\Laranail\EnvKit\WebUI\Livewire\EnvKitPanelComponent;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\EnvKitSecurityHeaders;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\EnsureEnvKitWebUIAccess;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\RequireEnvKitWebUIToken;
use Simtabi\Laranail\Package\Tools\Support\Definitions\AboutSectionDefinition;

final class EnvKitWebUIServiceProvider extends PackageServiceProvider
{
    /**
     * Deprecated names already warned about. Held for the life of the booted
     * application -- one request under PHP-FPM, one worker under Octane or a
     * queue worker -- so the bare limiter logs one line, not one per throttled
     * request. (The bare route names are announced once per process by
     * package-tools' BareRouteNameAliases.)
     *
     * @var array<string, true>
     */
    private array $warnedDeprecations = [];

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
            ->hasDoctorChecks(Checks::all())
            // Keep the bare `env-kit.*` route names this package used to register
            // resolving through route(), as deprecated aliases of the scoped names,
            // with one logged warning per name. Consulted only for a name the router
            // does not hold, so a host route genuinely named `env-kit.*` still wins,
            // and chained to any resolver registered before it. `Route::has()` does
            // not consult it: ask for the scoped name, or
            // `$package->deprecatedRouteNames()->has()`. Earliest removal of the
            // bare names: the next minor after 0.1.
            ->hasDeprecatedRouteNames(
                prefixes: [RegisteredNames::LEGACY_ROUTE_PREFIX => RegisteredNames::ROUTE_PREFIX],
                notice: DeprecationNotice::Log,
            );
    }

    public function packageRegistered(): void
    {
        // Singleton so consumer-registered theme adapters persist for the request.
        $this->app->singleton(ThemeManager::class);
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom($this->packagePath('resources/views'), RegisteredNames::NAMESPACE_ALIAS);
        $this->loadTranslationsFrom($this->packagePath('resources/lang'), RegisteredNames::NAMESPACE_ALIAS);

        // Add the canonical `laranail/env-kit-webui` form over the same paths, in
        // both registries. The package's own views and classes use it; the hyphen
        // form stays registered for hosts that already write it.
        NamespaceForms::mirror($this->app, RegisteredNames::NAMESPACE);

        $this->registerThrottle();

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
            // Scoped name first: Livewire maps a class back to the FIRST name it
            // was registered under, so snapshots and Livewire::test() use it.
            Livewire::component(RegisteredNames::LIVEWIRE_PANEL, EnvKitPanelComponent::class);

            // @deprecated The bare `env-kit-panel`, kept so `@livewire('env-kit-panel')`
            // still renders; mounting it raises one E_USER_DEPRECATED. Earliest
            // removal: the next minor after 0.1.
            Livewire::component(RegisteredNames::LEGACY_LIVEWIRE_PANEL, EnvKitPanelComponent::class);

            Livewire::listen('mount', static function (Component $component): void {
                DeprecationNotices::livewireMounted($component->getName());
            });
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
        RateLimiter::for(RegisteredNames::LEGACY_LIMITER, function (Request $request) use ($limit): Limit {
            $this->warnDeprecatedOnce('limiter:' . RegisteredNames::LEGACY_LIMITER, sprintf(
                '[laranail/env-kit-webui] The rate limiter "%s" is deprecated; use "throttle:%s" instead.',
                RegisteredNames::LEGACY_LIMITER,
                RegisteredNames::LIMITER,
            ));

            return $limit($request);
        });
    }

    /**
     * Log a deprecation once per name for the life of the booted application.
     */
    private function warnDeprecatedOnce(string $key, string $message): void
    {
        if (isset($this->warnedDeprecations[$key])) {
            return;
        }

        $this->warnedDeprecations[$key] = true;

        Log::warning($message);
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
