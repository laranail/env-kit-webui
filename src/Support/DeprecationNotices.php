<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Support;

/**
 * Announces a deprecated name once per process with E_USER_DEPRECATED, which
 * Laravel writes to the `deprecations` log channel when one is configured. A
 * component or a link rendered on every page would otherwise write a line per
 * request.
 */
final class DeprecationNotices
{
    /** @var array<string, true> */
    private static array $announced = [];

    public static function once(string $key, string $message): void
    {
        if (isset(self::$announced[$key])) {
            return;
        }

        self::$announced[$key] = true;

        trigger_error($message, E_USER_DEPRECATED);
    }

    /**
     * Raise the notice for a Livewire component mounted under the panel's
     * deprecated bare name. Any other name is ignored.
     */
    public static function livewireMounted(string $name): void
    {
        if ($name !== RegisteredNames::LEGACY_LIVEWIRE_PANEL) {
            return;
        }

        self::once('livewire:' . $name, sprintf(
            'laranail/env-kit-webui: the Livewire component name [%s] is deprecated and will be removed no earlier than the next minor after 0.1; use [%s].',
            $name,
            RegisteredNames::LIVEWIRE_PANEL,
        ));
    }

    /**
     * Forget which names were announced. For test suites.
     */
    public static function forget(): void
    {
        self::$announced = [];
    }
}
