<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\ViewModels;

/** The presentation-ready snapshot a theme adapter renders (no engine logic). */
final class EnvKitViewModel
{
    /** @param list<array{key: string, value: string, secret: bool}> $variables */
    public function __construct(
        public readonly array $variables,
        public readonly string $theme,
        public readonly string $apiPrefix,
        public readonly bool $production,
    ) {}
}
