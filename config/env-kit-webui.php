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
    ],

    // When false (default), secret-shaped values are masked in API responses.
    'reveal_secrets' => false,

    // The default presentation adapter (unstyled | tailwind | bootstrap | filament | nova).
    'theme' => 'unstyled',

];
