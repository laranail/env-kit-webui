<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Extension;

use Illuminate\Contracts\Config\Repository;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Bootstrap\BootstrapAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\FilamentAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Nova\NovaAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Tailwind\TailwindAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Unstyled\UnstyledAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Contracts\ThemeAdapterInterface;

/**
 * Theme adapter registry. The framework-agnostic themes always register; the
 * Filament and Nova themes register only when those frameworks are installed
 * (class_exists-guarded). Consumers add their own with register().
 */
final class ThemeManager
{
    /** @var array<string, ThemeAdapterInterface> */
    private array $adapters = [];

    public function __construct(
        private readonly Repository $config,
    ) {
        $this->register(new UnstyledAdapter)
            ->register(new TailwindAdapter)
            ->register(new BootstrapAdapter);

        // Optional-dependency guards: string literals so neither framework must
        // be installed (or known to static analysis) for this to compile.
        if (class_exists('Filament\\Panel')) {
            $this->register(new FilamentAdapter);
        }
        if (class_exists('Laravel\\Nova\\Nova')) {
            $this->register(new NovaAdapter);
        }
    }

    public function register(ThemeAdapterInterface $adapter): self
    {
        $this->adapters[$adapter->name()] = $adapter;

        return $this;
    }

    /** Resolve a theme (the configured default when $name is null), falling back to unstyled. */
    public function adapter(?string $name = null): ThemeAdapterInterface
    {
        if ($name === null) {
            $configured = $this->config->get('env-kit-webui.theme', 'unstyled');
            $name = is_string($configured) ? $configured : 'unstyled';
        }

        return $this->adapters[$name] ?? $this->adapters['unstyled'];
    }

    /** @return list<string> */
    public function themes(): array
    {
        return array_keys($this->adapters);
    }
}
