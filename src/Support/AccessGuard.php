<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Simtabi\Laranail\EnvKit\WebUI\Events\AccessDenied;

/** Logs + announces an access denial, then aborts — shared by the access middleware. */
final class AccessGuard
{
    public static function deny(Request $request, string $reason, int $status): never
    {
        $userId = self::userId($request);

        $channel = config('env-kit-webui.access.log_channel');
        Log::channel(is_string($channel) && $channel !== '' ? $channel : null)
            ->warning('EnvKit WebUI access denied', [
                'reason' => $reason,
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user' => $userId,
            ]);

        event(new AccessDenied($reason, $request->ip(), $request->path(), $userId));

        abort($status, 'EnvKit access denied.');
    }

    private static function userId(Request $request): ?string
    {
        $user = $request->user();
        if (! $user instanceof Authenticatable) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        return is_scalar($id) ? (string) $id : null;
    }
}
