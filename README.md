# Saucebase Core

[![Latest Version](https://img.shields.io/packagist/v/saucebase/core.svg)](https://packagist.org/packages/saucebase/core)
[![Downloads](https://img.shields.io/packagist/dt/saucebase/core.svg)](https://packagist.org/packages/saucebase/core)
[![Tests](https://github.com/saucebase-dev/core/actions/workflows/run-tests.yml/badge.svg)](https://github.com/saucebase-dev/core/actions/workflows/run-tests.yml)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=saucebase-dev_core&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=saucebase-dev_core)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

**The AI-ready Laravel SaaS kit.** Everything your SaaS needs, ready for your AI to build on.

This is the package behind [Saucebase](https://github.com/saucebase-dev/saucebase): the Vue or React starter UI, the module system, navigation, settings, localization and the admin panel. Every module ships Laravel Boost guidelines and skills, so your AI agent knows how it works from the first prompt.

## Install

The quickest way is a new app with everything set up:

```bash
curl -fsSL https://install.saucebase.dev | bash
```

Into a new Laravel app:

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
