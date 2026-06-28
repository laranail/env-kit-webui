# JSON API

A REST-ish CRUD surface over the engine, mounted at `config('env-kit-webui.route.prefix')`
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

## Validation & status codes

Input is validated with the **headless rules** (`ValidEnvKey`, `ValidEnvValue`)
before the engine is touched, and engine guards map to HTTP statuses:

| Status | Cause |
|--------|-------|
| `200` / `201` | Success |
| `404` | Surface disabled, or unknown key |
| `422` | Validation failure (malformed key/value) |
| `403` | Protected key, or a production-write without override |

Every write flows through the engine's atomic, backed-up, audited commit path —
the API never writes the file itself.

---

[← Docs index](../README.md#documentation)
