<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Middleware;

/**
 * @deprecated since 0.2.0 — renamed to {@see EnsureEnvKitWebUIAccess}, which also
 * enforces the IP allowlist, time-window and gate. Kept one release for BC.
 */
final class EnsureEnvKitWebUIEnabled extends EnsureEnvKitWebUIAccess {}
