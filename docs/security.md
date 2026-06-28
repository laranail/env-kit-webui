# Security

The web surface is the most sensitive part of EnvKit — it exposes `.env` over HTTP.
It ships locked down by three independent guardrails.

## 1. Disabled by default

Nothing responds until you explicitly opt in:

```dotenv
ENV_KIT_WEBUI_ENABLED=true
```

While disabled, every API and panel route returns `404` — checked per request, so
the surface can be toggled at runtime.

## 2. Auth-gated

Routes run behind the middleware in `config('env-kit-webui.route.middleware')` /
`web_middleware` — `auth:sanctum` for the API and `web`+`auth` for the panel by
default. Adjust to your stack; do **not** ship it unauthenticated.

## 3. Production-write-blocked

The headless engine's production guard applies to every write made through the API.
In production, writes are refused with `403` unless deliberately overridden — there
is no UI affordance to bypass it casually.

## Secret handling

- Secret-shaped values (the engine's `hidden_keys`) are **masked** in API responses
  and the panel unless `reveal_secrets` is enabled.
- The engine redacts secrets from logs, exceptions, audit records, and events, so a
  raw secret never escapes through the web layer either.

## Reporting

Found an issue? See [SECURITY.md](../SECURITY.md) — report privately to
`opensource@simtabi.com`, never via a public issue.

---

[← Docs index](../README.md#documentation)
