<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Gate: the EnvKit web surface 404s unless explicitly enabled in config. */
final class EnsureEnvKitWebUIEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_if(! config('env-kit-webui.enabled', false), 404);

        return $next($request);
    }
}
