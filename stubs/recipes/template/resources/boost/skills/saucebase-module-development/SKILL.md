---
name: saucebase-{module-}-development
description: Work on the {Module } template (modules/{module-}). Use when changing its routes, models, pages, stubs, demo, or tests.
---

# {Module } Template

Paths are relative to `modules/{module-}/` unless they say otherwise. Commands run from the application root. How templates work (stubs, states, publishing): `saucebase-template-development`.

## Key Files

| Layer | Files |
|-------|-------|
| Provider | `{Module}ServiceProvider` |
| Controller | `{Module}Controller` |
| Demo | `database/seeders/Default{Module}DatabaseSeeder.php` |
| Replaced app files | `stubs/` (written by `saucebase:template-capture`) |

## Testing

```bash
php artisan test --testsuite=Modules --filter='^Modules\\{Module}\\Tests'  # PHPUnit
npx playwright test --project="@{module-}*"                               # E2E
```
