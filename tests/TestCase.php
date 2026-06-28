<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Tests;

use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase as Orchestra;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\EnvKitServiceProvider;
use Simtabi\Laranail\EnvKit\WebUI\EnvKitWebUIServiceProvider;

abstract class TestCase extends Orchestra
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            EnvKitServiceProvider::class,     // the engine
            EnvKitWebUIServiceProvider::class, // the web surface
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        // Drop auth in tests so we exercise the enabled-gate + engine wiring directly.
        $app['config']->set('env-kit-webui.route.middleware', ['api']);

        // The dev path-repo symlink prevents the headless provider's auto
        // config-merge under Testbench (real installs from Packagist merge fine);
        // load the engine defaults explicitly so tests mirror production.
        $engineConfig = dirname(__DIR__).'/vendor/laranail/env-kit-headless/config/env-kit.php';
        if (is_file($engineConfig)) {
            $app['config']->set('env-kit', require $engineConfig);
        }
    }

    /** Point the engine at a fresh temp .env and rebind it. */
    protected function bindEnv(string $contents): string
    {
        $dir = sys_get_temp_dir().'/envkit-webui-'.bin2hex(random_bytes(5));
        @mkdir($dir, 0777, true);
        $path = $dir.'/.env';
        file_put_contents($path, $contents);

        config([
            'env-kit.path' => $path,
            'env-kit.backup_path' => $dir.'/backups',
            'env-kit.audit.enabled' => false,
            'env-kit.auto_backup' => false,
        ]);

        $this->app->forgetInstance(EnvKitInterface::class);
        Facade::clearResolvedInstance(EnvKitInterface::class);

        return $path;
    }
}
