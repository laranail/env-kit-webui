<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Bootstrap;

use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;

final class BootstrapAdapter extends AbstractThemeAdapter
{
    public function name(): string
    {
        return 'bootstrap';
    }

    protected function classes(): array
    {
        return [
            'body' => 'container py-4',
            'heading' => 'h3 mb-3',
            'warning' => 'alert alert-danger',
            'table' => 'table table-striped',
            'row' => '',
            'cell' => '',
        ];
    }
}
