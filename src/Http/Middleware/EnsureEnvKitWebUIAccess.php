<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Simtabi\Laranail\EnvKit\WebUI\Support\AccessGuard;
use Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess;

/**
 * The first guard on every EnvKit web route: 404 unless the surface is enabled, then
 * 403 if the request fails the IP allowlist, the time-window, or the authorization
 * gate. All checks are no-ops until configured.
 */
class EnsureEnvKitWebUIAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        // 404 (not 403) while disabled, so the surface's existence stays hidden.
        abort_unless(PanelAccess::enabled(), 404);

        $reason = match (true) {
            ! PanelAccess::ipAllowed($request) => 'ip',
            ! PanelAccess::withinSchedule() => 'schedule',
            ! PanelAccess::gatePasses() => 'gate',
            default => null,
        };

        if ($reason !== null) {
            AccessGuard::deny($request, $reason, 403);
        }

        return $next($request);
    }
}
