# Summary

<!-- Briefly describe what this PR changes and why. -->

## Type of change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation
- [ ] Refactor / internal

## Checklist

- [ ] Tests pass (`vendor/bin/pest`)
- [ ] Static analysis passes (`vendor/bin/phpstan analyse --no-progress`)
- [ ] Code style passes (`vendor/bin/pint --test`)
- [ ] The web layer only drives the engine — no engine logic was added here
      (business logic belongs in `laranail/env-kit-headless`)
- [ ] `CHANGELOG.md` updated
