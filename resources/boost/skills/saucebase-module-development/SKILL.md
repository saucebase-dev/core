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
4. _(frontend)_ Does it have public pages that need SSR?
5. Does it need a seeder?
6. _(frontend, logged-in area)_ Does it need navigation entries and breadcrumbs?
7. _(frontend)_ Should it have E2E tests?

Write each behavior's failing test before its implementation.

## Scaffolding

```bash
php artisan saucebase:recipe Example basic
composer update <vendor>/example
npm run build   # or restart `npm run dev`
```

`basic` is the recipe's folder name. Core ships it in `stubs/recipes/basic`; an application adds its
own as `stubs/recipes/<name>/`, with a `recipe.json` (`{"name": "…"}`) naming it in the prompt. Run the
command with no recipe to choose from the list.

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

Share Inertia props from `shareInertiaData()` (`Inertia::share('auth.user', fn () => ...)`), never directly in
`boot()`: it is core's hook for shared props, and keeping every share there makes them easy to find. Pass a
closure so the value resolves per request; scope a page-only prop inside it (`request()->routeIs('index') ? ... : null`).

List extra providers in `protected array $providers`. `replaceConfig($path, $key)` overwrites a whole
config key where `mergeConfigFrom` would merge numeric arrays wrongly. Call `parent::boot()` when
overriding `boot()`.

## Event Listeners

Put listeners in `src/Listeners/` with a `handle()` method typed to the event
(`handle(PasswordReset $event)`). The module loader discovers them while event discovery is on, which is
Laravel's default (`app-modules.should_discover_events` overrides it). Never also call `Event::listen()` for
them in the provider: the listener would run twice. `php artisan event:list` shows each listener once.

Discovery only covers dispatched events. Eloquent model events on a model the module doesn't own (the
app's `User`) are hooked in the provider's `boot()`:

```php
$users = config('auth.providers.users.model');

$users::deleted(fn (Model $user) => ...);
```

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

## Capabilities

`Saucebase\Core\Capabilities` is one catalog of what can be granted, grouped by who reads it:
`api` (what an API token may be scoped to, read by API Tokens) and `billing` (keys a product's
entitlements offer, read by Billing's product form). It only lists; each group's reader checks
access its own way. Core loads `routes/capabilities.php` from the app and every installed module
the first time a group is read, like navigation:

```php
use Saucebase\Core\Facades\Capabilities;

Capabilities::add('api', ['posts' => ['label' => 'Blog posts', 'description' => '…', 'read_only' => true]]);
Capabilities::add('billing', fn (): array => [...]); // from data: runs only when the group is read
```

A missing label falls back to the key. In tests, bind a fresh `Capabilities` (with a
`ModuleRegistry` that has no modules) to control the exact list; in E2E, add entries from a
`laravel.registerBootFunction()` helper.

## Toasts

`Saucebase\Core\Toast::success($message, $description)` (also `default`, `error`, `info`,
`warning`, `loading`) flashes a toast for the next page, through Inertia or the session for full-page
redirects such as OAuth callbacks.

## Seeders

`php artisan modules:seed` runs `Modules\<Name>\Database\Seeders\DatabaseSeeder` for every installed module
that has one; `--module=<name>` limits it to one. `--state=<state>` then also runs `<State><Name>DatabaseSeeder`, after
every module's required data exists; `showcase` is the sample content (`ShowcaseBlogDatabaseSeeder`). A state
no module provides fails. In production it asks first; `--force` (also on `saucebase:module-setup` and
`saucebase:module-install`) skips the question. Each set runs in registry order, except that a seeder marked
`#[SeedAfter('billing')]` (`Saucebase\Core\Attributes\SeedAfter`) runs after the named modules' seeder of the
same kind; modules that aren't installed are ignored. A module with an admin area creates its `manage {module}` permission
there (see `saucebase-filament-development`); state seeders never hold anything an install needs.

A state seeder that creates accounts declares them on its class, one attribute each:
`#[DemoAccount('owner@saucebase.dev', 'owns Acme and Globex', featured: true)]`
(`Saucebase\Core\Attributes\DemoAccount`). `php artisan modules:demo-accounts --state=showcase` lists them
(`--json` for the installer); `featured` ones are named where people start, after `modules:seed --state` and in
the installer's summary. Feature only the one or two to sign in with first. They all use `DEMO_PASSWORD`.

## Patches

A module changes application files it doesn't own (the `User` model, `routes/web.php`) with
`patches/*.patch`, applied by `git apply` on install. Several modules patch the same file, in
any order, so a patch only **adds** lines and never edits one another module may need:

- Imports and traits: add your own `use` line beside a neighbour.
- Interfaces: the stub's `User` lists one per line after `implements`. Insert `    YourInterface,`
  at a spot no other module uses, never at the end (the last entry has no comma).
- The app's `pint.json` sets `class_definition.single_line: false`, so Pint keeps that layout.

## Frontend Imports

A module imports Saucebase's frontend code through the `@saucebase/core` and
`@saucebase/<framework>` aliases (this package's `resources/js`), and the app's UI
through `@/components`, `@/layouts` and `@/lib/utils`. The app's
`saucebase-frontend-development` skill lists exactly what is available. Never import a
`vendor/` path.

## Stories

A module's components get Storybook stories beside them, as core's do:
`resources/js/vue/components/Banner.stories.ts` and `resources/js/react/components/Banner.stories.tsx`.
Storybook and `npm run test:stories` find every module's stories for the app's stack; they list
under `Modules/`.

Write stories for every component that shows something and can render from props alone:
UI, design-system and module components, and any component with states of its own (empty,
error, loading). Skip the rest; E2E covers them:

- Pages.
- Slots that only read the page and pass it on (`ComingSoonBannerSlot`).
- Components with no UI of their own (a toast fired on page load).
- Large components that only compose others: their parts get the stories.

A component that reads the page, sends requests or submits a form but should have stories
splits in two: a view that takes props and emits events, with the stories, and a slot or
parent that reads the page and sends the request (`TenantSwitcher` and `TenantSwitcherSlot`).

- Set `title: '<Module>/<Component>'`. Storybook adds the `Modules/` prefix.
- Import the preview as core's stories do (`import preview from '@/.storybook/preview'`) and
  page fixtures from `@/.storybook/page`.
- A component that reads page props gets them per story with `parameters: { inertia: { … } }`.
  Storybook merges parameters, so a story can override a meta's prop but not remove it.
- Every story is a test: it must render, and its `play` function must pass. Inertia visits are
  cancelled and logged in the Actions panel, so a story never needs a server.
- A plain link (`<a href>`) in an example points at `#`. Plain link clicks are cancelled and
  logged too, but `#` shows the reader it goes nowhere. Inertia links keep real paths: Inertia
  renders `#` as `href=""`, which is not a link to screen readers or to `getByRole('link')`.
- A story of a component that fetches on render needs a network mock; there is none, so don't
  write one for it.

### Controls

Every control in the Controls panel must change the story, following Storybook's
[Controls guide](https://storybook.js.org/docs/essentials/controls). The preview already
shows each control's description and default, and hides `as`, `asChild`, `by`, `testId`,
`defaultValue` and `modelValue`. A component reads `defaultValue` only once and `modelValue`
two-way, so a control can't change either; stories still set both in `args`.

- Let docgen choose controls from the prop types. Write `argTypes` only for a control that
  docgen gets wrong.
- Give union props a type without `null`: docgen shows `'a' | 'b' | null` as a JSON
  editor. Wrap cva's types: `variant?: NonNullable<AlertVariants['variant']>`.
- Give a prop that takes a component (an icon) a list of names and a `mapping` to the real
  components. Put the name in `args`, cast it (`icon: 'Rocket' as never`), because `mapping`
  swaps it for the component.
- Give a text slot an arg with a starting value, and show it in the render
  (`{{ args.default }}`). Set `control: false` on a slot the render fills itself.
- Leave object and array props on the JSON editor: that is their control.
- Give every main prop a starting value in the meta's `args`.
- Keep `fn()` out of the meta's `args`: Storybook can't print a call as an event handler,
  so every story's snippet gets an "Incomplete code snippet" warning. Put the spy only on
  the story whose `play` reads it, or handle the event in a decorator that passes its own
  handler (`story({ args: { onDismiss } })`) and logs it with `action()`.
- Render stories inline on the docs page: controls there don't reach a story in its own
  frame (`docs.story.inline: false`). A component that sticks or scrolls with the page
  gets a scrolling box of its own in a decorator instead.
- Describe every prop, slot and event with a JSDoc comment in the component: the panel shows
  it next to the control.

## npm Packages

Every module has a `package.json` (the recipe creates it), with only `name` and `private` until
the module imports an npm package core's stack manifest doesn't list. The app's `package.json`
declares `"workspaces": ["modules/*"]`, so `npm install` puts a module's packages in the root
`node_modules`, with no edit to the app's manifest. Each module is a workspace, so adding one
changes the app's `package-lock.json`: run `npm install` after adding one, or `npm ci` refuses
the stale lockfile.

```json
{
    "name": "@saucebase/reports",
    "private": true,
    "dependencies": { "chart.js": "^4.4.0" },
    "devDependencies": { "@iconify-json/mdi": "^1.2.3" }
}
```

- Build and test tools (icon sets for `~icons/…`, `@faker-js/faker` for E2E) are `devDependencies`.
- Never list `vue`, `react` or a package core already ships: a module carries code for both stacks
  and only one is installed, so those are `peerDependencies` at most.
- Installing a module doesn't run npm. Until `npm install` runs, Vite fails on the module's
  imports.

## TypeScript Types

PHP enums and classes marked `#[TypeScript]` are exported to `resources/js/types/generated.d.ts`. Never
edit that file; regenerate it with `php artisan module:generate-types <name>`.
