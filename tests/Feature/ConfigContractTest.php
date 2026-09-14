<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

/**
 * Asserts that this package reads its configuration at the key it actually registers.
 *
 * It did not. `->name('laranail/env-kit-webui')` + `->hasConfigFile('env-kit-webui')` register the
 * block at `laranail.env-kit-webui`, but all eighteen read sites asked for the bare
 * `env-kit-webui.*`, so in a real application every one of them resolved to nothing and fell back
 * to its inline default.
 *
 * The net effect is fail-closed rather than an exposure, which is worth stating plainly: the panel
 * is gated by `abort_unless(PanelAccess::enabled(), 404)`, and `enabled()` reads the inert key with
 * a `false` default — so **the Web UI could never be switched on**, whatever the consumer set. The
 * lockdown controls beside it (`access.token`, `access.allowed_ips`, `access.schedule`, `gate`,
 * `throttle`) were inert for the same reason, and `RequireEnvKitWebUIToken` no-ops when it reads no
 * token — so a configured shared secret was never checked.
 *
 * The suite could not see any of it: the test harness sets the bare keys itself (`TestCase`), which
 * creates the very tree the package was reading. Both sides moved together and stayed green.
 * `hasConfigFile('env-kit-webui')` is an id, not a key, and is correct as written.
 */
function shippedWebUiConfig(): array
{
    return require __DIR__ . '/../../config/env-kit-webui.php';
}

it('registers its config where the package reads it', function (): void {
    expect(config('laranail.env-kit-webui'))->toBeArray()->not->toBeEmpty();
});

it('exposes every shipped config value at the scoped key', function (): void {
    // Discovery-driven: a key added to the shipped file is covered here the day it lands.
    $missing = [];

    foreach (array_keys(shippedWebUiConfig()) as $key) {
        if (! config()->has("laranail.env-kit-webui.{$key}")) {
            $missing[] = $key;
        }
    }

    expect($missing)->toBe([], sprintf(
        "the shipped config offers these keys, but nothing resolves them at laranail.env-kit-webui.*:\n  %s",
        implode("\n  ", $missing),
    ));
});

it('reads no configuration at a bare env-kit-webui key', function (): void {
    // Asserted against the source, because a bare read is not an error -- it silently returns the
    // inline default. Nothing else in this package registers under this prefix (no container tags,
    // no gate abilities), so every occurrence in a config call is a defect.
    $offenders = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src')) as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        foreach (file($file->getPathname()) ?: [] as $number => $line) {
            // Both spellings the provider uses: a literal key, and an interpolated
            // "env-kit-webui.{$suffix}". Anchoring the closing quote straight after the first
            // segment would miss every multi-segment key, which is most of them.
            if (preg_match('/(?:config\(|->get\()\s*[\'"]env-kit-webui[.\'"]/', $line) !== 1) {
                continue;
            }

            $offenders[] = basename($file->getPathname()) . ':' . ($number + 1) . ' — ' . trim($line);
        }
    }

    expect($offenders)->toBe([], sprintf(
        "these read config at the bare key, which resolves to nothing in a real application:\n  %s",
        implode("\n  ", $offenders),
    ));
});

it('can actually be enabled through configuration', function (): void {
    // The end-to-end proof that the config is live rather than merely present. This is the exact
    // behaviour that was off in every deployment: the panel answered 404 no matter what was set.
    config()->set('laranail.env-kit-webui.enabled', true);
    expect(Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess::enabled())->toBeTrue();

    config()->set('laranail.env-kit-webui.enabled', false);
    expect(Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess::enabled())->toBeFalse();
});

it('honours a configured shared-secret token', function (): void {
    // RequireEnvKitWebUIToken no-ops when it reads no token, so an inert config key did not merely
    // ignore the setting -- it turned the gate off while reporting nothing.
    config()->set('laranail.env-kit-webui.access.token', 'a-configured-secret');

    $middleware = new Simtabi\Laranail\EnvKit\WebUI\Http\Middleware\RequireEnvKitWebUIToken;
    $passed = false;

    try {
        $middleware->handle(request(), function ($r) use (&$passed) {
            $passed = true;

            return $r;
        });
    } catch (Throwable) {
        // denied, which is the expected path for a request with no token header
    }

    expect($passed)->toBeFalse('a configured token was not enforced; the gate read nothing and passed the request through');
});
