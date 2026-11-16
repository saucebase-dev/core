---
name: saucebase-template-development
description: Create and develop Saucebase templates — a saucebase-template package that installs a whole app (its modules, features, replaced app files and demo state) into a new application. Use when setting up a template workspace, running saucebase:template-capture or saucebase:template-install, writing a template's stubs/ or Default…DatabaseSeeder, or installing an app with --template.
---

# Saucebase Template Development

A template is a module whose Composer type is `saucebase-template`, plus `stubs/`: app files it replaces on install. It installs once, into a new app, and the app owns the copied files. Everything the module skill says about code (`saucebase-module-development`) applies here too.

## Where you work

A template is built inside an app: the app runs it, the template is `modules/<name>`, and `saucebase.json` names it (`"template": "<name>"`). An app builds one template.

| App | Created with | Frontend files |
|---|---|---|
| Normal app (anyone) | `saucebase new <app> --modules=… --make-template=<vendor>/<name>` | App flat (`resources/js/pages`); template keeps `resources/js/{stack}/` |
| Contributor checkout (Saucebase team, also changing core or modules) | Contributor setup, `"template"` in `saucebase.json`, `tsconfig` `@/*` with `./resources/js/{stack}/*` first, then `saucebase:recipe <Name> template` | Both stacks, in their folders |

In an existing app: `php artisan saucebase:recipe <Name> template --vendor=<vendor>`, then `composer update <vendor>/<name>`.

`template-install` refuses a contributor checkout and an app that already has a template.

## The template's composer.json

The recipe writes it: `type: saucebase-template`, `require` = core and every Saucebase module the app has, `extra.saucebase.frameworks` = the stacks it kept.

- A module added later: `saucebase:module-install <module>`, then add it to `require` by hand.
- `extra.saucebase.configure` (optional): module settings for the install, e.g. `{"auth": {"registration_enabled": true}}`.
- The demo's account goes on `Default{Template}DatabaseSeeder`, not here: `#[DemoAccount('notes@saucebase.dev', 'your notes and lists', featured: true)]`. The installer names it after `--demo` (see `saucebase-module-development`, Seeders).
- Keep the template's frontend in `resources/js/{stack}/`. The installer flattens it into apps of that stack and refuses a module without the folder.

## Stubs: replacing app files

- Edit the app's file (`resources/js/pages/Index.vue`, `routes/web.php`; in a contributor checkout `resources/js/vue/pages/Index.vue`), then `php artisan saucebase:template-capture <path>`. In a normal app, frontend files are filed under the stack folder for you. With no arguments it refreshes every existing stub; `--check` fails when one is stale.
- `stubs/` mirrors the app root. `resources/js/{vue,react}/…` and `resources/views/{vue,react}/…` lose the stack folder on install; the other stack's stubs are skipped.
- Refused: `composer.json`, `composer.lock`, `.env*`. `stubs/.env.example` is allowed: its missing keys are added to the app's `.env`.
- Import app code in stubs through `@/…`.
- Only replace what the template must. A stub is copied once; later template releases never reach existing apps.
- Most templates replace the starter kit's `routes/navigation.php` (its GitHub and Documentation links, "Welcome" for the dashboard) and `resources/js/{stack}/components/Footer.vue` (its credit), besides the home page and `routes/web.php`.

## Data

| Seeder | Runs |
|---|---|
| `DatabaseSeeder` | Every install: plans, permissions, anything the app needs to work |
| `Default<Name>DatabaseSeeder` | Only with `--demo` / `--state=default`: sample accounts and content |

- `default` is the template's demo. Modules don't ship `Default…` seeders; the template recipe scaffolds an empty one.
- Seeders run twice without duplicating (`firstOrCreate`, `updateOrCreate`). Test that: run the state twice.
- Demo passwords come from `config('saucebase.demo_password')`. Never a literal. `modules:seed` makes a random one when it's unset.
- A clean install has no demo data, so every empty page needs an empty state and a first action.

## Security

- Per-user records: bind them among the signed-in user's own (`Route::bind('note', fn ($id) => Note::where('user_id', Auth::id())->findOrFail($id))`), and child records through their parent (`scopeBindings()`). Someone else's is then a 404. Never guard ownership with a gate or policy alone: core's `Gate::before` lets admins pass every check.
- Validate into arrays and set the owner with `associate()`; never `$request->all()`.
- Escape `LIKE` input with a portable character (`escape '!'`), not a backslash (MySQL).

## Tests

Write each behaviour's failing test first, in `modules/<name>/tests`:

- Feature tests for routes and the state (`modules:seed --module=<name> --state=default`, twice).
- E2E for the replaced pages (`npx playwright test --project="@<Name>*"`; first run needs `php artisan playwright:env`).

## Check it as a user gets it

In `sandboxes/`, never in the app that builds it:

```bash
saucebase new my-app
cd my-app
composer config repositories.<name> '{"type":"path","url":"<path to modules/<name>>","options":{"symlink":false}}'
php artisan saucebase:template-install <vendor>/<name> [--state=default]   # no DEMO_PASSWORD? one is made and saved
npm install && npm run build
```

From a registry the installer knows: `saucebase new my-app --template=<vendor>/<name> [--demo]`.

## Gotchas

- `template-install` takes the full package name; a bare name means `saucebase/<name>`.
- A path repository must copy (`"symlink": false`); the module installer refuses a symlink.
- Iterating through a path repository: `composer update` changes nothing while `version` stays the same. Run `composer reinstall <vendor>/<name>`, then `template-install` again.
- An app takes one template, installed into a fresh app. A failed run prints the command to retry.
