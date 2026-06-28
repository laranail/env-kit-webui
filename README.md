# EnvKit WebUI

> A framework-agnostic web companion for [`laranail/env-kit-headless`](https://github.com/laranail/env-kit-headless) —
> a JSON CRUD API and a themed HTML panel that **drive the engine**, never re-implement it.

[![Tests](https://github.com/laranail/env-kit-webui/actions/workflows/ci.yml/badge.svg)](https://github.com/laranail/env-kit-webui/actions)
[![Packagist](https://img.shields.io/packagist/v/laranail/env-kit-webui)](https://packagist.org/packages/laranail/env-kit-webui)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

`laranail/env-kit-webui` adds a web surface to the EnvKit engine. Every read and
write calls the headless `EnvKit` — so the web layer inherits its atomic writes,
guards, audit trail, and secret redaction for free, and holds **no** parsing or
mutation logic of its own.

It is **disabled by default**, **auth-gated**, and **production-write-blocked**.

## Install

```bash
composer require laranail/env-kit-webui
```

Requires **PHP 8.4.1+**, **Laravel 13**, and `laranail/env-kit-headless`. Publish and
enable the config:

```bash
php artisan vendor:publish --tag=env-kit-webui-config
```

```php
// config/env-kit-webui.php
'enabled' => env('ENV_KIT_WEBUI_ENABLED', false),  // turn it on deliberately
```

```dotenv
ENV_KIT_WEBUI_ENABLED=true
```

## What you get

**A JSON CRUD API** under `api/v1/env-kit` (auth-gated):

| Method | Route | Action |
|--------|-------|--------|
| `GET` | `keys` | List keys (secrets masked) |
| `GET` | `keys/{key}` | Read one key |
| `POST` | `keys` | Create a key |
| `PUT`/`PATCH` | `keys/{key}` | Update a value |
| `DELETE` | `keys/{key}` | Remove a key |

Writes reuse the **headless validation rules** (invalid input → `422` before the
engine) and surface engine guards as HTTP statuses (protected key / production →
`403`).

**A themed HTML panel** at `env-kit` (read-only, separate web route group):

```php
'theme' => 'unstyled',  // unstyled | tailwind | bootstrap | filament | nova
```

The Filament and Nova themes register only when those frameworks are installed.

<a id="documentation"></a>

## Documentation

| Page | What it covers |
|------|----------------|
| [Installation](docs/installation.md) | Requirements, enabling, auth & route config |
| [API](docs/api.md) | The JSON CRUD endpoints, validation, status codes |
| [Themes](docs/themes.md) | The theme adapters and writing your own |
| [Security](docs/security.md) | The disabled/auth/production guardrails |

Rendered docs: **<https://opensource.simtabi.com/env-kit-webui/docs/>**.

## Security

See **[SECURITY.md](SECURITY.md)**. The surface is off until enabled, requires auth,
blocks production writes, and masks secret-shaped values in responses. Report
vulnerabilities privately to `opensource@simtabi.com`.

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
