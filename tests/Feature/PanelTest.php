<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

it('404s the panel when disabled', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => false]);

    $this->get('env-kit')->assertNotFound();
});

it('renders the panel HTML (default unstyled theme), masking secrets', function () {
    $this->bindEnv("APP_NAME=Acme\nDB_PASSWORD=topsecret123\n");
    config(['env-kit-webui.enabled' => true]);

    $this->get('env-kit')
        ->assertOk()
        ->assertSee('unstyled')
        ->assertSee('APP_NAME')
        ->assertDontSee('topsecret123');
});

it('renders the configured theme with its classes', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => true, 'env-kit-webui.theme' => 'tailwind']);

    $this->get('env-kit')
        ->assertOk()
        ->assertSee('tailwind')
        ->assertSee('bg-gray-50');
});

it('registers the agnostic themes and class_exists-guards Filament/Nova', function () {
    $themes = app(ThemeManager::class)->themes();

    expect($themes)->toContain('unstyled')
        ->toContain('tailwind')
        ->toContain('bootstrap')
        ->toContain('filament')   // Filament is installed in dev
        ->not->toContain('nova'); // Nova is absent → adapter not registered
});

it('falls back to the unstyled theme for an unknown name', function () {
    expect(app(ThemeManager::class)->adapter('does-not-exist')->name())->toBe('unstyled');
});

it('lets a consumer register a custom theme adapter', function () {
    $manager = app(ThemeManager::class);

    $manager->register(new class extends AbstractThemeAdapter
    {
        public function name(): string
        {
            return 'custom';
        }

        protected function classes(): array
        {
            return ['body' => 'custom-body'];
        }
    });

    expect($manager->themes())->toContain('custom')
        ->and($manager->adapter('custom')->name())->toBe('custom');
});
