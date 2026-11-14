# {Module }

A Saucebase template. Describe the app it starts in one sentence.

## What you get

- `/`: the template's home page (`stubs/resources/js/…/pages/Index.vue`, once captured).
- `/{module-}`: describe the main page.

## Install

```bash
saucebase new my-app --template={COMPOSER_VENDOR}/{module-}
```

Add `--demo` for sample data. The installer prints the account to sign in with.

## Develop

This app builds the template: its code is in `modules/{module-}`, and the app files it replaces are in `modules/{module-}/stubs`.

```bash
php artisan saucebase:template-capture resources/js/pages/Index.vue   # replace an app file
php artisan modules:seed --module={module-} --state=default           # load the demo
php artisan test --compact modules/{module-}/tests
```

Publish `modules/{module-}` as its own Git repository. See [Templates](https://saucebase-dev.github.io/docs/fundamentals/templates).
