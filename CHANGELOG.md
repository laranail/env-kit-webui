# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.2.1] - 2026-06-29

### Fixed

- The access time-window now **fails closed** (403) on a malformed `schedule` config instead
  of throwing a 500, and accepts non-zero-padded `HH:MM` times (e.g. `9:00`).

## [0.2.0] - 2026-06-29

### Added

- **Access-control lockdown** (`env-kit-webui.access` / `throttle`, all opt-in): an IP
  allowlist (IPv4/IPv6/CIDR), a timing-safe `X-EnvKit-Token` secret for the API, a
  time-window (timezone + days + daily window + absolute range), and a request throttle.
- **Response-hardening headers** on every EnvKit response (`Cache-Control: no-store`,
  `X-Frame-Options: DENY` + CSP `frame-ancestors`, `nosniff`, `noindex`).
- **Access-denial observability**: a structured security log line + an `AccessDenied`
  event on every blocked request.

### Fixed (security)

- The authorization `gate` is now enforced on the **HTTP API + HTML panel** (not just the
  Filament/Livewire surfaces); the lockdown middleware is package-prepended so it can't be
  dropped by overriding `route.middleware`.
- Engine update-gate / observer-veto refusals map to **403**.

### Changed

- `EnsureEnvKitWebUIEnabled` middleware renamed to `EnsureEnvKitWebUIAccess` (the old class
  is kept as a deprecated subclass for one release).

## [0.1.2] - 2026-06-29

### Fixed

- **Security:** the Filament page and the Livewire `env-kit-panel` component now honour the
  `enabled` flag (and an optional `gate` ability) — previously they bypassed the
  disabled-by-default switch and the Filament page had no access control, so any panel user
  could read/edit `.env`. New `Support\PanelAccess` gate, `EnvKitPage::canAccess()`, and a
  `mount()` guard on the component.
- The Livewire panel now surfaces engine guard/validation failures inline (an inline error)
  instead of throwing an ungraceful 500.
- Corrected the CHANGELOG comparison links.

## [0.1.1] - 2026-06-29

### Added

- Real Filament integration — an `EnvKitPage` + `EnvKitPlugin` that surface the editor
  inside a Filament 5 panel.
- A Laravel Nova `EnvKitTool` (opt-in, adaptable starting point; excluded from CI).

### Fixed

- Secret masking now honours the engine's `hidden_keys` config across the API, the HTML
  panel, and the Livewire component (previously the built-in patterns only).
- A write to a key outside `editable_keys` now returns **403** (was an unmapped 422).

## [0.1.0] - 2026-06-29

### Added

- Initial pre-release of `laranail/env-kit-webui`, a framework-agnostic web
  UI/UX companion for `laranail/env-kit-headless`. The package holds no engine
  logic — its web layer DRIVES the headless `EnvKit` engine.
- HTTP surface that is **disabled by default**, **auth-gated**, and
  **production-write-blocked**, so the panel and API never expose `.env`
  editing unless a consumer explicitly opts in.
- JSON CRUD API for environment keys (`GET`/`POST`/`PUT`/`DELETE`) that drives
  the headless `EnvKit` engine for all reads and writes.
- FormRequests that reuse the headless validation rules rather than
  re-implementing them, keeping validation in a single source of truth.
- Secret masking applied to API responses so sensitive `.env` values are never
  returned in clear text.
- HTML read panel built on a theme-adapter architecture
  (`ThemeAdapterInterface` + `ThemeManager`): Unstyled, Tailwind, and Bootstrap
  adapters built in; Filament and Nova adapters registered only when those
  frameworks are installed (`class_exists`-guarded).
- Support for consumer-registrable custom theme adapters.

[Unreleased]: https://github.com/laranail/env-kit-webui/compare/v0.2.1...HEAD
[0.2.1]: https://github.com/laranail/env-kit-webui/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/laranail/env-kit-webui/compare/v0.1.2...v0.2.0
[0.1.2]: https://github.com/laranail/env-kit-webui/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/laranail/env-kit-webui/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/laranail/env-kit-webui/releases/tag/v0.1.0
