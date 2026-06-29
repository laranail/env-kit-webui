<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Events;

/** Dispatched when the WebUI access guard blocks a request (IP / token / schedule / gate). */
final class AccessDenied
{
    public function __construct(
        public readonly string $reason,
        public readonly ?string $ip,
        public readonly string $path,
        public readonly ?string $userId,
    ) {}
}
