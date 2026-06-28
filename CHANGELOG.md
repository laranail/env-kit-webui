# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-06-28

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

[Unreleased]: https://github.com/laranail/env-kit-webui/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/laranail/env-kit-webui/releases/tag/v0.1.0
