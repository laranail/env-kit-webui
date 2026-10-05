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
- `Support\DeprecationNotices`, which announces a deprecated Livewire name or Filament slug once
  per process with `E_USER_DEPRECATED`, and `Adapters\Filament\RedirectLegacySlug`, which answers
  the page's old URL.
- `RegisteredNames` constants for the view and translation namespaces, the Livewire panel name and
  the Filament slug, with their deprecated predecessors.

### Changed

- `laravel/framework ^13.0` is now declared in `require`. `src/` uses `FormRequest` from `Illuminate\Foundation`, which no `illuminate/*` component ships, so the dependency only arrived through the host application.
- **Route names are vendor-scoped.** `env-kit.keys.{index,show,store,update,destroy}` and
  `env-kit.panel` are now registered as `laranail-env-kit-webui.keys.*` and
  `laranail-env-kit-webui.panel`. `env-kit.*` is also the engine's (`laranail/env-kit`) own
  namespace, so the bare names were a collision waiting to happen.
- **The API rate limiter is vendor-scoped.** The routes now use `throttle:laranail-env-kit-webui.api`
  instead of `throttle:env-kit`. The limit and its bucket key are unchanged.
- The deprecated bare route names are served by `laranail/package-tools`' shared
  `BareRouteNameAliases` (declared with `$package->hasDeprecatedRouteNames()`), replacing the
  provider's own resolver. Behaviour is unchanged: one logged warning per name, chained to any
  resolver registered earlier, never shadowing a host route. The warning now reads "the route name
  [...] is deprecated and will stop resolving no earlier than the next minor after 0.1; use
  [...]". Requires `laranail/package-tools ^0.1.3`.
- **The Livewire panel is vendor-scoped:** `laranail-env-kit-webui.panel`, which the Filament page
  now embeds.
- **The Filament page slug is vendor-scoped:** `laranail-env-kit-webui`, so the page is served at
  `{panel}/laranail-env-kit-webui` under the route name
  `filament.{panel}.pages.laranail-env-kit-webui`.
- Views and translations are registered under `laranail/env-kit-webui::` as well as
  `laranail-env-kit-webui::`, over the same paths. The package's own view calls use the slash
  form; its translation calls stay on the hyphen form, which is where a host's overrides in
  `lang/vendor/laranail-env-kit-webui/` are read from.
- `NamingConventionTest` runs on package-tools' `AssertsRegisteredNames`, and also covers the
  Livewire component, the view and translation namespaces and the Filament slug.

### Deprecated

- The bare route names `env-kit.keys.*` and `env-kit.panel`. They still resolve through `route()`
  via `URL::resolveMissingNamedRoutesUsing()` (chained to any resolver already registered) and log
  a warning naming the replacement. `Route::has()` and `routeIs()` do not consult that hook; use
  the scoped names there. Earliest removal: the next minor after 0.1.
- The bare `env-kit` rate limiter. It is still registered, delegates to the same limit, and logs a
  warning naming `laranail-env-kit-webui.api`. Earliest removal: the next minor after 0.1.
- The Livewire name `env-kit-panel`. It is still registered for the same component; mounting it
  raises one `E_USER_DEPRECATED` naming `laranail-env-kit-webui.panel`. Earliest removal: the
  next minor after 0.1.
- The Filament page slug `env-kit`. `{panel}/env-kit` redirects to the page, keeping the query
  string, under the page's old route name `filament.{panel}.pages.env-kit`, and raises one
  `E_USER_DEPRECATED`. Earliest removal: the next minor after 0.1.

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
