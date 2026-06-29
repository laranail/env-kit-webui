<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\EnvKit\WebUI\Support\AccessGuard;

/**
 * Optional shared-secret gate for the API: requires the `X-EnvKit-Token` header to
 * match `env-kit-webui.access.token` (timing-safe). No-op when no token is configured.
 */
final class RequireEnvKitWebUIToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $expected = config('env-kit-webui.access.token');

        if (! is_string($expected) || $expected === '') {
            return $next($request); // no token configured → gate is off
        }

        $provided = $request->header('X-EnvKit-Token');

        if (! is_string($provided) || ! hash_equals($expected, $provided)) {
            AccessGuard::deny($request, 'token', 403);
        }

        return $next($request);
    }
}
