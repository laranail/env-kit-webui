<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Http\Controllers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\View\View;
use Simtabi\Laranail\EnvKit\Headless\Contracts\EnvKitInterface;
use Simtabi\Laranail\EnvKit\Headless\Security\SecretRedactor;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;
use Simtabi\Laranail\EnvKit\WebUI\Http\ViewModels\EnvKitViewModel;

/** Renders the read-only HTML panel through the active theme adapter. */
final class PanelController
{
    public function __construct(
        private readonly EnvKitInterface $env,
        private readonly SecretRedactor $redactor,
        private readonly ThemeManager $themes,
        private readonly Repository $config,
    ) {}

    public function show(): View
    {
        $reveal = (bool) $this->config->get('env-kit-webui.reveal_secrets', false);
        $adapter = $this->themes->adapter();
        $prefix = $this->config->get('env-kit-webui.route.prefix', 'api/v1/env-kit');

        $variables = [];
        foreach ($this->env->all() as $key => $value) {
            $variables[] = [
                'key' => $key,
                'value' => $reveal ? $value : $this->redactor->forKey($key, $value),
                'secret' => $this->redactor->isSecretKey($key),
            ];
        }

        return $adapter->render(new EnvKitViewModel(
            variables: $variables,
            theme: $adapter->name(),
            apiPrefix: is_string($prefix) ? $prefix : 'api/v1/env-kit',
            production: app()->environment('production'),
        ));
    }
}
