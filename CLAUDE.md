# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dependencies
composer install

# Run all tests
composer test
# or
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/Path/To/TestTest.php

# Run tests with coverage
./vendor/bin/phpunit --coverage-text
```

## Architecture

This is a **Laravel package** (not a standalone app). It is installed into a host Laravel application via Composer. There is no `artisan`, no `.env`, and no database of its own — everything reads from the host app's runtime state.

### Entry point

`src/CockpitServiceProvider.php` is the only entry point. Laravel auto-discovers it via `extra.laravel.providers` in `composer.json`. It:
- merges `config/cockpit.php` into the host app's config
- registers all six services as singletons in the container
- registers views from `resources/views/` under the `cockpit::` namespace
- wraps `routes/cockpit.php` in a `Route::group` with the configured prefix + middleware
- defines the `viewCockpit` Gate used by the authorize middleware

### Service layer — one class per concern

All data collection and mutation logic lives in `src/Services/`. Controllers are thin wrappers that inject a service, call it, and pass the result to a view.

| Service | Responsibility |
|---|---|
| `RouteInspector` | Collect all app routes via `Route::getRoutes()` |
| `CommandInspector` | Collect Artisan commands via `Artisan::all()`; loads `routes/console.php` for Laravel 11+ compatibility |
| `ScheduleInspector` | Collect schedule events via `Schedule::events()`; loads `routes/console.php` for Laravel 11+ compatibility |
| `EventInspector` | Collect event listeners via `app('events')->getRawListeners()` |
| `LogParser` | Memory-safe log parsing (reads at most `log_max_read_bytes` from the tail); provides `paginate()` and `recentErrors()` |
| `JobManager` | All queue DB operations: read pending/failed jobs, retry (in a transaction), delete, clear, count |

### Request lifecycle

Every Cockpit page is a synchronous Blade render — no background workers, no persistent storage. Data is collected fresh on every request from Laravel's runtime APIs.

### Laravel 11 / 12 compatibility

In Laravel 11+, scheduled tasks and closure commands live in `routes/console.php`, which is **only loaded during console bootstrap** — not during HTTP requests. Both `ScheduleInspector` and `CommandInspector` use a static flag (`$consoleLoaded`) to `require_once` that file exactly once per request when it exists. This is safe because `Schedule::` and `Artisan::command()` calls in that file target container singletons.

### Authorization

`src/Http/Middleware/CockpitAuthorize.php` is always in the middleware stack:
- `local` environment → allow everyone
- any other environment → require auth + `viewCockpit` Gate (checked against `cockpit.allowed_emails`)
- guards against redirect loops when no `login` route is registered

### Config keys

All config lives under the `cockpit` key. Notable keys: `enabled`, `path` (URL prefix), `middleware`, `allowed_emails`, `queue_table`, `failed_jobs_table`, `log_file`, `log_max_read_bytes` (default 20 MB), `command_blocklist`.

### Frontend

No build step. Tailwind CSS and Alpine.js are loaded from CDN in `resources/views/layout.blade.php`. All interactivity is inline Alpine components. `window.cockpitFetch()` (defined in `layout.blade.php`) wraps `fetch` with CSRF headers and is available in every view. A module-compatible version is also exported from `resources/js/cockpit.js` for projects with a build step.
