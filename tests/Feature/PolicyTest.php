<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

beforeEach(fn () => config(['env-kit-webui.enabled' => true]));

it('honours a custom hidden_keys pattern in the API (masking)', function () {
    $this->bindEnv("APP_NAME=Acme\nWIDGET_API=topsecret123\n");
    config(['env-kit.hidden_keys' => ['WIDGET_*']]);

    $response = $this->getJson('api/v1/env-kit/keys')->assertOk();

    expect($response->getContent())->not->toContain('topsecret123');
    $response->assertJsonFragment(['key' => 'WIDGET_API', 'secret' => true])
        ->assertJsonFragment(['key' => 'APP_NAME', 'value' => 'Acme', 'secret' => false]);
});

it('returns 403 for a write outside the editable allowlist', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit.editable_keys' => ['APP_*']]);

    $this->postJson('api/v1/env-kit/keys', ['key' => 'DB_HOST', 'value' => 'x'])->assertForbidden();

    // an allowlisted key still writes
    $this->postJson('api/v1/env-kit/keys', ['key' => 'APP_NAME', 'value' => 'Acme'])->assertCreated();
});
