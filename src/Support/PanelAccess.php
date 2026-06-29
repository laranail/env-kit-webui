<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Support;

use Illuminate\Support\Facades\Gate;

/**
 * The single access decision shared by every in-app EnvKit surface (the Filament
 * page and the Livewire panel). The surface is off unless `env-kit-webui.enabled`
 * is true and — when configured — the `env-kit-webui.gate` ability passes. This is
 * what keeps the "disabled by default, authorised" guarantee from leaking past the
 * HTTP routes into Filament/Livewire.
 */
final class PanelAccess
{
    public static function allowed(): bool
    {
        if (! config('env-kit-webui.enabled', false)) {
            return false;
        }

        $gate = config('env-kit-webui.gate');

        return ! is_string($gate) || Gate::allows($gate);
    }
}
