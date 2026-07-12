<?php

declare(strict_types=1);

return [

    // Disabled by default. The HTTP surface only responds once explicitly
    // enabled (and even then it is auth-gated and production-write-blocked).
    'enabled' => env('ENV_KIT_WEBUI_ENABLED', false),

    'route' => [
        'prefix' => 'api/v1/env-kit',
        // Auth-gated by default. Override to suit your stack (sanctum/session/…).
        'middleware' => ['api', 'auth:sanctum'],

        // The HTML panel (read-only) lives on a separate web route group.
        'web_prefix' => 'env-kit',
        'web_middleware' => ['web', 'auth'],
    ],

    // Optional authorization gate (a Gate ability name) checked for ALL surfaces —
    // the HTTP API/panel and the in-app Filament/Livewire surfaces — on top of
    // `enabled`. null = rely on `enabled` + the surrounding panel/route auth.
    'gate' => null,

    // Extra lockdown controls (all off until configured). Behind a proxy you MUST
    // configure Laravel's trusted proxies, or the IP allowlist matches the proxy.
    'access' => [
        // IPv4/IPv6/CIDR allowlist. [] = allow any IP.
        'allowed_ips' => [],

        // Shared secret required in the `X-EnvKit-Token` header (API only, timing-safe).
        // null/'' = no token gate.
        'token' => env('ENV_KIT_WEBUI_TOKEN'),

        // Time-window. All empty = always open. `days`: ISO 1-7 or names (Mon, Tue…).
        // `start`/`end`: 'HH:MM' (overnight-aware). `from`/`until`: absolute datetimes.
        'schedule' => [
            'timezone' => null, // null = config('app.timezone')
            'days' => [],
            'start' => null,
            'end' => null,
            'from' => null,
            'until' => null,
        ],

        // Log channel for access denials. null = the default channel.
        'log_channel' => null,
    ],

    // Request throttle for the write API (named limiter 'env-kit').
    'throttle' => [
        'enabled' => true,
        'per_minute' => 30,
    ],

    // When false (default), secret-shaped values are masked in API responses.
    'reveal_secrets' => false,

    // The default presentation adapter (unstyled | tailwind | bootstrap | filament | nova).
    'theme' => 'unstyled',

    // Colour scheme for the panel (light | dark). Themes ship dark-mode classes.
    'dark_mode' => 'light',

];
