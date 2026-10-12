# Saucebase Core

Everything [Saucebase](https://github.com/saucebase-dev/saucebase) adds on top of a bare Laravel application: navigation, localization, module infrastructure, the settings modal, and the Inertia layer.

## Install

In a new Laravel application:

```bash
composer config allow-plugins.saucebase/module-installer true
composer require saucebase/core
php artisan saucebase:install vue   # or react
npm install && npm run build
php artisan migrate --seed
```

The first line trusts the Composer plugin that installs modules into `modules/`. Composer asks about it when run by hand, but refuses the plugin outright when run unattended (`--no-interaction`, CI).

`saucebase:install` copies the starter pages, the build configuration and a few PHP files into the application, which owns them from then on. A framework is chosen once: the command refuses to run again after it has been set.

It replaces these files of a new Laravel application, and asks first unless you pass `--force`:

- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `database/seeders/DatabaseSeeder.php`
- `routes/web.php`
- `public/favicon.ico`
- `tests/Feature/ExampleTest.php`

The example test is replaced because the home page now reads the settings table, so the test has to migrate the database (`RefreshDatabase`) to pass.

Core has no sign-in pages. Until you add them, guests get a 404 on the dashboard and the admin panel:

```bash
composer require saucebase/auth
```

After adding a module, run its migrations:

```bash
php artisan migrate
```

The install also adds two lines to `post-autoload-dump` in `composer.json`, so they run after every Composer command:

- `filament:upgrade` republishes the admin panel's assets, which a Filament update replaces.
- `module:generate-types --all` generates the TypeScript types of the installed modules, which ship without them.

## What lives here, and what doesn't

Core owns the plumbing. The application owns everything a user would realistically open and edit — `App\Models\User`, routes, controllers, the Filament panel provider, and the whole frontend under `resources/js`.

The rule: **the app keeps a file only if a user must open it to do a normal thing.**

Core is deliberately not a general-purpose Laravel package. It depends on Inertia, Filament and `internachi/modular`, because nothing uses Saucebase without them.

## License

MIT
