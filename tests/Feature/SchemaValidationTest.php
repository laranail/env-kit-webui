<?php

declare(strict_types=1);

use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['env-kit-webui.enabled' => true]);
});

it('rejects a stored value that violates the engine schema', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit.schema' => ['PORT' => 'integer']]);

    $this->postJson('api/v1/env-kit/keys', ['key' => 'PORT', 'value' => 'not-an-int'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('value');

    $this->postJson('api/v1/env-kit/keys', ['key' => 'PORT', 'value' => '8080'])
        ->assertCreated();
});

it('validates an updated value against the schema', function () {
    $this->bindEnv("PORT=8080\n");
    config(['env-kit.schema' => ['PORT' => 'integer']]);

    $this->putJson('api/v1/env-kit/keys/PORT', ['value' => 'nope'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('value');

    $this->putJson('api/v1/env-kit/keys/PORT', ['value' => '9090'])
        ->assertOk();
});

it('is a no-op when no schema is configured', function () {
    $this->bindEnv("A=1\n");

    $this->postJson('api/v1/env-kit/keys', ['key' => 'ANYTHING', 'value' => 'free-form'])
        ->assertCreated();
});
