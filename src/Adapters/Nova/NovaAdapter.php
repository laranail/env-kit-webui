<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Nova;

use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;

/**
 * Nova-flavoured theme. Registered only when Laravel Nova is installed (the
 * manager class_exists-guards it); references no Nova symbols itself.
 */
final class NovaAdapter extends AbstractThemeAdapter
{
    public function name(): string
    {
        return 'nova';
    }

    protected function classes(): array
    {
        return [
            'body' => 'px-6 py-4',
            'heading' => 'text-90 font-normal text-xl mb-4',
            'warning' => 'bg-red-100 text-red-600 rounded p-2 mb-4',
            'table' => 'w-full table-default',
            'row' => '',
            'cell' => 'py-2',
        ];
    }
}
