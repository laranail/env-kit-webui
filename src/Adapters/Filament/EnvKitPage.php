<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament;

use Filament\Pages\Page;
use Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess;

/**
 * A Filament panel page that surfaces the EnvKit editor (the reactive Livewire
 * panel) inside a Filament admin panel. Register it via {@see EnvKitPlugin} on
 * your panel. Only autoloaded when Filament is installed (the consumer references
 * it from their panel config).
 */
final class EnvKitPage extends Page
{
    protected static ?string $navigationLabel = 'EnvKit';

    protected static ?string $title = 'EnvKit';

    protected static ?string $slug = 'env-kit';

    protected string $view = 'env-kit-webui::filament.env-kit-page';

    /** Honour the same disabled-by-default + gate policy as the HTTP surface. */
    public static function canAccess(): bool
    {
        return PanelAccess::allowed();
    }
}
