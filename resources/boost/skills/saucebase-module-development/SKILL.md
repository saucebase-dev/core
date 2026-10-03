---
name: saucebase-module-development
description: Create and develop Saucebase modules — scaffolding, the module service provider, config, navigation, breadcrumbs, settings sections, sitemap entries, toasts, seeders, and generated TypeScript types. Use when working under modules/, running saucebase:recipe, modules:seed, or module:generate-types, or changing module structure.
---

# Saucebase Module Development

Paths are relative to the module's root, `modules/<name>/`. Commands run from the application root.

## Before Writing Code

Ask one question at a time, and skip frontend questions for admin-only modules:

1. Does it have Inertia pages, or is it admin-only (Filament)?
2. Does it need models and migrations?
3. Does it need Filament resources? If yes, activate `saucebase-filament-development`.
4. *(frontend)* Does it have public pages that need SSR?
5. Does it need a seeder?
6. *(frontend, logged-in area)* Does it need navigation entries and breadcrumbs?
7. *(frontend)* Should it have E2E tests?

Write each behavior's failing test before its implementation.

## Scaffolding

```bash
php artisan saucebase:recipe Example "Basic Recipe"
composer update <vendor>/example
npm run build   # or restart `npm run dev`
```

The recipe creates `modules/example/`, including its own `resources/boost` guideline and
`saucebase-example-development` skill. Keep both current as the module grows: the guideline holds the
few rules an agent must always know, the skill holds the rest. Run `composer boost:update` after
changing either.

Then apply the answers:

- **Admin-only** → empty `routes/navigation.php`; a `route()` call to a missing route throws when navigation renders.
- **No frontend pages** → delete the scaffolded pages and skip E2E setup.
- **Has E2E tests** → `tests/e2e/index.spec.ts`, selecting by `data-testid` only.

## Service Provider

`src/Providers/<Name>ServiceProvider.php` extends `Saucebase\Core\Providers\ModuleServiceProvider`. The
base provider already:

- merges `config/config.php` under the module's lowercase name (`config('<name>.key')`), in `register()`,
  so other providers can read it while booting;
- loads translations from the module's root `lang/` as `<name>::` (`__('<name>::file.key')`);
- publishes `resources/assets` to `public/modules/<kebab-name>` (tag `module-assets`);
- under `runningUnitTests()`, loads migrations from `tests/Support/migrations/`. Tables for test-only
  models go there, never in a `Schema::create` inside a test: on MySQL that DDL commits the test's
  transaction.

Override `shareInertiaData()` for props every page needs (`Inertia::share('auth.user', fn () => ...)`),
and list extra providers in `protected array $providers`. `replaceConfig($path, $key)` overwrites a whole
config key where `mergeConfigFrom` would merge numeric arrays wrongly. Call `parent::boot()` when
overriding `boot()`.

## Navigation

Core loads `routes/navigation.php` from the app and from every installed module.

```php
Navigation::add('Roadmap', fn () => route('roadmap.index'), function (Section $section) {
    $section->attributes(['group' => 'landing', 'slug' => 'roadmap', 'icon' => 'roadmap', 'order' => 2]);
});
```

Pass the URL as a closure: it resolves when navigation renders, not when the file loads. `Navigation::addWhen($condition, ...)`
shows an item only while `$condition` returns true. Besides `group`, `slug`, `icon`, and `order`, items may
set `action`, `type`, `external`, `newPage`, `class`, and `badge`. The `icon` and `action` names must be
registered by the frontend; see `saucebase-frontend-development`.

## Breadcrumbs

Core loads `routes/breadcrumbs.php` from every installed module:

```php
use Saucebase\Breadcrumbs\Breadcrumbs;
use Saucebase\Breadcrumbs\Generator as Trail;

Breadcrumbs::for('roadmap.index', function (Trail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('Roadmap'), route('roadmap.index'));
});
```

## Settings Modal Sections

Put a `Saucebase\Core\Settings\SettingsSection` subclass in `src/Settings/`; `SectionRegistry` discovers
it, so there's nothing to register. Implement `slug()`, `title()` (translated), `component()`
(`'<Name>::SettingsExample'`), and `props()`. Optionally override `icon()`, `order()` (default 100), and
`visible()`, which runs per request. The section's constructor is resolved from the container, so inject
dependencies there. Redirect into a section with `SettingsSection::url($slug)`.

Spatie settings classes share `src/Settings/`; their Filament pages extend
`Saucebase\Core\Filament\Pages\SettingsPage`, which puts them in the admin Settings group.

## Sitemap

Add URLs from the provider's `boot()`. The callback runs only when `/sitemap.xml` is requested:

```php
$this->app->make(SitemapRegistry::class)->add(fn (Sitemap $sitemap) => $sitemap
    ->add(route('blog.index'))
    ->add(Post::published()->get()));
```

## Toasts

`Saucebase\Core\Helpers\Toast::success($message, $description)` (also `default`, `error`, `info`,
`warning`, `loading`) flashes a toast for the next page, through Inertia or the session for full-page
redirects such as OAuth callbacks.

## Seeders

`php artisan modules:seed` runs `Modules\<Name>\Database\Seeders\DatabaseSeeder` for every installed module
that has one; `--module=<name>` limits it to one. `--demo` then also runs `Demo<Name>DatabaseSeeder`, after
every module's required data exists.

## TypeScript Types

PHP enums and classes marked `#[TypeScript]` are exported to `resources/js/types/generated.d.ts`. Never
edit that file; regenerate it with `php artisan module:generate-types <name>`.
