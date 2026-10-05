# JSON API

A REST-ish CRUD surface over the engine, mounted at `config('laranail.env-kit-webui.route.prefix')`
(default `api/v1/env-kit`) behind the configured auth middleware. Every route 404s
while the surface is disabled.

## Endpoints

| Method | Route | Action |
|--------|-------|--------|
| `GET` | `keys` | List all keys/values |
| `GET` | `keys/{key}` | Read one key |
| `POST` | `keys` | Create a key |
| `PUT` / `PATCH` | `keys/{key}` | Update a value |
| `DELETE` | `keys/{key}` | Remove a key |

### Read

```http
GET /api/v1/env-kit/keys
```

```json
{
  "data": [
    { "key": "APP_NAME", "value": "Acme", "secret": false },
    { "key": "DB_PASSWORD", "value": "••••••", "secret": true }
  ]
}
```

Secret-shaped values (per the engine's `hidden_keys`) are masked unless
`reveal_secrets` is enabled in config.

### Create

```http
POST /api/v1/env-kit/keys
{ "key": "MAIL_HOST", "value": "smtp.acme.test" }
```

Returns `201` with the created variable.

### Update / delete

```http
PUT    /api/v1/env-kit/keys/MAIL_HOST   { "value": "smtp.new.test" }
DELETE /api/v1/env-kit/keys/OLD_KEY
```

## Route and limiter names

Every route is named under `laranail-env-kit-webui.*`, and the API is throttled by the named
limiter `laranail-env-kit-webui.api` (its limit comes from `laranail.env-kit-webui.throttle`).

| Route name | Method | Path (default prefix) |
|------------|--------|-----------------------|
| `laranail-env-kit-webui.keys.index` | `GET` | `api/v1/env-kit/keys` |
| `laranail-env-kit-webui.keys.show` | `GET` | `api/v1/env-kit/keys/{key}` |
| `laranail-env-kit-webui.keys.store` | `POST` | `api/v1/env-kit/keys` |
| `laranail-env-kit-webui.keys.update` | `PUT` / `PATCH` | `api/v1/env-kit/keys/{key}` |
| `laranail-env-kit-webui.keys.destroy` | `DELETE` | `api/v1/env-kit/keys/{key}` |
| `laranail-env-kit-webui.panel` | `GET` | `env-kit` |

```php
route('laranail-env-kit-webui.keys.show', ['key' => 'APP_NAME']);
```

> **Deprecated aliases.** The bare names these replaced — `env-kit.keys.*`, `env-kit.panel` and
> the `env-kit` limiter — still work: `route('env-kit.panel')` resolves to the scoped route and
> `throttle:env-kit` applies the same limit, each logging a warning that names the replacement
> (once per name for the life of the booted application, not once per link or request).
> They are removed no earlier than the next minor after 0.1. Two things do not follow the alias:
> `Route::has('env-kit.panel')` and `request()->routeIs('env-kit.*')` ask the route collection
> directly, so use the scoped names there.

## Validation & status codes

Input is validated with the **headless rules** (`ValidEnvKey`, `ValidEnvValue`,
and `MatchesEnvSchema` — so a configured `laranail.env-kit.schema` is enforced over the API
exactly as on the CLI; it is a no-op until a schema is defined) before the engine is
touched, and engine guards map to HTTP statuses:

| Status | Cause |
|--------|-------|
| `200` / `201` | Success |
| `404` | Surface disabled, or unknown key |
| `422` | Validation failure (malformed key/value) |
| `403` | Protected / non-editable key, production-write without override, a denied IP / token / schedule / surface gate, or an **update-gate denial / observer veto** ([authorization](https://opensource.simtabi.com/env-kit/docs/authorization)) |
| `429` | Throttled (see `laranail.env-kit-webui.throttle`) |

Every write flows through the engine's atomic, backed-up, audited commit path —
the API never writes the file itself.

---

[← Docs index](../README.md#documentation)
