# Themes

The HTML panel is framework-agnostic. A **theme adapter** maps the panel to one
presentation framework; the active one is chosen by `config('laranail.env-kit-webui.theme')`.

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

Set `config('laranail.env-kit-webui.dark_mode')` to `'dark'` (default `'light'`) to add a `dark`
root class; every built-in theme ships `dark:` variants:

```php
// config/laranail/env-kit-webui.php
'theme'     => 'tailwind',
'dark_mode' => 'dark',
```

## How it works

All themes render one Blade view (`laranail/env-kit-webui::panel`), parameterised by a CSS
class map — there is no per-theme view duplication. The view is fed an
`EnvKitViewModel` built from the engine (keys/values with secrets masked).

Views answer to both `laranail/env-kit-webui::` (canonical) and `laranail-env-kit-webui::`, over
the same paths, so an override in `resources/views/vendor/laranail-env-kit-webui/` applies to
either. Translations are registered under both forms too. The package's own views translate
through `laranail-env-kit-webui::`, so overrides keep living in
`lang/vendor/laranail-env-kit-webui/`.

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
// config/laranail/env-kit-webui.php → 'theme' => 'corporate'
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

The page is served at `{panel}/laranail-env-kit-webui` (route name
`filament.{panel}.pages.laranail-env-kit-webui`) and embeds the reactive Livewire panel,
`laranail-env-kit-webui.panel`. (Requires `livewire/livewire`, which Filament already depends
on.) To embed the panel elsewhere:

```blade
@livewire('laranail-env-kit-webui.panel')
```

### Deprecated names

These still work and are removed no earlier than the next minor after 0.1:

| Deprecated | Replacement | How it still works |
|---|---|---|
| Page slug `env-kit` | `laranail-env-kit-webui` | `{panel}/env-kit` redirects to the page, keeping the query string, under the old route name `filament.{panel}.pages.env-kit`. |
| Livewire `env-kit-panel` | `laranail-env-kit-webui.panel` | Still registered for the same component. |

Each raises one `E_USER_DEPRECATED` per process naming the replacement, which Laravel writes to
the `deprecations` log channel when one is configured.

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
