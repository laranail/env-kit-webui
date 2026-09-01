<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Simtabi\Laranail\EnvKit\WebUI\Support\PanelAccess;
use Simtabi\Laranail\EnvKit\WebUI\Tests\TestCase;

uses(TestCase::class);

afterEach(fn () => Carbon::setTestNow());

function reqFrom(string $ip): Request
{
    return Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ip]);
}

it('ipAllowed: open with no allowlist, matches CIDR, rejects others', function () {
    config(['env-kit-webui.access.allowed_ips' => []]);
    expect(PanelAccess::ipAllowed(reqFrom('203.0.113.9')))->toBeTrue();

    config(['env-kit-webui.access.allowed_ips' => ['10.0.0.0/8', '::1']]);
    expect(PanelAccess::ipAllowed(reqFrom('10.9.9.9')))->toBeTrue()
        ->and(PanelAccess::ipAllowed(reqFrom('203.0.113.9')))->toBeFalse();
});

it('ipAllowed: allows when there is no client IP (CLI/non-HTTP)', function () {
    config(['env-kit-webui.access.allowed_ips' => ['10.0.0.0/8']]);

    expect(PanelAccess::ipAllowed(new Request))->toBeTrue(); // no REMOTE_ADDR → ip() is null
});

it('withinSchedule: open when nothing is configured', function () {
    config(['env-kit-webui.access.schedule' => []]);
    expect(PanelAccess::withinSchedule())->toBeTrue();
});

it('withinSchedule: honours an allowed-day set (names + ISO numbers)', function () {
    Carbon::setTestNow($now = Carbon::parse('2026-06-15 12:00:00', 'UTC'));
    $today = $now->format('D');
    $other = $today === 'Mon' ? 'Tue' : 'Mon';

    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'days' => [$today]]]);
    expect(PanelAccess::withinSchedule())->toBeTrue();

    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'days' => [$other]]]);
    expect(PanelAccess::withinSchedule())->toBeFalse();

    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'days' => [$now->dayOfWeekIso]]]);
    expect(PanelAccess::withinSchedule())->toBeTrue();
});

it('withinSchedule: same-day and overnight daily windows', function () {
    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'start' => '09:00', 'end' => '17:00']]);
    Carbon::setTestNow(Carbon::parse('2026-06-15 03:00:00', 'UTC'));
    expect(PanelAccess::withinSchedule())->toBeFalse();
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));
    expect(PanelAccess::withinSchedule())->toBeTrue();

    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'start' => '22:00', 'end' => '06:00']]);
    Carbon::setTestNow(Carbon::parse('2026-06-15 23:30:00', 'UTC'));
    expect(PanelAccess::withinSchedule())->toBeTrue();   // inside overnight window
    Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', 'UTC'));
    expect(PanelAccess::withinSchedule())->toBeFalse();
});

it('withinSchedule: honours an absolute from/until range', function () {
    config(['env-kit-webui.access.schedule' => ['timezone' => 'UTC', 'from' => '2026-06-01', 'until' => '2026-06-15']]);

    Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00', 'UTC'));
    expect(PanelAccess::withinSchedule())->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-06-20 12:00:00', 'UTC'));
    expect(PanelAccess::withinSchedule())->toBeFalse();
});
