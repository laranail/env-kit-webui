# Security Policy

## Supported versions

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

This package is in pre-release (`0.1.0`). Security fixes are applied to the
latest `0.1.x` line.

## Surface and threat model

`laranail/env-kit-webui` ships an HTTP surface (a JSON CRUD API and an HTML
panel) that edits `.env` files and therefore handles secrets. The surface is:

- **Disabled by default** — it is not exposed unless a consumer explicitly
  opts in.
- **Auth-gated** — requests must pass the configured authorization layer.
- **Production-write-blocked** — write operations are refused in production
  environments.

Secret values are **masked** in API responses, so sensitive `.env` values are
never returned in clear text. Even so, treat any deployment of this surface as
security-sensitive and restrict it to trusted operators on trusted networks.

## Reporting a vulnerability

Please report suspected vulnerabilities **privately**. Do **not** open a public
GitHub issue, pull request, or discussion for a security report.

Email **opensource@simtabi.com** with:

- A description of the issue and its impact.
- Steps to reproduce (proof-of-concept where possible).
- Affected version(s) and environment details.

You can expect an acknowledgement within **about 3 business days**. We will
work with you on a fix and coordinate disclosure once a patch is available.

Thank you for helping keep the laranail ecosystem and its users safe.
