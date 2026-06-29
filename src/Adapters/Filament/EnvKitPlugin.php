<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

/**
 * Registers the EnvKit page on a Filament panel:
 *
 *   $panel->plugin(\Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPlugin::make());
 *
 * Only autoloaded when Filament is installed.
 */
final class EnvKitPlugin implements Plugin
{
    public function getId(): string
    {
        return 'env-kit-webui';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([EnvKitPage::class]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return new self;
    }
}
