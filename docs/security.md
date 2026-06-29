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

## Access-control lockdown

Beyond `enabled` + auth, harden the surface with `env-kit-webui.access` (all opt-in). The
guards are **package-prepended** to the routes, so they can't be dropped by overriding
`route.middleware`.

```php
'access' => [
    'allowed_ips' => ['10.0.0.0/8', '203.0.113.4'], // IPv4/IPv6/CIDR; [] = any
    'token'       => env('ENV_KIT_WEBUI_TOKEN'),     // X-EnvKit-Token header (API), timing-safe
    'schedule'    => [                               // all empty = always open
        'timezone' => 'UTC',
        'days'     => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        'start'    => '09:00',
        'end'      => '17:00',     // overnight-aware when end < start
        'from'     => null, 'until' => null, // optional absolute datetime range
    ],
],
'throttle' => ['enabled' => true, 'per_minute' => 30],
```

A blocked request **404**s while disabled (hidden) and **403**s for an IP / token / schedule /
gate failure — each logged (`access.log_channel`) and dispatched as an `Events\AccessDenied`
event for alerting.

> **Behind a proxy** you MUST configure Laravel's [trusted proxies](https://laravel.com/docs/trusted-proxies)
> or the IP allowlist matches the proxy, not the client.

### Response headers + self-protection

Every EnvKit response carries `Cache-Control: no-store`, `X-Frame-Options: DENY`
(+ CSP `frame-ancestors`), `nosniff`, and `noindex`. Keep `ENV_KIT_WEBUI_TOKEN` and `APP_KEY`
in the engine's `protected_keys` + `hidden_keys` so the editor cannot expose or rewrite its own
gate (`APP_KEY` / `*_TOKEN` already match the defaults).

## Reporting

Found an issue? See [SECURITY.md](../SECURITY.md) — report privately to
`opensource@simtabi.com`, never via a public issue.

---

[← Docs index](../README.md#documentation)
