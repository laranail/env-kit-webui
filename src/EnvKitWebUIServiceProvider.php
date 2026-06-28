<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\EnsureEnvKitWebUIEnabled;
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

    public function packageBooted(): void
    {
        $config = $this->app->make(Repository::class);

        $prefix = $config->get('env-kit-webui.route.prefix', 'api/v1/env-kit');
        $middleware = $config->get('env-kit-webui.route.middleware', ['api']);

        Route::group([
            'prefix' => is_string($prefix) ? $prefix : 'api/v1/env-kit',
            // The enabled-gate runs first on every request, so toggling config at
            // runtime takes effect without re-registering routes.
            'middleware' => array_merge(
                [EnsureEnvKitWebUIEnabled::class],
                is_array($middleware) ? array_values($middleware) : ['api'],
            ),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }
}
