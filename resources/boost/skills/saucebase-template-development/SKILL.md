---
name: saucebase-template-development
description: Create and develop Saucebase templates — a saucebase-template package that installs a whole app (its modules, features, replaced app files and demo state) into a new application. Use when setting up a template workspace, running saucebase:template-capture or saucebase:template-install, writing a template's stubs/ or Default…DatabaseSeeder, or installing an app with --template.
---

# Saucebase Template Development

A template is a module whose Composer type is `saucebase-template`, plus `stubs/`: app files it replaces on install. It installs once, into a new app, and the app owns the copied files. Everything the module skill says about code (`saucebase-module-development`) applies here too.

## Where you work

| Place | Signs | What you do there |
|---|---|---|
| Template workspace | `saucebase.json` has `"contributor": true` and `"template": "<name>"` | Build the template |
| Installed app | No `contributor` | Use it. Never edit a template from an app |

`template-capture` runs only in a template workspace; `template-install` refuses one.

## Set up a workspace (by hand, once)

1. Contributor setup (CONTRIBUTING.md, "Set up").
2. `saucebase.json`: add `"template": "<name>"`.
3. `tsconfig.json`: `"@/*": ["./resources/js/{stack}/*", "./vendor/saucebase/core/stubs/stack/{stack}/js/*"]`, and add `resources/js/{stack}/**/*.ts` and `**/*.vue` (or `.tsx`) to `include`.
4. `php artisan saucebase:recipe <Name> basic --vendor=saucebase --type=saucebase-template`, then `composer update saucebase/<name>`.

## The template's composer.json

- `require`: every module it needs, by full name and constraint. Composer is the list; don't repeat it elsewhere.
- `extra.saucebase.frameworks`: the stacks it ships (`["vue"]`). Delete the recipe's folder for a stack you don't ship.
- `extra.saucebase.configure` (optional): module settings for the install, e.g. `{"auth": {"registration_enabled": true}}`.

Install the same modules in the workspace: `php artisan saucebase:module-install <module>`, then `npm install && npm run build`.

## Stubs: replacing app files

- Edit the app file in the workspace (`resources/js/vue/pages/Index.vue`, `routes/web.php`), then `php artisan saucebase:template-capture <path>`. With no arguments it refreshes every existing stub; `--check` fails when one is stale.
- `stubs/` mirrors the app root. `resources/js/{vue,react}/…` and `resources/views/{vue,react}/…` lose the stack folder on install; the other stack's stubs are skipped.
- Refused: `composer.json`, `composer.lock`, `.env*`. `stubs/.env.example` is allowed: its missing keys are added to the app's `.env`.
- Import app code in stubs through `@/…`.
- Only replace what the template must. A stub is copied once; later template releases never reach existing apps.

## Data

| Seeder | Runs |
|---|---|
| `DatabaseSeeder` | Every install: plans, permissions, anything the app needs to work |
| `Default<Name>DatabaseSeeder` | Only with `--demo` / `--state=default`: sample accounts and content |

- `default` is the template's demo. Modules don't ship `Default…` seeders. The recipe scaffolds `Showcase…`: rename it.
- Seeders run twice without duplicating (`firstOrCreate`, `updateOrCreate`). Test that: run the state twice.
- Demo passwords come from `config('saucebase.demo_password')`. Never a literal. `modules:seed` makes a random one when it's unset.
- A clean install has no demo data, so every empty page needs an empty state and a first action.

## Tests

Write each behaviour's failing test first, in `modules/<name>/tests`:

- Feature tests for routes and the state (`modules:seed --module=<name> --state=default`, twice).
- E2E for the replaced pages (`npx playwright test --project="@<Name>*"`; first run needs `php artisan playwright:env`).

## Check it as a user gets it

In `sandboxes/`, never in the workspace:

```bash
saucebase new my-app                     # plain app
cd my-app
composer config repositories.<name> '{"type":"path","url":"<path to modules/<name>>","options":{"symlink":false}}'
php artisan saucebase:template-install <name> [--state=default]   # no DEMO_PASSWORD? one is made and saved
npm install && npm run build
```

From a registry the installer knows: `saucebase new my-app --template=saucebase/<name> [--demo]`.

## Gotchas

- The package is looked up as `saucebase/<name>`: `template-install` takes the short name.
- A path repository must copy (`"symlink": false`); the module installer refuses a symlink.
- An app takes one template, installed into a fresh app. A failed run prints the command to retry.
