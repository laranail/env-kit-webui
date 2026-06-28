<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Unstyled;

use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;

/** Semantic, class-free HTML — the framework-agnostic default. */
final class UnstyledAdapter extends AbstractThemeAdapter
{
    public function name(): string
    {
        return 'unstyled';
    }

    protected function classes(): array
    {
        return [];
    }
}
