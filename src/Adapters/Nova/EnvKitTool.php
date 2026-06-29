<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Tool;

/**
 * A Laravel Nova tool that links the Nova sidebar to the EnvKit web panel.
 * Register it in your NovaServiceProvider:
 *
 *   public function tools(): array
 *   {
 *       return [new \Simtabi\Laranail\EnvKit\WebUI\Adapters\Nova\EnvKitTool];
 *   }
 *
 * Nova is a paid, opt-in dependency, so this class is only autoloaded when Nova is
 * installed and is excluded from this package's static analysis / test gates. Treat
 * it as a starting point — adapt the menu path/icon (and, if you want the panel
 * embedded rather than linked, a Nova Vue resource) to your Nova version.
 */
final class EnvKitTool extends Tool
{
    public function boot(): void
    {
        //
    }

    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('EnvKit')
            ->path('/env-kit')
            ->icon('cog');
    }
}
