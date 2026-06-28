<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

it('404s when the web UI is disabled', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => false]);

    $this->getJson('api/v1/env-kit/keys')->assertNotFound();
});

it('lists keys and masks secrets when enabled', function () {
    $this->bindEnv("APP_NAME=Acme\nDB_PASSWORD=topsecret123\n");
    config(['env-kit-webui.enabled' => true]);

    $response = $this->getJson('api/v1/env-kit/keys')->assertOk();

    $response->assertJsonFragment(['key' => 'APP_NAME', 'value' => 'Acme', 'secret' => false]);
    expect($response->getContent())->not->toContain('topsecret123');
});

it('shows a single key', function () {
    $this->bindEnv("APP_NAME=Acme\n");
    config(['env-kit-webui.enabled' => true]);

    $this->getJson('api/v1/env-kit/keys/APP_NAME')
        ->assertOk()
        ->assertJsonPath('data.key', 'APP_NAME')
        ->assertJsonPath('data.value', 'Acme');
});

it('404s an unknown key', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.enabled' => true]);

    $this->getJson('api/v1/env-kit/keys/NOPE')->assertNotFound();
});

it('reveals secrets when configured', function () {
    $this->bindEnv("DB_PASSWORD=topsecret123\n");
    config(['env-kit-webui.enabled' => true, 'env-kit-webui.reveal_secrets' => true]);

    $this->getJson('api/v1/env-kit/keys/DB_PASSWORD')
        ->assertOk()
        ->assertJsonPath('data.value', 'topsecret123');
});
