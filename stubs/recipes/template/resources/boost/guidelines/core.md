## {Module } template

`modules/{module-}` (namespace `Modules\{Module}`) is a Saucebase template: describe the app it starts in one or two sentences.

- App files it replaces live in `stubs/`; edit the app's file, then run `saucebase:template-capture`. Never edit `stubs/` by hand.
- Its demo is `Default{Module}DatabaseSeeder`, safe to run twice. Data every install needs goes in `DatabaseSeeder`.

Activate `saucebase-template-development` and `saucebase-{module-}-development` before changing this template.
