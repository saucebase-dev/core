# Saucebase Core

[![Latest Version](https://img.shields.io/packagist/v/saucebase/core.svg)](https://packagist.org/packages/saucebase/core)
[![Downloads](https://img.shields.io/packagist/dt/saucebase/core.svg)](https://packagist.org/packages/saucebase/core)
[![Tests](https://github.com/saucebase-dev/core/actions/workflows/run-tests.yml/badge.svg)](https://github.com/saucebase-dev/core/actions/workflows/run-tests.yml)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=saucebase-dev_core&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=saucebase-dev_core)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

What [Saucebase](https://github.com/saucebase-dev/saucebase) adds to a Laravel app: the Vue or React starter UI, modules, navigation, the settings modal, localization and the admin panel.

## Install

The quickest way is a new app with everything set up:

```bash
curl -fsSL https://install.saucebase.dev | bash
```

Into an existing Laravel app:

```bash
composer config allow-plugins.saucebase/module-installer true
composer require saucebase/core
composer require --dev saucebase/laravel-playwright
php artisan saucebase:install vue   # or react
npm install && npm run build
php artisan migrate --seed
```

`saucebase:install` copies the starter UI and a few files into the app, which owns them from then on. It asks before replacing files unless you pass `--force`.

### With an AI agent

Paste this into Claude Code, Cursor or any coding agent, from the root of a Laravel app:

```text
Add Saucebase to this Laravel app. Run
`composer config allow-plugins.saucebase/module-installer true`,
`composer require saucebase/core`,
`composer require --dev saucebase/laravel-playwright`,
`php artisan saucebase:install vue --force`, then
`npm install`, `npm run build` and `php artisan migrate --seed`.
Tell me if any step fails.
```

## License

MIT.

---

The documentation is being updated for v3.
