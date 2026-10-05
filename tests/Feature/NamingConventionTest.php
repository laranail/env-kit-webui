<?php

declare(strict_types=1);

use Illuminate\Routing\Route;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Console\Kernel;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;
use Simtabi\Laranail\EnvKit\WebUI\Support\RegisteredNames;

uses(TestCase::class);

/*
 * Every name this package registers into a framework-owned registry.
 *
 * Route names, rate limiters, Artisan commands and middleware aliases are flat
 * maps keyed by the name, so a second package claiming the same key does not
 * collide loudly — it silently replaces the first. `env-kit.keys.index` and the
 * `env-kit` limiter were exactly that kind of name, and `env-kit.*` is the
 * sibling engine's (laranail/env-kit) own namespace besides.
 *
 * These assertions read the live registries, not the provider source, so the
 * guard holds whatever the registration code looks like.
 */

const OWN_PREFIX = 'laranail-env-kit-webui.';

/**
 * The routes this package registered — identified by the controller they
 * dispatch to, not by their name, so a route registered under a bare name is
 * still found and still fails.
 *
 * @return list<Route>
 */
function envKitWebUiRoutes(): array
{
    return array_values(array_filter(
        app('router')->getRoutes()->getRoutes(),
        static fn (Route $route): bool => str_starts_with(
            (string) $route->getActionName(),
            'Simtabi\\Laranail\\EnvKit\\WebUI\\',
        ),
    ));
}

/** @return list<string> */
function registeredLimiterNames(): array
{
    $limiter = app(RateLimiter::class);

    /** @var array<string, mixed> $limiters */
    $limiters = (fn (): array => $this->limiters)->call($limiter);

    return array_keys($limiters);
}

it('registers every one of its routes under laranail-env-kit-webui.*', function (): void {
    $routes = envKitWebUiRoutes();

    // Non-vacuity: a discovery that finds nothing would pass every check below.
    expect($routes)->toHaveCount(6);

    $names = array_map(static fn (Route $route): ?string => $route->getName(), $routes);

    foreach ($names as $name) {
        expect($name)->toBeString()->toStartWith(OWN_PREFIX);
    }

    expect($names)->toEqualCanonicalizing([
        'laranail-env-kit-webui.keys.index',
        'laranail-env-kit-webui.keys.show',
        'laranail-env-kit-webui.keys.store',
        'laranail-env-kit-webui.keys.update',
        'laranail-env-kit-webui.keys.destroy',
        'laranail-env-kit-webui.panel',
    ]);
});

it('throttles its API through the vendor-scoped limiter, never the bare one', function (): void {
    expect(registeredLimiterNames())->toContain(RegisteredNames::LIMITER);

    $api = array_filter(
        envKitWebUiRoutes(),
        static fn (Route $route): bool => str_starts_with((string) $route->getName(), OWN_PREFIX . 'keys.'),
    );

    expect($api)->toHaveCount(5);

    foreach ($api as $route) {
        $throttles = array_values(array_filter(
            $route->gatherMiddleware(),
            static fn (mixed $m): bool => is_string($m) && str_starts_with($m, 'throttle:'),
        ));

        expect($throttles)->toBe(['throttle:' . RegisteredNames::LIMITER]);
    }
});

it('owns no bare limiter other than the deprecated env-kit alias', function (): void {
    $ours = array_values(array_filter(
        registeredLimiterNames(),
        static fn (string $name): bool => str_contains($name, 'env-kit'),
    ));

    // The deprecated alias is the one sanctioned bare name; anything else
    // containing env-kit must carry the vendor prefix.
    foreach (array_diff($ours, [RegisteredNames::LEGACY_LIMITER]) as $name) {
        expect($name)->toStartWith(OWN_PREFIX);
    }

    expect($ours)->toContain(RegisteredNames::LIMITER);
});

it('registers no Artisan command and no middleware alias of its own', function (): void {
    expect(app(Kernel::class)->all())->not->toBeEmpty();
    // Asserted rather than assumed: the env:* / laranail::env-kit.* commands
    // in the kernel belong to laranail/env-kit, the engine. Anything this
    // package adds must take the laranail::env-kit-webui.<command> shape.
    $commands = array_keys(app(Kernel::class)->all());

    foreach ($commands as $command) {
        if (str_contains($command, 'webui')) {
            expect($command)->toStartWith('laranail::env-kit-webui.');
        }
    }

    foreach (array_keys(app('router')->getMiddleware()) as $alias) {
        if (str_contains($alias, 'env-kit')) {
            expect($alias)->toStartWith('laranail-env-kit-webui');
        }
    }
});

it('still resolves the deprecated bare route names, and warns', function (): void {
    Log::spy();

    expect(route('env-kit.panel', [], false))->toBe('/env-kit')
        ->and(route('env-kit.keys.show', ['key' => 'APP_NAME'], false))->toBe('/api/v1/env-kit/keys/APP_NAME')
        ->and(route('env-kit.keys.index'))->toBe(route('laranail-env-kit-webui.keys.index'));

    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, 'env-kit.panel')
            && str_contains($message, 'laranail-env-kit-webui.panel'))
        ->once();
});

it('does not invent a fallback for a bare name it never owned', function (): void {
    expect(fn () => route('env-kit.nonexistent'))
        ->toThrow(Symfony\Component\Routing\Exception\RouteNotFoundException::class);
});

it('keeps the deprecated env-kit limiter working with the same limit', function (): void {
    $limiter = app(RateLimiter::class);
    $request = Illuminate\Http\Request::create('/');

    Log::spy();

    $legacy = $limiter->limiter(RegisteredNames::LEGACY_LIMITER);
    $scoped = $limiter->limiter(RegisteredNames::LIMITER);

    expect($legacy)->not->toBeNull()->and($scoped)->not->toBeNull();

    $a = $legacy($request);
    $b = $scoped($request);

    expect($a->maxAttempts)->toBe($b->maxAttempts)
        ->and($a->key)->toBe($b->key);

    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, RegisteredNames::LIMITER))
        ->atLeast()->once();
});

it('warns once per deprecated route name, however many links resolve it', function (): void {
    Log::spy();

    // A page with N links to the same bare name must not log N lines.
    foreach (range(1, 3) as $ignored) {
        route('env-kit.panel');
        route('env-kit.keys.index');
    }

    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, '"env-kit.panel"'))
        ->once();
    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, '"env-kit.keys.index"'))
        ->once();
});

it('warns once for the deprecated env-kit limiter, however many requests it throttles', function (): void {
    $legacy = app(RateLimiter::class)->limiter(RegisteredNames::LEGACY_LIMITER);
    $request = Illuminate\Http\Request::create('/');

    Log::spy();

    foreach (range(1, 3) as $ignored) {
        $legacy($request);
    }

    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, RegisteredNames::LEGACY_LIMITER))
        ->once();
});
