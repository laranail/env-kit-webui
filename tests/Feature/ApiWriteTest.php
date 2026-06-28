<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Facades\EnvKit;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['env-kit-webui.enabled' => true]);
});

it('creates a key via POST', function () {
    $this->bindEnv("A=1\n");

    $this->postJson('api/v1/env-kit/keys', ['key' => 'NEW', 'value' => 'val'])
        ->assertCreated()
        ->assertJsonPath('data.key', 'NEW')
        ->assertJsonPath('data.value', 'val');

    expect(EnvKit::get('NEW'))->toBe('val');
});

it('rejects an invalid key with 422', function () {
    $this->bindEnv("A=1\n");

    $this->postJson('api/v1/env-kit/keys', ['key' => '1bad', 'value' => 'x'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('key');
});

it('updates an existing key via PUT', function () {
    $this->bindEnv("A=1\n");

    $this->putJson('api/v1/env-kit/keys/A', ['value' => '2'])
        ->assertOk()
        ->assertJsonPath('data.value', '2');

    expect(EnvKit::get('A'))->toBe('2');
});

it('404s when updating a missing key', function () {
    $this->bindEnv("A=1\n");

    $this->putJson('api/v1/env-kit/keys/NOPE', ['value' => '2'])->assertNotFound();
});

it('deletes a key via DELETE', function () {
    $this->bindEnv("A=1\nB=2\n");

    $this->deleteJson('api/v1/env-kit/keys/A')
        ->assertOk()
        ->assertJsonPath('data.deleted', true);

    expect(EnvKit::has('A'))->toBeFalse();
});

it('refuses to write a protected key with 403', function () {
    $this->bindEnv("APP_KEY=base64:abcdef\n");

    $this->putJson('api/v1/env-kit/keys/APP_KEY', ['value' => 'new'])->assertForbidden();

    expect(EnvKit::get('APP_KEY'))->toBe('base64:abcdef');
});

it('blocks a write in production with 403', function () {
    $this->bindEnv("A=1\n");
    $this->app['env'] = 'production';
    $this->app->forgetInstance(EnvKitInterface::class);

    $this->postJson('api/v1/env-kit/keys', ['key' => 'B', 'value' => '2'])->assertForbidden();

    expect(EnvKit::has('B'))->toBeFalse();
});
