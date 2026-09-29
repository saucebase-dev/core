## Saucebase core

`saucebase/core` (namespace `Saucebase\Core`) loads modules and provides what they build on: the module
service provider, navigation, breadcrumbs, the settings modal, the sitemap, toasts, and the Filament
module plugin.

- Modules are copy-and-own Composer packages in lowercase `modules/<name>/` directories, with TitleCase
  namespaces. An installed module is active; there is no enable/disable toggle.
- A module's main provider extends `Saucebase\Core\Providers\ModuleServiceProvider` without `$name` or
  `$nameLower`; the base provider looks the name up with `ModuleRegistry::moduleForClass()`.
- Traits live in `Traits/`, never `Concerns/`, including internal helpers (`Filament/Traits/`,
  `Console/Traits/`), so the directory says what the file is.
- A module's agent context lives in its `resources/boost/`. `composer boost:update` runs `modules:boost`,
  which links each module into the vendor path Boost scans and lists it in `boost.json`, so never list
  modules there by hand.

Activate `saucebase-module-development` before creating or changing a module, and
`saucebase-filament-development` before changing a module's Filament code.
