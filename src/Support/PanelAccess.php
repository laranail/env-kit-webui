<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * The single access decision shared by every in-app EnvKit surface (Filament page,
 * Livewire panel) and the HTTP middleware. The surface is off unless `enabled`, and
 * — when configured — the request must pass the authorization gate, the IP allowlist,
 * and the time-window. (The secret token is an API/header concern handled by its own
 * middleware, not part of `allowed()`.)
 */
final class PanelAccess
{
    public static function enabled(): bool
    {
        return (bool) config('env-kit-webui.enabled', false);
    }

    public static function gatePasses(): bool
    {
        $gate = config('env-kit-webui.gate');

        return ! is_string($gate) || Gate::allows($gate);
    }

    public static function ipAllowed(?Request $request = null): bool
    {
        $list = config('env-kit-webui.access.allowed_ips', []);
        if (! is_array($list) || $list === []) {
            return true;
        }

        $ip = ($request ?? request())->ip();
        if (! is_string($ip)) {
            return true; // non-HTTP / CLI context — nothing to match against
        }

        return IpUtils::checkIp($ip, array_values(array_filter($list, 'is_string')));
    }

    public static function withinSchedule(?CarbonImmutable $now = null): bool
    {
        $schedule = config('env-kit-webui.access.schedule', []);
        if (! is_array($schedule)) {
            return true;
        }

        $tz = is_string($schedule['timezone'] ?? null) && $schedule['timezone'] !== '' ? $schedule['timezone'] : null;
        $tz ??= is_string($appTz = config('app.timezone')) ? $appTz : 'UTC';

        try {
            $now = ($now ?? CarbonImmutable::now($tz))->setTimezone($tz);

            return self::withinRange($schedule, $now, $tz)
                && self::onAllowedDay($schedule, $now)
                && self::withinDailyWindow($schedule, $now);
        } catch (\Throwable) {
            // A malformed schedule (bad timezone / from / until) fails CLOSED — deny
            // rather than 500, so a config typo can't silently open the surface.
            return false;
        }
    }

    public static function allowed(): bool
    {
        return self::enabled() && self::gatePasses() && self::ipAllowed() && self::withinSchedule();
    }

    /** @param array<string, mixed> $schedule */
    private static function withinRange(array $schedule, CarbonImmutable $now, string $tz): bool
    {
        if (is_string($schedule['from'] ?? null) && $now->lt(CarbonImmutable::parse($schedule['from'], $tz))) {
            return false;
        }

        if (is_string($schedule['until'] ?? null) && $now->gt(CarbonImmutable::parse($schedule['until'], $tz))) {
            return false;
        }

        return true;
    }

    /** @param array<string, mixed> $schedule */
    private static function onAllowedDay(array $schedule, CarbonImmutable $now): bool
    {
        $days = $schedule['days'] ?? [];
        if (! is_array($days) || $days === []) {
            return true;
        }

        $iso = $now->dayOfWeekIso;          // 1 (Mon) .. 7 (Sun)
        $name = strtolower($now->format('D')); // mon, tue, …

        foreach ($days as $day) {
            if (is_int($day) && $day === $iso) {
                return true;
            }
            if (is_string($day) && strtolower(substr($day, 0, 3)) === $name) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $schedule */
    private static function withinDailyWindow(array $schedule, CarbonImmutable $now): bool
    {
        $start = self::minutesOfDay($schedule['start'] ?? null);
        $end = self::minutesOfDay($schedule['end'] ?? null);
        if ($start === null || $end === null) {
            return true; // no valid window configured → no constraint
        }

        $time = $now->hour * 60 + $now->minute;

        return $start <= $end
            ? ($time >= $start && $time <= $end)        // same-day window
            : ($time >= $start || $time <= $end);       // overnight window (e.g. 22:00–06:00)
    }

    /** Parse an `H:i` time (accepts `9:00` or `09:00`) to minutes-since-midnight, or null. */
    private static function minutesOfDay(mixed $time): ?int
    {
        if (! is_string($time) || preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m) !== 1) {
            return null;
        }

        $hours = (int) $m[1];
        $minutes = (int) $m[2];

        return $hours <= 23 && $minutes <= 59 ? $hours * 60 + $minutes : null;
    }
}
