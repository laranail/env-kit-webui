# Changelog

All notable changes to `laranail/env-kit-webui` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
