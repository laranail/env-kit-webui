<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\EnvKit\WebUI\Events\AccessDenied;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

beforeEach(fn () => config(['env-kit-webui.enabled' => true]));
afterEach(fn () => Carbon::setTestNow());

it('passes through with no lockdown configured', function () {
    $this->bindEnv("A=1\n");

    $this->getJson('api/v1/env-kit/keys')->assertOk();
});

it('blocks a request from a non-allowlisted IP and allows an allowlisted CIDR', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.access.allowed_ips' => ['10.0.0.0/8']]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
        ->getJson('api/v1/env-kit/keys')->assertForbidden();

    $this->withServerVariables(['REMOTE_ADDR' => '10.1.2.3'])
        ->getJson('api/v1/env-kit/keys')->assertOk();
});

it('requires a valid secret token when configured', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.access.token' => 'sekret']);

    $this->getJson('api/v1/env-kit/keys')->assertForbidden();
    $this->getJson('api/v1/env-kit/keys', ['X-EnvKit-Token' => 'wrong'])->assertForbidden();
    $this->getJson('api/v1/env-kit/keys', ['X-EnvKit-Token' => 'sekret'])->assertOk();
});

it('blocks outside the configured daily time-window', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'start' => '09:00', 'end' => '17:00']]);

    Carbon::setTestNow(Carbon::parse('2026-06-30 03:00:00', 'UTC'));
    $this->getJson('api/v1/env-kit/keys')->assertForbidden();

    Carbon::setTestNow(Carbon::parse('2026-06-30 12:00:00', 'UTC'));
    $this->getJson('api/v1/env-kit/keys')->assertOk();
});

it('sets response-hardening headers', function () {
    $this->bindEnv("A=1\n");

    $this->getJson('api/v1/env-kit/keys')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('logs + emits AccessDenied and returns 403 when the gate denies', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.gate' => 'manage-env']);
    Gate::define('manage-env', fn () => false);
    Event::fake([AccessDenied::class]);

    $this->getJson('api/v1/env-kit/keys')->assertForbidden();

    Event::assertDispatched(AccessDenied::class, fn (AccessDenied $e) => $e->reason === 'gate');
});

it('throttles the API once over the limit', function () {
    $this->bindEnv("A=1\n");
    config(['env-kit-webui.throttle.per_minute' => 1]);

    $this->getJson('api/v1/env-kit/keys')->assertOk();
    $this->getJson('api/v1/env-kit/keys')->assertStatus(429);
});

it('returns 403 when the engine update gate denies a write', function () {
    $this->bindEnv("A=1\n");
    Gate::define('env-kit.update', fn (?Authenticatable $user) => false);

    $this->postJson('api/v1/env-kit/keys', ['key' => 'NEW', 'value' => 'x'])->assertForbidden();
});
