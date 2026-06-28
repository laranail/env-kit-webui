# Contributing

Thanks for your interest in improving `laranail/env-kit-webui` — the
framework-agnostic web UI/UX companion for `laranail/env-kit-headless`.

## Requirements

- PHP **8.4.1+**
- Laravel **13**
- This package depends on `laranail/env-kit-headless`. For local development,
  `composer install` resolves it via a `path` repository pointed at
  `../headless`, so clone both side by side under the same parent directory.

## Getting started

```bash
composer install
```

## Before opening a PR

Run the full local suite and make sure it is green:

```bash
vendor/bin/pest              # tests
vendor/bin/phpstan analyse   # static analysis, level 9
vendor/bin/pint              # code style
```

## Conventions

- **Tests**: Pest 4 with Orchestra Testbench feature tests.
- **Strict types**: every PHP file declares `declare(strict_types=1);`.
- **Drive, don't reimplement**: the web layer (controllers, API, panel) must
  DRIVE the headless `EnvKit` engine. Never re-implement engine logic in this
  package.
- **Validation reuse**: FormRequests reuse the headless validation rules rather
  than duplicating them.
- **Security posture**: keep the HTTP surface disabled by default, auth-gated,
  and production-write-blocked; keep secret masking intact in responses.

## Commit messages

- Lowercase, imperative subject line, ≤ 72 characters
  (e.g. `add bootstrap theme adapter`).
- Explain the *why* in the body when it isn't obvious.
- No AI attribution: no `Co-Authored-By` trailers, no "generated with" lines,
  and no mention of AI assistants.

## Reporting security issues

Do not file public issues for vulnerabilities. See [SECURITY.md](SECURITY.md)
and email `opensource@simtabi.com`.
