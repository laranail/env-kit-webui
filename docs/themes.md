# Themes

The HTML panel is framework-agnostic. A **theme adapter** maps the panel to one
presentation framework; the active one is chosen by `config('env-kit-webui.theme')`.

## Built-in themes

| Theme | Notes |
|-------|-------|
| `unstyled` | Semantic, class-free HTML — the default |
| `tailwind` | Tailwind utility classes |
| `bootstrap` | Bootstrap classes |
| `filament` | Filament-flavoured classes — registered only when Filament is installed |
| `nova` | Nova-flavoured classes — registered only when Laravel Nova is installed |

The Filament and Nova adapters are **`class_exists`-guarded**: they are registered
only when the framework is present, so neither needs to be installed for the package
to work. An unknown theme name falls back to `unstyled`.

## Dark mode

Set `config('env-kit-webui.dark_mode')` to `'dark'` (default `'light'`) to add a `dark`
root class; every built-in theme ships `dark:` variants:

```php
// config/env-kit-webui.php
'theme'     => 'tailwind',
'dark_mode' => 'dark',
```

## How it works

All themes render one Blade view (`env-kit-webui::panel`), parameterised by a CSS
class map — there is no per-theme view duplication. The view is fed an
`EnvKitViewModel` built from the engine (keys/values with secrets masked).

## Custom themes

Extend `AbstractThemeAdapter` (supply `name()` + a `classes()` map) and register it
with the `ThemeManager` from your service provider:

```php
use Simtabi\Laranail\EnvKit\WebUI\Adapters\AbstractThemeAdapter;
use Simtabi\Laranail\EnvKit\WebUI\Extension\ThemeManager;

final class CorporateTheme extends AbstractThemeAdapter
{
    public function name(): string { return 'corporate'; }

    protected function classes(): array
    {
        return ['body' => 'corp-body', 'table' => 'corp-table', /* … */];
    }
}

public function boot(ThemeManager $themes): void
{
    $themes->register(new CorporateTheme);
}
// config/env-kit-webui.php → 'theme' => 'corporate'
```

For full control, implement `Contracts\ThemeAdapterInterface` directly and return any
`Illuminate\Contracts\View\View` from `render()`.

## Filament panel

Surface the editor as a page inside a Filament panel — register the shipped plugin on
your panel (Filament 5):

```php
use Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament\EnvKitPlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(EnvKitPlugin::make());
}
```

The page embeds the reactive Livewire panel. (Requires `livewire/livewire`, which
Filament already depends on.)

## Laravel Nova

Nova is paid/opt-in, so the `EnvKitTool` is shipped as an adaptable starting point
(excluded from this package's CI). Register it in your `NovaServiceProvider`:

```php
public function tools(): array
{
    return [new \Simtabi\Laranail\EnvKit\WebUI\Adapters\Nova\EnvKitTool];
}
```

It links the Nova sidebar to the EnvKit web panel route; adapt the menu path/icon (or
add a Nova Vue resource to embed it) to your Nova version.

---

[← Docs index](../README.md#documentation)
