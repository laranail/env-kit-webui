<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\EnvKit\WebUI\Contracts\ThemeAdapterInterface;
use Simtabi\Laranail\EnvKit\WebUI\Http\ViewModels\EnvKitViewModel;

/**
 * Shared adapter: renders the one package panel view, parameterised only by a
 * theme's CSS class map. Each concrete theme supplies `name()` + `classes()` —
 * no per-theme Blade duplication.
 */
abstract class AbstractThemeAdapter implements ThemeAdapterInterface
{
    abstract public function name(): string;

    /** @return array<string, string> */
    abstract protected function classes(): array;

    public function render(EnvKitViewModel $viewModel): View
    {
        return app(ViewFactory::class)->make('env-kit-webui::panel', [
            'vm' => $viewModel,
            'theme' => $this->classes(),
        ]);
    }
}
