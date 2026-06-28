# BUILD-LOG — laranail/env-kit-webui

Append-only log of build progress. Each entry: what changed · tests covering it · what's still open.
Spec: `_scratch-files/dotenv-editor-consolidation-plan.md`. WebUI drives the headless engine; it holds
no engine logic.

## Phase checklist

- [x] **Phase 0 — repo setup** — git identity, `.gitignore` (+ `.phpstan.cache`), tooling (phpunit/pint/phpstan), BUILD-LOG.
- [~] **Phase 5 — build webui** — UI-agnostic core + JSON API + theme/panel adapters + quality bar (i18n/a11y/responsive/dark); full test regime green; adapters `class_exists`-guarded.
  - [x] slice 1 — install wiring (path repo → ../headless, livewire ^4.1 for Filament 5) + provider + config + JSON read API (keys index/show) driving the engine, enabled-gate, secret masking.
  - [x] slice 2 — write API (store/update/destroy) reusing headless Rules + production/protected guards.
  - [ ] slice 3+ — adapters (unstyled/tailwind/bootstrap/filament/nova), ViewModels, i18n/a11y.
- [ ] **Phase 6 — docs** — README + docs/ set.
- [ ] **Phase 7 — release** — after headless, after explicit approval.

## Notes

- Requires `laranail/env-kit-headless`; for local dev use a `path` repository → `../headless`.
- Disabled by default; auth-gated; prod-blocked. Validation reuses the headless `Rules/`.

## Log

### Phase 5 — slice 1 (JSON read API) green
- Composer wiring: `path` repo → `../headless` (symlink); headless gets `branch-alias dev-main → 1.0.x-dev`
  so `^1.0` resolves locally. Bumped `livewire/livewire` to `^4.1` (Filament 5 requires it).
- `EnvKitWebUIServiceProvider` (on package-tools; `hasConfigFile('env-kit-webui')`); routes registered
  under the config prefix, gated by `EnsureEnvKitWebUIEnabled` (404 unless `enabled`).
- `Http/Controllers/EnvController` (index/show) **drives `EnvKitInterface`** — no engine logic here;
  secret-shaped values masked via the headless `SecretRedactor` unless `reveal_secrets`.
- **5 tests** (Testbench): disabled→404, masked listing, show, unknown-key→404, reveal. L9 + Pint clean.
- Note: the `path` repository in `composer.json` is dev-only (consumers resolve headless from Packagist).

### Phase 5 — slice 2 (JSON write API) green
- `EnvController` now CRUD; injects the concrete `EnvKit` and drives `set()/forget()` (every write hits
  the engine's atomic/guarded/audited commit). `Http/Requests/{Store,Update}EnvVariableRequest` **reuse
  the headless `Rules/` (`ValidEnvKey`/`ValidEnvValue`)** → invalid input is 422 before the engine.
- `guardWrite()` maps engine guard failures to HTTP: `ProductionGuard`/`ProtectedKey` → **403**, other
  `EnvKitException` → 422 (messages are secret-safe).
- **12 tests** incl. POST create, 422 invalid key, PUT update, 404 missing, DELETE, **protected-key 403**,
  **production-write 403**. L9 + Pint clean.
- Testbench note: the path-repo symlink blocks the headless config auto-merge, so the test harness loads
  `vendor/laranail/env-kit-headless/config/env-kit.php` explicitly (real installs merge it normally).
