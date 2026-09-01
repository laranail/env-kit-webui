<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament;

use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;

/**
 * Filament-flavoured theme. Registered only when Filament is installed (the
 * manager class_exists-guards it); the class itself references no Filament
 * symbols, so it is safe to autoload anywhere.
 */
final class FilamentAdapter extends AbstractThemeAdapter
{
    public function name(): string
    {
        return 'filament';
    }

    protected function classes(): array
    {
        return [
            'body' => 'fi-body p-6',
            'heading' => 'fi-header-heading text-2xl font-bold mb-4',
            'warning' => 'fi-badge fi-color-danger px-3 py-2 mb-4',
            'table' => 'fi-ta-table w-full text-sm',
            'row' => 'fi-ta-row',
            'cell' => 'fi-ta-cell px-4 py-2',
        ];
    }
}
