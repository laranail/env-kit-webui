# laranail/env-kit-webui

[![Latest version on Packagist](https://img.shields.io/packagist/v/laranail/env-kit-webui.svg)](https://packagist.org/packages/laranail/env-kit-webui)
[![Tests](https://github.com/laranail/env-kit-webui/actions/workflows/ci.yml/badge.svg)](https://github.com/laranail/env-kit-webui/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> A framework-agnostic web companion for [`laranail/env-kit`](https://opensource.simtabi.com/documentation/laranail/env-kit/) — a JSON CRUD API and a themed HTML panel that **drive the engine**, never re-implement it. Disabled by default, auth-gated, and production-write-blocked.

Requires PHP `^8.4.1`, Laravel `^13`, and `laranail/env-kit`.

## Install

```bash
composer require laranail/env-kit-webui
php artisan vendor:publish --tag=env-kit-webui-config
```

```dotenv
ENV_KIT_WEBUI_ENABLED=true   # turn it on deliberately
```

## Documentation

Full documentation is at **[opensource.simtabi.com/documentation/laranail/env-kit-webui](https://opensource.simtabi.com/documentation/laranail/env-kit-webui/)** — what you get, enabling + auth-gating, the JSON API, the HTML panel + themes, and configuration.

## Contributing & security

Issues and PRs are welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per
[SECURITY.md](SECURITY.md) (opensource@simtabi.com); participation follows the [Code of Conduct](CODE_OF_CONDUCT.md).

## License

MIT © Simtabi LLC. See [LICENSE](LICENSE).
