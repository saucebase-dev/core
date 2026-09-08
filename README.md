# Saucebase Core

Everything [Saucebase](https://github.com/saucebase-dev/saucebase) adds on top of a bare Laravel application: navigation, localization, module infrastructure, the settings modal, and the Inertia layer.

## Install

```bash
composer require saucebase/core
```

The service provider is auto-discovered.

## What lives here, and what doesn't

Core owns the plumbing. The application owns everything a user would realistically open and edit — `App\Models\User`, routes, controllers, the Filament panel provider, and the whole frontend under `resources/js`.

The rule: **the app keeps a file only if a user must open it to do a normal thing.**

Core is deliberately not a general-purpose Laravel package. It depends on Inertia, Filament and `internachi/modular`, because nothing uses Saucebase without them.

## License

MIT
