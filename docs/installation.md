# Installation

## Requirements

- PHP 8.4.1+ and Laravel 13
- [`laranail/env-kit-headless`](https://github.com/laranail/env-kit-headless) (the engine)

## Install

```bash
composer require laranail/env-kit-webui
```

The service provider auto-registers. Publish the config:

```bash
php artisan vendor:publish --tag=env-kit-webui-config
```

## Enable it

The surface is **disabled by default**. Turn it on deliberately:

```dotenv
ENV_KIT_WEBUI_ENABLED=true
```

While disabled, every route returns `404`.

## Configure routes & auth

```php
// config/env-kit-webui.php
'route' => [
    'prefix'         => 'api/v1/env-kit',
    'middleware'     => ['api', 'auth:sanctum'], // auth-gated by default
    'web_prefix'     => 'env-kit',
    'web_middleware' => ['web', 'auth'],
],
'reveal_secrets' => false, // mask secret-shaped values in responses
'theme'          => 'unstyled',
```

Adjust the middleware to match your auth stack (Sanctum, session, a custom guard).
The engine's own production-write guard still applies on top of this.

## Local development

This package depends on the unpublished `laranail/env-kit-headless`. For local
development, the repo's `composer.json` declares a `path` repository to `../headless`
so Composer resolves the engine from your working copy.

---

[← Docs index](../README.md#documentation)
