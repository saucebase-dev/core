# My Saucebase App

Built with [Saucebase](https://github.com/saucebase-dev/saucebase), on Laravel and Inertia.

## Start

| Installed with | Open the app |
| --- | --- |
| Herd | Already served at `https://<folder>.test`. Run `npm run dev` for live reload. |
| Native | `composer dev` (app, queue, logs and Vite), then open the URL it prints. |
| Docker | `docker compose up -d`, then open `APP_URL` from `.env`. Logs: `docker compose logs -f`. Stop: `docker compose down`. |

**On Docker, run every `php artisan` and `composer` command inside the container:**
`docker compose exec app php artisan …`. Or use `task artisan -- …`, which adds the prefix for you.

## Sign in

With demo data, sign in as `chef@saucebase.dev`, a full admin. Every demo account uses the
password saved in `.env` as `DEMO_PASSWORD`. The admin panel is at `/admin`.

Reload the demo data with `task db:reset:demo`.

## Modules

```bash
php artisan saucebase:module-install          # pick from the list
php artisan saucebase:module-install billing  # or name them
npm install && npm run build
```

## Tasks

[Task](https://taskfile.dev) runs the common commands. `task --list` shows them all, including each
installed module's own.

## Tests

```bash
php artisan test    # PHPUnit
npm run test:e2e    # Playwright
```

**`npm run test:e2e` resets the database in `.env`.** Run it on an app whose data you can lose.

## Learn more

- [Saucebase](https://github.com/saucebase-dev/saucebase)
- [Modules](https://github.com/orgs/saucebase-dev/repositories)
