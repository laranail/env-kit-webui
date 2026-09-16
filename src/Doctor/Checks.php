<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Doctor;

use Simtabi\Laranail\Package\Tools\Services\Doctor\DoctorCheck;
use Simtabi\Laranail\Package\Tools\Services\Doctor\Checks\ConfigPresentCheck;

/**
 * Doctor checks for laranail/env-kit-webui. Registered on the package via
 * `->hasDoctorChecks(Checks::all())` and run by
 * `php artisan laranail::package-tools.doctor`.
 */
final class Checks
{
    /** @return list<DoctorCheck|class-string<DoctorCheck>> */
    public static function all(): array
    {
        return [
            new ConfigPresentCheck(
                ['env-kit-webui config' => 'laranail.env-kit-webui'],
                required: true,
                name: 'env-kit-webui:config',
                description: 'Env Kit Web UI config is published',
            ),
        ];
    }
}
