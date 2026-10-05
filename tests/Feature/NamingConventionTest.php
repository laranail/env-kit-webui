<?php

declare(strict_types=1);

use Filament\Panel;
use Livewire\Livewire;
use Illuminate\Routing\Route;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;
use Illuminate\Support\Facades\Route as RouteFacade;
use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\EnvKit\WebUI\Support\RegisteredNames;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPage;
use Simtabi\Laranail\EnvKit\WebUI\Livewire\EnvKitPanelComponent;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;

uses(TestCase::class, AssertsRegisteredNames::class);

/*
 * Every name this package registers into a framework-owned registry.
 *
 * Route names, rate limiters, Artisan commands, middleware aliases, Livewire
 * components and view and translation namespaces are flat maps keyed by the
 * name, so a second package claiming the same key does not collide loudly -- it
 * silently replaces the first. `env-kit.keys.index`, the `env-kit` limiter and
 * the `env-kit-panel` component were exactly that kind of name, and `env-kit.*`
 * is the sibling engine's (laranail/env-kit) own namespace besides.
 *
 * package-tools' AssertsRegisteredNames reads the live registries, not the
 * provider source, so the guard holds whatever the registration code looks like.
 */

const OWN_PREFIX = 'laranail-env-kit-webui.';

function envKitWebUiScope(): NamingScope
{
    // basePath is src/: package-tools 0.1.3 defaults it to the package root, which
    // would also claim closures defined under vendor/ and tests/ as this package's.
    return NamingScope::for(
        'laranail/env-kit-webui',
        'Simtabi\\Laranail\\EnvKit\\WebUI\\',
        basePath: dirname(__DIR__, 2) . '/src',
    );
}

/**
 * The routes this package registered, identified by the controller they
 * dispatch to.
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
    // Non-vacuity: the package ships six routes.
    expect($this->assertRouteNamesScoped(envKitWebUiScope(), atLeast: 6))->toEqualCanonicalizing([
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
    expect($this->assertRateLimitersScoped(envKitWebUiScope(), deprecated: [RegisteredNames::LEGACY_LIMITER], atLeast: 1))
        ->toBe([RegisteredNames::LIMITER]);
});

it('registers no Artisan command and no middleware alias of its own', function (): void {
    // atLeast: 0 -- the package registers neither today; a bare one it owns
    // still fails, and the env:* / laranail::env-kit.* commands in the kernel
    // belong to laranail/env-kit, the engine, not to this package.
    expect($this->assertCommandNamesScoped(envKitWebUiScope(), atLeast: 0))->toBe([])
        ->and($this->assertMiddlewareAliasesScoped(envKitWebUiScope(), atLeast: 0))->toBe([]);
});

it('registers its Livewire panel under laranail-env-kit-webui.panel, keeping env-kit-panel as a deprecated alias', function (): void {
    expect($this->assertLivewireComponentsScoped(
        envKitWebUiScope(),
        deprecated: [RegisteredNames::LEGACY_LIVEWIRE_PANEL],
        atLeast: 1,
    ))->toBe([RegisteredNames::LIVEWIRE_PANEL]);

    // Livewire maps a class back to the FIRST name it was registered under.
    expect(app('livewire.finder')->normalizeName(EnvKitPanelComponent::class))->toBe(RegisteredNames::LIVEWIRE_PANEL);
});

it('registers its views and translations under both namespace forms', function (): void {
    $scope = envKitWebUiScope();

    expect($this->assertViewNamespacesScoped($scope, atLeast: 2))
        ->toContain(RegisteredNames::NAMESPACE, RegisteredNames::NAMESPACE_ALIAS)
        ->and($this->assertTranslationNamespacesScoped($scope, atLeast: 2))
        ->toContain(RegisteredNames::NAMESPACE, RegisteredNames::NAMESPACE_ALIAS);

    // The canonical view form resolves through exactly the hint paths of the hyphen
    // form, the application's published override directory included, so moving
    // the package's own calls to it cannot bypass a host's override.
    $hints = view()->getFinder()->getHints();

    expect($hints[RegisteredNames::NAMESPACE])->toBe($hints[RegisteredNames::NAMESPACE_ALIAS]);

    expect(view()->exists('laranail-env-kit-webui::panel'))->toBeTrue()
        ->and(view()->exists('laranail/env-kit-webui::panel'))->toBeTrue()
        ->and(__('laranail-env-kit-webui::messages.key'))->toBe(__('laranail/env-kit-webui::messages.key'))
        ->and(__('laranail/env-kit-webui::messages.key'))->not->toBe('laranail/env-kit-webui::messages.key');
});

it('still resolves the deprecated bare route names, and warns', function (): void {
    Log::spy();

    $this->assertDeprecatedRouteNamesResolve(
        ['env-kit.panel' => 'laranail-env-kit-webui.panel', 'env-kit.keys.show' => 'laranail-env-kit-webui.keys.show'],
        parameters: ['env-kit.keys.show' => ['key' => 'APP_NAME']],
    );

    expect(route('env-kit.panel', [], false))->toBe('/env-kit')
        ->and(route('env-kit.keys.show', ['key' => 'APP_NAME'], false))->toBe('/api/v1/env-kit/keys/APP_NAME')
        ->and(route('env-kit.keys.index'))->toBe(route('laranail-env-kit-webui.keys.index'))
        ->and(RouteFacade::has('env-kit.panel'))->toBeFalse();

    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, '[env-kit.panel]')
            && str_contains($message, '[laranail-env-kit-webui.panel]'))
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
        ->withArgs(static fn (string $message): bool => str_contains($message, '[env-kit.panel]'))
        ->once();
    Log::shouldHaveReceived('warning')
        ->withArgs(static fn (string $message): bool => str_contains($message, '[env-kit.keys.index]'))
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

it('mounts the panel under its deprecated env-kit-panel name, announcing the replacement once', function (): void {
    config(['laranail.env-kit-webui.enabled' => true]);
    $this->bindEnv("APP_NAME=Acme\n");

    $notices = [];
    set_error_handler(function (int $level, string $message) use (&$notices): bool {
        $notices[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        Livewire::test(RegisteredNames::LIVEWIRE_PANEL)->assertSee('APP_NAME');
        expect($notices)->toBe([]);

        Livewire::test(RegisteredNames::LEGACY_LIVEWIRE_PANEL)->assertSee('APP_NAME');
        Livewire::test(RegisteredNames::LEGACY_LIVEWIRE_PANEL)->assertSee('APP_NAME');
    } finally {
        restore_error_handler();
    }

    expect($notices)->toHaveCount(1)
        ->and($notices[0])->toContain('[env-kit-panel] is deprecated')
        ->and($notices[0])->toContain('[' . RegisteredNames::LIVEWIRE_PANEL . ']');
});

it('serves the Filament page at the scoped slug and redirects the deprecated env-kit slug to it', function (): void {
    // The page's route registration reads Filament's manager.
    app()->register(Filament\FilamentServiceProvider::class);

    $panel = Panel::make()->id('envkit-test')->path('admin');

    // The group Filament wraps every panel route in: name prefix and path.
    RouteFacade::name('filament.envkit-test.')->prefix('admin')->group(
        static fn () => EnvKitPage::registerRoutes($panel),
    );
    app('router')->getRoutes()->refreshNameLookups();

    expect(EnvKitPage::getSlug())->toBe(RegisteredNames::FILAMENT_SLUG)
        ->and(RouteFacade::has('filament.envkit-test.pages.laranail-env-kit-webui'))->toBeTrue()
        // The old route name still generates a link -- to the redirect.
        ->and(route('filament.envkit-test.pages.env-kit', [], false))->toBe('/admin/env-kit');

    $notices = [];
    set_error_handler(function (int $level, string $message) use (&$notices): bool {
        $notices[] = $message;

        return true;
    }, E_USER_DEPRECATED);

    try {
        $this->get('/admin/env-kit?tab=keys')->assertRedirect('http://localhost/admin/laranail-env-kit-webui?tab=keys');
        $this->get('/admin/env-kit')->assertRedirect('http://localhost/admin/laranail-env-kit-webui');
    } finally {
        restore_error_handler();
    }

    expect($notices)->toHaveCount(1)
        ->and($notices[0])->toContain('[env-kit] is deprecated');
});
