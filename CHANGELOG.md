# Changelog

All notable changes to `laranail/env-kit-webui` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `Support\RegisteredNames`, holding the vendor-scoped route-name prefix and limiter name, and
  `tests/Feature/NamingConventionTest.php`, which reads the live router, `RateLimiter`, Artisan
  kernel and middleware-alias map and fails on any bare route name, limiter, command or alias this
  package owns.

### Changed

- **Route names are vendor-scoped.** `env-kit.keys.{index,show,store,update,destroy}` and
  `env-kit.panel` are now registered as `laranail-env-kit-webui.keys.*` and
  `laranail-env-kit-webui.panel`. `env-kit.*` is also the engine's (`laranail/env-kit`) own
  namespace, so the bare names were a collision waiting to happen.
- **The API rate limiter is vendor-scoped.** The routes now use `throttle:laranail-env-kit-webui.api`
  instead of `throttle:env-kit`. The limit and its bucket key are unchanged.

### Deprecated

- The bare route names `env-kit.keys.*` and `env-kit.panel`. They still resolve through `route()`
  via `URL::resolveMissingNamedRoutesUsing()` (chained to any resolver already registered) and log
  a warning naming the replacement. `Route::has()` and `routeIs()` do not consult that hook; use
  the scoped names there. Earliest removal: the next minor after 0.1.
- The bare `env-kit` rate limiter. It is still registered, delegates to the same limit, and logs a
  warning naming `laranail-env-kit-webui.api`. Earliest removal: the next minor after 0.1.

### Fixed

- The deprecated-name warnings fired on every resolution, so a page with N links to a bare
  `env-kit.*` route name logged N lines per request, and the bare `env-kit` limiter logged once per
  throttled request for the life of an Octane or queue worker. Each deprecated name now warns once
  for the life of the booted application.
- The suite configured the engine through the bare `env-kit.*` keys (`path`, `backup_path`,
  `audit.enabled`, `auto_backup`, `hidden_keys`, `editable_keys`, `schema`,
  `limits.max_value_length`). `laranail/env-kit` now reads its configuration only at
  `laranail.env-kit.*`, so against a fresh resolve every one of those overrides was ignored and 13
  tests ran against the defaults: no temp `.env`, no schema, no allowlist. The tests, and the
  `docs/api.md` mention of the schema key, now use `laranail.env-kit.*`. No runtime code read the
  bare keys.

## [0.1.0] - 2026-07-11

### Fixed

- **The shipped configuration was inert, so the Web UI could never be switched on.**
  The provider registers the block at `laranail.env-kit-webui` (`->name('laranail/env-kit-webui')`
  plus `->hasConfigFile('env-kit-webui')`), but all eighteen read sites asked for the bare
  `env-kit-webui.*`. In an application every one of them resolved to nothing and fell back to its
  inline default.

  The panel is gated by `abort_unless(PanelAccess::enabled(), 404)`, and `enabled()` read the inert
  key with a `false` default — so every route answered 404 no matter what was configured. This
  fails closed, so it was an outage rather than an exposure, but the lockdown controls beside it
  were equally inert: `access.token`, `access.allowed_ips`, `access.schedule`, `gate` and
  `throttle`. `RequireEnvKitWebUIToken` no-ops when it reads no token, so a configured shared
  secret was not merely ignored — the gate was off.

  The `ConfigPresentCheck` in `Doctor\Checks` — the one thing positioned to catch this — was itself
  asserting the bare key, and is corrected to the registered one.

  Nothing errored, and the suite could not see it: the test harness sets the bare keys itself, which
  created the very tree the package was reading, so both sides moved together and stayed green.

  **Breaking for anyone who worked around this by setting the bare key.** Configuration now reads
  from `laranail.env-kit-webui.*`; the file publishes to `config/laranail/env-kit-webui.php`.
  `hasConfigFile('env-kit-webui')` is an id, not a key, and is unchanged.

### Added

- `tests/Feature/ConfigContractTest.php` — asserts every shipped key resolves under the registered
  name, that no source file reads a bare one again, and behaviourally that the panel can be enabled
  through configuration and that a configured shared-secret token is actually enforced.

Initial public release.

[Unreleased]: https://github.com/laranail/env-kit-webui/compare/v0.1.0...HEAD
