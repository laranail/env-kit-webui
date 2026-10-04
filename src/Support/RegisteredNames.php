<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Support;

/**
 * The names this package registers into framework-owned, flat registries
 * (route names and rate limiters), and the deprecated bare names that still
 * resolve to them.
 *
 * Every owned name carries the vendor and the package slug, so a sibling
 * package — `laranail/env-kit` itself uses `env-kit.*` — or the host
 * application cannot silently replace it.
 */
final class RegisteredNames
{
    /** Prefix of every route name this package registers. */
    public const string ROUTE_PREFIX = 'laranail-env-kit-webui.';

    /** The named rate limiter that throttles the JSON API. */
    public const string LIMITER = 'laranail-env-kit-webui.api';

    /**
     * Prefix the routes were registered under before they were vendor-scoped.
     *
     * @deprecated Since 0.1. Use ROUTE_PREFIX. The bare names still resolve
     *             through route() with a logged warning; earliest removal is
     *             the next minor after 0.1.
     */
    public const string LEGACY_ROUTE_PREFIX = 'env-kit.';

    /**
     * The limiter name used before it was vendor-scoped.
     *
     * @deprecated Since 0.1. Use LIMITER. Still registered and delegating to
     *             the same limit with a logged warning; earliest removal is
     *             the next minor after 0.1.
     */
    public const string LEGACY_LIMITER = 'env-kit';

    /**
     * The vendor-scoped route name a deprecated bare one was written as, or
     * null when the name was never one of this package's.
     */
    public static function scopedRouteFor(string $name): ?string
    {
        if (! str_starts_with($name, self::LEGACY_ROUTE_PREFIX)) {
            return null;
        }

        return self::ROUTE_PREFIX . substr($name, strlen(self::LEGACY_ROUTE_PREFIX));
    }
}
