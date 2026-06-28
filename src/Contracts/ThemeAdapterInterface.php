<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Contracts;

use Illuminate\Contracts\View\View;
use Simtabi\Laranail\EnvKit\WebUI\Http\ViewModels\EnvKitViewModel;

/** Renders the EnvKit panel for one presentation framework / theme. */
interface ThemeAdapterInterface
{
    /** The theme's short name (the config('env-kit-webui.theme') value). */
    public function name(): string;

    public function render(EnvKitViewModel $viewModel): View;
}
