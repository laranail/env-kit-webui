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

---

[← Docs index](../README.md#documentation)
