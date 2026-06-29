# Security

The web surface is the most sensitive part of EnvKit — it exposes `.env` over HTTP.
It ships locked down by three independent guardrails.

## 1. Disabled by default

Nothing responds until you explicitly opt in:

```dotenv
ENV_KIT_WEBUI_ENABLED=true
```

While disabled, every API and panel route returns `404` — checked per request, so
the surface can be toggled at runtime.

The **in-app surfaces honour the same switch**: the Filament page's `canAccess()` and
the Livewire `env-kit-panel` component both refuse (Filament hides it / the component
403s) unless `enabled` is true — so embedding them anywhere can't bypass the gate.

## 2. Auth-gated

For the in-app surfaces, set an authorization gate so only the right users reach the
editor (on top of the panel/route auth):

```php
// config/env-kit-webui.php
'gate' => 'manage-env',   // a Gate ability checked by the Filament page + Livewire panel
```

```php
Gate::define('manage-env', fn ($user) => $user->isAdmin());
```


Routes run behind the middleware in `config('env-kit-webui.route.middleware')` /
`web_middleware` — `auth:sanctum` for the API and `web`+`auth` for the panel by
default. Adjust to your stack; do **not** ship it unauthenticated. The `auth:sanctum`
default requires `laravel/sanctum` installed and configured — if you use a different
guard, change `route.middleware` accordingly (an unknown guard otherwise errors).

## 3. Production-write-blocked

The headless engine's production guard applies to every write made through the API.
In production, writes are refused with `403` unless deliberately overridden — there
is no UI affordance to bypass it casually.

## Secret handling

- Secret-shaped values (the engine's `hidden_keys`) are **masked** in API responses
  and the panel unless `reveal_secrets` is enabled.
- The engine redacts secrets from logs, exceptions, audit records, and events, so a
  raw secret never escapes through the web layer either.

## Reporting

Found an issue? See [SECURITY.md](../SECURITY.md) — report privately to
`opensource@simtabi.com`, never via a public issue.

---

[← Docs index](../README.md#documentation)
