<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament;

use Filament\Panel;
use Filament\Pages\Page;
use Filament\Pages\PageConfiguration;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess;
use Simtabi\Laranail\EnvKit\WebUI\Support\RegisteredNames;

/**
 * A Filament panel page that surfaces the EnvKit editor (the reactive Livewire
 * panel) inside a Filament admin panel. Register it via {@see EnvKitPlugin} on
 * your panel. Only autoloaded when Filament is installed (the consumer references
 * it from their panel config).
 *
 * The slug is vendor-scoped (`laranail-env-kit-webui`), since Filament keys page
 * URLs and route names by it. The old `env-kit` slug still answers: see
 * {@see self::registerRoutes()}.
 */
final class EnvKitPage extends Page
{
    protected static ?string $navigationLabel = 'EnvKit';

    protected static ?string $title = 'EnvKit';

    protected static ?string $slug = RegisteredNames::FILAMENT_SLUG;

    protected string $view = 'laranail/env-kit-webui::filament.env-kit-page';

    /**
     * Register the page, plus the deprecated `env-kit` slug as a redirect to it.
     *
     * The redirect is registered in the same group as the page, so it gets the
     * panel's prefix, tenancy and auth middleware, and takes the page's old
     * route name (`filament.<panel>.pages.env-kit`), so a host's
     * `route('filament.admin.pages.env-kit')` still generates a working link.
     * It is skipped for a configured or clustered page, whose URL never was the
     * bare slug.
     *
     * @deprecated the redirect only; it goes with the old slug, no earlier than
     *             the next minor after 0.1.
     */
    public static function registerRoutes(Panel $panel, ?PageConfiguration $configuration = null): void
    {
        parent::registerRoutes($panel, $configuration);

        if ($configuration !== null || filled(self::getCluster())) {
            return;
        }

        Route::name('pages.')->group(static function (): void {
            Route::get('/' . RegisteredNames::LEGACY_FILAMENT_SLUG, RedirectLegacySlug::class)
                ->name(RegisteredNames::LEGACY_FILAMENT_SLUG);
        });
    }

    /** Honour the same disabled-by-default + gate policy as the HTTP surface. */
    public static function canAccess(): bool
    {
        return PanelAccess::allowed();
    }
}
