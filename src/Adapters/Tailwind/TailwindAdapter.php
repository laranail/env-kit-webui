<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Tailwind;

use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;

final class TailwindAdapter extends AbstractThemeAdapter
{
    public function name(): string
    {
        return 'tailwind';
    }

    protected function classes(): array
    {
        return [
            'body' => 'bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 p-6 font-sans',
            'heading' => 'text-2xl font-bold mb-4',
            'warning' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-3 py-2 rounded mb-4',
            'table' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700',
            'row' => 'odd:bg-white even:bg-gray-50 dark:odd:bg-gray-800 dark:even:bg-gray-900',
            'cell' => 'px-4 py-2 text-sm',
        ];
    }
}
