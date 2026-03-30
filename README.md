# Laraprints

Track page views, clicks, and custom events across your Laravel application. Stores data asynchronously via queued jobs and ships with a ready-to-use Vue 3 analytics dashboard you can drop into any Inertia or Vue-powered page.

---

## What it does

- **Page view tracking** — Middleware captures every request and stores path, referrer, query params, device type, session ID, and optional user ID.
- **Click tracking** — A lightweight JS composable attaches a global click listener and posts events to a built-in API endpoint.
- **Custom event tracking** — Fire named events from anywhere in your PHP code or from the frontend. Useful for signups, purchases, feature usage, or anything you want to measure.
- **Analytics dashboard** — A self-contained Vue 3 component with date range filtering, trend charts, sortable tables, visitors, events, device breakdowns, period-over-period deltas, and CSV export.
- **Pre-aggregated stats** — A daily aggregation command rolls up raw events into fast summary tables so the dashboard stays snappy as your data grows.
- **Horizon-style authorization** — Access is gated by a `viewLaraprints` gate. Publish a one-file provider, add your email, done.
- **Subdomain / multi-app tracking** — Point any app at the primary app's database via a named connection so all analytics land in one place.
- **Async by design** — All write operations are queued jobs. Nothing blocks the response.
- **Auto-pruning** — `MassPrunable` on all models. Schedule `model:prune` and configure a retention window.

---

## Requirements

| | |
|---|---|
| PHP | 8.1+ |
| Laravel | 10, 11, or 12 |
| Queue | Any real queue driver in production (`redis`, `database`, `sqs`, etc.) |
| Frontend (optional) | Vue 3 — required for the dashboard component and click tracking composable |

> The backend — middleware, API endpoints, models, jobs, and artisan commands — works with any frontend or no frontend at all. The dashboard component and JS composable require Vue 3.

### Queue

A real queue driver is required in production. Jobs are dispatched asynchronously and will not run without a worker.

```bash
# Start a queue worker
php artisan queue:work

# Or use Laravel Horizon for a Redis-backed UI
composer require laravel/horizon && php artisan horizon:install
```

**Local development:** set `QUEUE_CONNECTION=sync` in your `.env` to process jobs immediately without a worker running.

---

## Installation

```bash
composer require chrickell/laraprints
php artisan laraprints:install
```

The install command walks you through everything interactively:

1. Publishes the config file
2. Publishes the authorization provider — **asks for your email and writes it in automatically**
3. Publishes Vue components
4. **Configures middleware** — offers to patch `bootstrap/app.php` automatically (Laravel 11+) or set `auto_register_middleware` in your config
5. Runs migrations — skips gracefully if tables already exist
6. **Asks if you're using Inertia** and prints the exact JS snippet for your setup

After install, there is typically one thing left to do: add the JS snippet to your entry point.

### Install command flags

| Flag | Description |
|---|---|
| `--no-migrate` | Skip running migrations |
| `--no-components` | Skip publishing Vue components (backend-only installs) |
| `--subdomain` | Subdomain mode — publishes config, provider, and components but skips migrations and middleware setup |

```bash
# Backend only
php artisan laraprints:install --no-components

# Subdomain / read-only app
php artisan laraprints:install --subdomain
```

### Verify your install

```bash
php artisan laraprints:check
```

Checks config, all database tables, middleware registration, queue driver, geo and UA parser dependencies, authorization gate, pruning config, and spike notification settings.

---

## Testing & Release Readiness

For package development:

```bash
# Validate package metadata
composer validate --strict

# Run the full backend suite
composer test
```

This repository includes a GitHub Actions matrix (`.github/workflows/tests.yml`) that runs tests across:
- Laravel 10 / Testbench 8 / PHP 8.1 (including a `--prefer-lowest` run)
- Laravel 11 / Testbench 9 / PHP 8.2
- Laravel 12 / Testbench 10 / PHP 8.3

### Are frontend tests required?

Not strictly for this package to be installable, because distribution is Composer-first and the critical install/runtime behavior is in PHP (service provider, routes, middleware, jobs, commands, and migrations).

Frontend tests are still recommended when changing published Vue components or the tracking composable in ways that could regress behavior.

## Page View Tracking

The `TrackPageViews` middleware intercepts incoming requests and dispatches a queued `StorePageView` job. Nothing is written synchronously.

### Register the middleware

**Option A — automatically on all web routes (set during install, or in config):**

```php
// config/laraprints.php
'requests' => [
    'auto_register_middleware' => true,
]
```

**Option B — on specific route groups:**

```php
// routes/web.php
Route::middleware('track.requests')->group(function () {
    Route::get('/', HomeController::class);
    Route::get('/about', AboutController::class);
});
```

**Option C — globally via `bootstrap/app.php` (Laravel 11+):**

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('web', \Chrickell\Laraprints\Http\Middleware\TrackPageViews::class);
})
```

### What gets recorded

| Column | Description |
|---|---|
| `domain` | Hostname (useful when tracking multiple apps into one database) |
| `session_id` | Laravel session ID |
| `visit_id` | UUID persisted in session across requests — groups a browsing session |
| `user_id` | Authenticated user's ID (nullable) |
| `device_type` | `desktop`, `mobile`, or `unknown` |
| `country_code` | Two-letter country code (nullable) |
| `method` | HTTP method |
| `current_path` | Request path |
| `current_params` | URL query parameters as JSON (nullable) |
| `referrer_path` | Referring URL path (nullable) |
| `referrer_params` | Referrer query params as JSON (nullable) |
| `viewed_at` | Timestamp |

### Configuration — `requests` section

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Master toggle |
| `auto_register_middleware` | `false` | Push middleware onto the global stack automatically on boot |
| `methods` | `['GET']` | HTTP methods to track |
| `track_page_views` | `true` | Whether to persist records. `false` runs middleware without storing |
| `excluded_paths` | `['api/*', ...]` | Paths never tracked. Supports `*` wildcards |
| `exclude_assets` | `true` | Skip requests for images, CSS, JS, fonts based on file extension |
| `ignore_bots` | `true` | Skip requests from known bots and crawlers |
| `respect_dnt` | `false` | Skip requests with `DNT: 1` header. Enable for GDPR-friendly deployments |
| `only_authenticated` | `false` | Only track logged-in users |
| `exclude_admins` | `false` | Skip admin users. `true` checks `$user->is_admin`. Pass a callable for custom logic |
| `store_user_id` | `true` | Store the authenticated user's ID |
| `store_ip_address` | `true` | Store the visitor's IP address |
| `store_user_agent` | `true` | Store the raw User-Agent string |
| `store_device_type` | `true` | Detect and store device type (requires `store_user_agent`) |
| `store_referrer` | `true` | Capture and store the referrer URL |
| `store_params` | `true` | Capture and store the current URL's query params |
| `session_key` | `'laraprints_visit_id'` | Session key used to persist the per-visit UUID |

**`exclude_admins` examples:**

```php
'exclude_admins' => true,                                    // checks $user->is_admin
'exclude_admins' => fn ($user) => $user->hasRole('admin'),   // spatie/laravel-permission
'exclude_admins' => fn ($user) => $user->role === 'admin',   // custom attribute
```

---

## Click Tracking

Clicks are tracked from the frontend via a lightweight JavaScript composable. It attaches a global listener, detects interactions with buttons, links, inputs, and Vue `@click` elements, and posts events to a built-in API endpoint.

### Frontend setup

The composable is published to `resources/js/vendor/laraprints/composables/useAnalyticsTracking.js` by `laraprints:install`.

**Basic setup:**

```js
import { setupClickTracking } from '@/vendor/laraprints/composables/useAnalyticsTracking'

setupClickTracking()
```

**With Inertia:**

```js
import { patchClickListeners, setupClickTracking } from '@/vendor/laraprints/composables/useAnalyticsTracking'

createInertiaApp({
    setup({ el, App, props, plugin }) {
        patchClickListeners() // call BEFORE mount to detect Vue @click directives
        const app = createApp({ render: () => h(App, props) }).use(plugin)
        app.mount(el)
        setupClickTracking({ inertia: true })
    }
})
```

Call `patchClickListeners()` before `mount()` so the patch is in place when Vue attaches its event listeners. This makes `@click` directives on plain elements (`div`, `span`, `tr`, etc.) detectable.

### Sharing tracking IDs with Inertia

When using Inertia, share the session/visit IDs from the server so page views and clicks can be correlated:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return array_merge(parent::share($request), [
        'tracking_session_id' => $request->session()->getId(),
        'tracking_visit_id'   => $request->session()->get(
            config('laraprints.requests.session_key', 'laraprints_visit_id')
        ),
    ]);
}
```

### `setupClickTracking` options

| Option | Default | Description |
|---|---|---|
| `inertia` | `false` | Read session/visit IDs from Inertia page props |
| `endpoint` | `'/api/clicks'` | The click tracking endpoint |
| `eventsEndpoint` | `'/api/events'` | The custom events endpoint |
| `sessionId` | — | Pre-seeded session ID (overrides sessionStorage) |
| `visitId` | — | Pre-seeded visit ID (overrides sessionStorage) |
| `axios` | — | Axios instance to use. Falls back to `window.axios`, then `fetch` |

### Configuration — `clicks` section

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Master toggle. `false` also prevents the click route from being registered |
| `route` | `'/api/clicks'` | Endpoint path |
| `only_authenticated` | `false` | Return 401 for unauthenticated submissions |
| `store_user_id` | `true` | Store the authenticated user's ID on each click |

---

## Custom Event Tracking

Track named events with optional metadata from anywhere in your application.

### Server-side (PHP)

```php
use Chrickell\Laraprints\Laraprints;

// Basic
Laraprints::track('signup');

// With metadata
Laraprints::track('checkout_completed', ['plan' => 'pro', 'amount' => 49]);
Laraprints::track('feature_used', ['feature' => 'export']);
```

Session and visit IDs are resolved automatically from the current request session. Safe to call from controllers, listeners, jobs, or commands.

### Frontend (JavaScript)

`setupClickTracking()` returns a `trackEvent` function:

```js
const { trackEvent } = setupClickTracking({ inertia: true })

// Track a custom event
trackEvent('button_clicked', { label: 'Get Started' })
trackEvent('video_played', { title: 'Product Tour' })
```

### Configuration — `events` section

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Master toggle. `false` also prevents the events route from being registered |
| `route` | `'/api/events'` | API endpoint for frontend event payloads |
| `only_authenticated` | `false` | Return 401 for unauthenticated submissions |
| `store_user_id` | `true` | Store the authenticated user's ID on each event |
| `prune_after_days` | `null` | Auto-delete event records older than this many days |

---

## Analytics Dashboard

The dashboard is a **Vue 3 component** — you embed it into an Inertia page or any Vue-mounted view in your own application. The package registers the JSON API endpoints that power it.

### Embedding the component

```vue
<!-- resources/js/Pages/Analytics.vue -->
<script setup>
import AnalyticsDashboard from '@/vendor/laraprints/components/AnalyticsDashboard.vue'
</script>

<template>
  <AnalyticsDashboard />
</template>
```

**Blade with a Vue mount point:**

```blade
<div id="laraprints-app"></div>
@vite(['resources/js/laraprints.js'])
```

```js
// resources/js/laraprints.js
import { createApp } from 'vue'
import AnalyticsDashboard from '@/vendor/laraprints/components/AnalyticsDashboard.vue'
createApp(AnalyticsDashboard).mount('#laraprints-app')
```

### Protecting the route

```php
// routes/web.php
Route::get('/analytics', fn () => inertia('Analytics'))
    ->middleware(['auth', 'can:viewLaraprints'])
    ->name('analytics');
```

### Props

| Prop | Default | Description |
|---|---|---|
| `baseUrl` | `'/laraprints'` | URL prefix for the data API. Match to `dashboard.route_prefix` if changed |

```vue
<AnalyticsDashboard base-url="/my-custom-prefix" />
```

### Dashboard features

**Stat cards** — page views, unique sessions, clicks, pages per visit, mobile percentage — each with a **period-over-period delta** (↑ 12% vs prior period).

**Page Views tab:**
- Trend chart (views over time)
- Sortable top-25 pages table with desktop/mobile split
- Device breakdown (desktop / mobile / unknown)
- Top-15 referrers table

**Clicks tab:**
- Trend chart (clicks over time)
- Top-25 clicked pages
- Top-25 clicked elements with `<tag>`, `.class`, `#id`

**Visitors tab:**
- Paginated visitor sessions with country, device, browser, duration, and page count
- Filter by country, device type, or browser
- Click any session to drill into its full page-view history

**Events tab:**
- Top events ranked by count, with unique session counts and proportion bars
- Shows events tracked via `Laraprints::track()` or `trackEvent()`

**Export:**
- Download Page Views or Sessions as CSV for the selected date range

**Date ranges:** 7 days, 30 days, 90 days, 1 year — all with prior-period comparison.

### Dashboard API endpoints

These are registered automatically when `dashboard.enabled` is `true`. All require the `viewLaraprints` gate.

```
GET /laraprints/stats?start=YYYY-MM-DD&end=YYYY-MM-DD
GET /laraprints/visitors?start=YYYY-MM-DD&end=YYYY-MM-DD&sort=last_seen_at&dir=desc&page=1
GET /laraprints/export?type=page_views&start=YYYY-MM-DD&end=YYYY-MM-DD
GET /laraprints/sessions/{session}
GET /laraprints/visits/{visit}
GET /laraprints/page?path=about
```

### Configuration — `dashboard` section

| Key | Default | Description |
|---|---|---|
| `enabled` | `true` | Master toggle. `false` prevents the API routes from being registered |
| `route_prefix` | `'laraprints'` | URL prefix for the data endpoints |
| `middleware` | `['web']` | Middleware wrapping the routes. The `viewLaraprints` gate is always enforced on top |

---

## Artisan Commands

### `laraprints:install`

Guided installation. Publishes config, provider, and components, asks for your email, configures middleware, and runs migrations.

```bash
php artisan laraprints:install
php artisan laraprints:install --subdomain   # read-only install for subdomain apps
php artisan laraprints:install --no-migrate  # skip migrations
```

### `laraprints:check`

Health check for your installation. Reports on 9 areas:

```bash
php artisan laraprints:check
```

```
  Config published ........................... OK
  Table: page_views .......................... OK
  Table: clicks .............................. OK
  Table: laraprints_daily_stats .............. OK
  Table: laraprints_sessions ................. OK
  Table: laraprints_events ................... OK
  Queue driver (redis) ....................... OK
  Geo driver (stevebauman/location) .......... OK
  UA parser (jenssegers/agent) ............... OK
  Authorization gate (viewLaraprints) ........ OK — custom provider active
  Pruning .................................... CONFIGURED
  Spike notifications ........................ CONFIGURED — fires when page views exceed 1000/day
```

### `laraprints:aggregate-daily`

Rolls up raw page views, clicks, and sessions into pre-aggregated summary tables (`laraprints_daily_stats` and `laraprints_sessions`). This is what makes the dashboard fast as your data grows. Scheduled automatically at 00:05 daily.

```bash
php artisan laraprints:aggregate-daily             # yesterday
php artisan laraprints:aggregate-daily --date=2025-06-15  # backfill a specific date
```

Uses a distributed cache lock to prevent concurrent runs.

### `laraprints:anonymize`

Null out IP addresses and user agent strings on records older than N days — useful for GDPR-friendly deployments.

```bash
php artisan laraprints:anonymize --days=30          # preview
php artisan laraprints:anonymize --days=30 --force  # apply
```

---

## Traffic Spike Notifications

Fire a webhook when daily page views exceed a threshold after the nightly aggregation runs.

```php
'notifications' => [
    'spike_webhook_url' => 'https://hooks.slack.com/services/...',
    'spike_threshold'   => 1000,
],
```

The webhook receives a `POST` with JSON body:

```json
{ "date": "2025-06-15", "page_views": 4821, "threshold": 1000 }
```

---

## Authorization

Dashboard access is controlled by the `viewLaraprints` gate — the same pattern Laravel Horizon uses.

### Publish the provider

`laraprints:install` publishes this automatically (and writes your email in). To publish manually:

```bash
php artisan vendor:publish --tag=laraprints-provider
```

This creates `app/Providers/LaraprintsServiceProvider.php`. The package auto-discovers and registers it — no manual step in `bootstrap/providers.php` required.

```php
class LaraprintsServiceProvider extends LaraprintsApplicationServiceProvider
{
    protected array $emails = [
        'you@example.com',
    ];

    protected function gate(): void
    {
        Gate::define('viewLaraprints', function ($user) {
            return in_array($user->email, $this->emails);
        });
    }
}
```

### Customizing the gate

```php
// Allow anyone in local environment
Gate::define('viewLaraprints', fn ($user) => app()->environment('local'));

// Spatie roles
Gate::define('viewLaraprints', fn ($user) => $user->hasRole('admin'));

// Model attribute
Gate::define('viewLaraprints', fn ($user) => $user->is_admin === true);
```

### Default behavior (before publishing)

- **Local environment** → access granted
- **All other environments** → access denied (403)

---

## Geo & User Agent Parsing

Geo and browser detection are used during daily aggregation (`laraprints:aggregate-daily`) to enrich session records with country, browser, OS, and device type.

### Geo (country from IP)

Install one of the supported drivers:

```bash
# Option A — stevebauman/location (default, HTTP-based lookup)
composer require stevebauman/location

# Option B — MaxMind GeoLite2 (local database, no HTTP calls)
composer require geoip2/geoip2
```

```php
'analytics' => [
    'geo_enabled'      => true,
    'geo_driver'       => 'stevebauman',  // or 'maxmind'
    'maxmind_db_path'  => null,           // path to GeoLite2-Country.mmdb when using maxmind
],
```

### User Agent Parser

```bash
# Option A — jenssegers/agent (default)
composer require jenssegers/agent

# Option B — hisorange/browser-detect
composer require hisorange/browser-detect
```

```php
'analytics' => [
    'ua_parser' => 'jenssegers',  // or 'hisorange'
],
```

Both are optional. If neither is installed, Laraprints falls back to device type detection from the stored `device_type` column.

---

## Subdomain / Multi-App Tracking

Point any app at the primary app's database so all analytics land in one place:

```php
// config/laraprints.php on the subdomain app
'database' => [
    'connection' => 'primary', // named connection in config/database.php
],
```

Use `php artisan laraprints:install --subdomain` on the subdomain app — it skips migrations and middleware setup since the primary app owns those.

---

## Queue Configuration

| Key | Default | Description |
|---|---|---|
| `connection` | `null` | Queue connection name. `null` uses the application default |
| `requests_queue` | `null` | Queue name for page view jobs |
| `clicks_queue` | `null` | Queue name for click jobs |
| `events_queue` | `null` | Queue name for custom event jobs |

```php
'queue' => [
    'connection'      => 'redis',
    'requests_queue'  => 'analytics',
    'clicks_queue'    => 'analytics',
    'events_queue'    => 'analytics',
],
```

---

## Data Pruning

Configure retention windows in `config/laraprints.php`:

```php
'pruning' => [
    'page_views_after_days' => 90,
    'clicks_after_days'     => 90,
],

'events' => [
    'prune_after_days' => 90,
],

'analytics' => [
    'sessions_prune_after_days'    => 365,
    'daily_stats_prune_after_days' => null, // keep forever
],
```

Then schedule `model:prune`:

```php
// routes/console.php (Laravel 11+)
Schedule::command('model:prune')->daily();
```

All values default to `null` (keep forever). Prune manually:

```bash
php artisan model:prune --model="Chrickell\Laraprints\Models\PageView"    # laraprints_page_views
php artisan model:prune --model="Chrickell\Laraprints\Models\Click"       # laraprints_clicks
php artisan model:prune --model="Chrickell\Laraprints\Models\LpEvent"     # laraprints_events
php artisan model:prune --model="Chrickell\Laraprints\Models\Session"     # laraprints_sessions
php artisan model:prune --model="Chrickell\Laraprints\Models\DailyStat"   # laraprints_daily_stats
```

---

## Publishing Assets

Migrations are loaded automatically and do not need to be published. Just run `php artisan migrate`.

| Tag | What it publishes |
|---|---|
| `laraprints-provider` | `app/Providers/LaraprintsServiceProvider.php` |
| `laraprints-config` | `config/laraprints.php` |
| `laraprints-migrations` | `database/migrations/` — only needed if you want to customize them |
| `laraprints-components` | `resources/js/vendor/laraprints/` — Vue components and composable |

```bash
# Publish everything at once
php artisan vendor:publish --provider="Chrickell\Laraprints\LaraPrintServiceProvider"
```

---

## Uninstalling

`composer remove` removes the package from `vendor/` but leaves behind files that were published into your application. Clean those up manually:

**1. Roll back and drop the database tables:**

```bash
php artisan migrate:rollback
```

Or drop the tables directly if you've already run other migrations since install:

```sql
DROP TABLE laraprints_page_views;
DROP TABLE laraprints_clicks;
DROP TABLE laraprints_daily_stats;
DROP TABLE laraprints_sessions;
DROP TABLE laraprints_events;
```

**2. Remove published files:**

```bash
rm config/laraprints.php
rm app/Providers/LaraprintsServiceProvider.php
rm -rf resources/js/vendor/laraprints/
```

If you published migrations manually (`--tag=laraprints-migrations`), remove those too:

```bash
rm database/migrations/*_create_laraprints_*.php
```

**3. Remove the package:**

```bash
composer remove chrickell/laraprints
```

**4. Clean up any manual middleware registration** — if you added `TrackPageViews` to `bootstrap/app.php` or a route group, remove that reference.

---

## License

MIT — see [LICENSE](LICENSE).
# laraprints
