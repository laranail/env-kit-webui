<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Support;

/**
 * The names this package registers into framework-owned, flat registries
 * (route names, rate limiters, the Livewire component, the Filament page slug,
 * view and translation namespaces), and the deprecated bare names that still
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

    /** The canonical view and translation namespace: the composer package name. */
    public const string NAMESPACE = 'laranail/env-kit-webui';

    /**
     * The hyphen view and translation namespace. Still registered over the same
     * files, so a host writing `laranail-env-kit-webui::` keeps working; it is an
     * alias, not deprecated.
     */
    public const string NAMESPACE_ALIAS = 'laranail-env-kit-webui';

    /** The Livewire name of the reactive panel. */
    public const string LIVEWIRE_PANEL = 'laranail-env-kit-webui.panel';

    /** The Filament page slug, and so the last segment of its URL and route name. */
    public const string FILAMENT_SLUG = 'laranail-env-kit-webui';

    /**
     * The Livewire name the panel was registered under before it was vendor-scoped.
     *
     * @deprecated Since 0.1. Use LIVEWIRE_PANEL. Still registered for the same
     *             component; mounting it raises one E_USER_DEPRECATED. Earliest
     *             removal is the next minor after 0.1.
     */
    public const string LEGACY_LIVEWIRE_PANEL = 'env-kit-panel';

    /**
     * The Filament page slug used before it was vendor-scoped.
     *
     * @deprecated Since 0.1. Use FILAMENT_SLUG. `{panel}/env-kit` still answers
     *             with a redirect to the page, under the old route name; earliest
     *             removal is the next minor after 0.1.
     */
    public const string LEGACY_FILAMENT_SLUG = 'env-kit';

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
