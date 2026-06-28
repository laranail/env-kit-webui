<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => true]);
});

it('renders translatable UI strings', function () {
    $this->get('env-kit')->assertOk()->assertSee('Key')->assertSee('Value');
});

it('honours an overridden translation line', function () {
    app('translator')->addLines(['messages.key' => 'Clé'], 'en', 'env-kit-webui');

    $this->get('env-kit')->assertOk()->assertSee('Clé');
});

it('applies the dark root class and dark-mode variants', function () {
    config(['env-kit-webui.theme' => 'tailwind', 'env-kit-webui.dark_mode' => 'dark']);

    $this->get('env-kit')
        ->assertOk()
        ->assertSee('class="dark"', false)
        ->assertSee('dark:bg-gray-900', false);
});

it('omits the dark root class in light mode', function () {
    config(['env-kit-webui.theme' => 'tailwind', 'env-kit-webui.dark_mode' => 'light']);

    $this->get('env-kit')->assertOk()->assertDontSee('class="dark"', false);
});
