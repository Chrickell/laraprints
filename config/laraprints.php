<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Request Tracking
    |--------------------------------------------------------------------------
    | Controls the TrackRequests middleware, which intercepts incoming HTTP
    | requests and stores them as PageView records via a queued job.
    |
    | Register the middleware manually on the routes you want to track:
    |
    |   Route::middleware('track.requests')->group(...)
    |
    | Or set `auto_register_middleware` to true and the package will append
    | it to the `web` middleware group automatically on boot.
    |
    | To register manually in Laravel 11+ (bootstrap/app.php), append to the
    | web group so the session is available:
    |
    |   ->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $m) {
    |       $m->appendToGroup('web', \Chrickell\Laraprints\Http\Middleware\TrackPageViews::class);
    |   })
    */
    'requests' => [

        // Master toggle — set to false to disable all request tracking.
        'enabled' => true,

        // Append the middleware to the `web` group automatically on boot.
        // When false (default) you register it manually (see above).
        'auto_register_middleware' => false,

        // HTTP methods to track. Add 'POST', 'PUT', 'DELETE', etc. to capture
        // non-GET requests. 'GET' is the typical choice for page view analytics.
        'methods' => ['GET'],

        // Whether to persist each tracked request as a PageView record.
        // Disable if you only want the middleware for a custom sub-class but
        // don't want data written to the page_views table.
        'track_page_views' => true,

        // Paths that will NOT be tracked. Supports wildcards (*).
        'excluded_paths' => [
            'api/*',
            '_debugbar/*',
            'livewire/*',
            'telescope/*',
            'horizon/*',
        ],

        // When true, requests for static assets (images, CSS, JS, fonts, etc.)
        // are silently skipped based on the file extension in the URL path.
        'exclude_assets' => true,

        // When true, requests from known bots and crawlers are silently ignored.
        'ignore_bots' => true,

        // When true, requests with the DNT (Do Not Track) header set to "1" are
        // silently skipped. Disabled by default — enable for GDPR-friendly deployments.
        'respect_dnt' => false,

        // When true, only authenticated users' requests are tracked.
        // Guest traffic is silently skipped.
        'only_authenticated' => false,

        // Whether to skip tracking for admin users.
        //
        // true  — skips users where $user->is_admin === true
        // false — tracks everyone (default)
        //
        // For custom admin checks, provide a callable instead:
        //   'exclude_admins' => fn ($user) => $user->hasRole('admin'),
        //   'exclude_admins' => fn ($user) => $user->role === 'admin',
        'exclude_admins' => false,

        // Whether to store the authenticated user's ID with each record.
        // Set to false for stricter privacy / anonymised analytics.
        'store_user_id' => true,

        // Whether to store the visitor's IP address.
        // Set to false to avoid storing personally identifiable network data.
        'store_ip_address' => true,

        // Whether to store the raw User-Agent string.
        // Set to false to avoid storing browser fingerprint data.
        'store_user_agent' => true,

        // Whether to detect and store the device type (desktop / mobile / unknown).
        // Requires store_user_agent to be true for accurate detection.
        'store_device_type' => true,

        // Whether to capture and store the referrer path and params.
        'store_referrer' => true,

        // Whether to capture and store the URL query parameters for the
        // current page (stored as JSON in the `current_params` column).
        'store_params' => true,

        // The session key used to persist the per-visit UUID across requests.
        'session_key' => 'laraprints_visit_id',

    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Event Tracking
    |--------------------------------------------------------------------------
    | Track named events from your application code using the Laraprints helper:
    |
    |   \Chrickell\Laraprints\Laraprints::track('checkout_completed', ['plan' => 'pro']);
    |   \Chrickell\Laraprints\Laraprints::track('signup');
    |
    | Events are also trackable from the frontend via the JS composable:
    |
    |   const { trackEvent } = setupClickTracking({ ... })
    |   trackEvent('button_clicked', { label: 'Get Started' })
    */
    'events' => [

        // Master toggle — set to false to disable all event tracking.
        'enabled' => true,

        // The API endpoint that receives event payloads from the frontend.
        'route' => '/api/events',

        // When true, only authenticated users' events are stored.
        'only_authenticated' => false,

        // Whether to store the authenticated user's ID on each event.
        'store_user_id' => true,

        // How many days of event records to keep. null = keep forever.
        'prune_after_days' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Click Tracking
    |--------------------------------------------------------------------------
    */
    'clicks' => [

        // Master toggle — set to false to disable all click tracking.
        'enabled' => true,

        // The API endpoint that receives click events from the frontend.
        // Change this if /api/clicks conflicts with your own routes.
        'route' => '/api/clicks',

        // When true, only authenticated users' clicks are tracked.
        'only_authenticated' => false,

        // Whether to store the authenticated user's ID on each click.
        'store_user_id' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Laraprints Dashboard
    |--------------------------------------------------------------------------
    | Controls the JSON data API consumed by the AnalyticsDashboard Vue
    | component.
    |
    | Access is always gated by the `viewLaraprints` gate, which is defined in
    | your published LaraprintsServiceProvider (app/Providers/LaraprintsServiceProvider.php).
    | Publish it with:
    |
    |   php artisan vendor:publish --tag=laraprints-provider
    |
    | Then add the email addresses of users who should have access, or replace
    | the gate logic entirely (roles, permissions, etc.).
    |
    | The `middleware` option below controls only the surrounding route group
    | (session handling, CSRF, etc.) — not the authorization check itself.
    */
    'dashboard' => [

        // Master toggle — set to false to disable the data API entirely.
        'enabled' => true,

        // Route prefix. Defaults to: GET /laraprints/page-views, GET /laraprints/clicks
        'route_prefix' => 'laraprints',

        // Middleware wrapping the dashboard routes. The `viewLaraprints` gate is
        // always enforced on top of whatever you put here.
        'middleware' => ['web'],

    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    | By default the package uses your application's default database
    | connection. Set `connection` to a named connection from your
    | config/database.php to store analytics data elsewhere — useful when
    | a subdomain app (e.g. admin.example.com) needs to write to the primary
    | application's database instead of its own.
    |
    | Note: run migrations from whichever app owns that connection. The
    | admin app should set `'connection' => 'primary'` (or whatever you
    | name it) and skip running laraprints migrations there.
    */
    'database' => [
        'connection' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    | Both StorePageView and StoreClick are queued jobs. Set to null to use
    | the application's default connection and queue. You can route each job
    | type to a different queue if needed.
    */
    'queue' => [

        // Connection name (e.g. 'redis', 'database', 'sqs'). null = default.
        'connection' => null,

        // Queue name for request / page-view jobs. null = default queue.
        'requests_queue' => null,

        // Queue name for click jobs. null = default queue.
        'clicks_queue' => null,

        // Queue name for custom event jobs. null = default queue.
        'events_queue' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Data Pruning
    |--------------------------------------------------------------------------
    | Automatically delete old records to keep the tables lean. Set to null
    | to disable pruning. Pruning is triggered by the Laravel model:prune
    | command — add it to your scheduler:
    |
    |   $schedule->command('model:prune')->daily();
    */
    'pruning' => [

        // Delete page_views records older than this many days. null = keep forever.
        'page_views_after_days' => null,

        // Delete clicks records older than this many days. null = keep forever.
        'clicks_after_days' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    | Laraprints can fire a webhook when a traffic spike is detected after the
    | daily aggregation runs. Set a URL and a threshold to enable.
    |
    | The webhook receives a POST with JSON body:
    |   { "date": "2026-03-29", "page_views": 4821, "threshold": 1000 }
    */
    'notifications' => [

        // Webhook URL to POST to when a spike is detected. null = disabled.
        'spike_webhook_url' => null,

        // Page views per day that triggers the webhook. null = disabled.
        'spike_threshold' => null,

    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Aggregation
    |--------------------------------------------------------------------------
    | Settings for the laraprints:aggregate-daily command, which rolls up raw
    | events into pre-aggregated tables (laraprints_daily_stats and
    | laraprints_sessions) for fast dashboard queries.
    |
    | Schedule the command automatically (registered in the service provider):
    |   php artisan laraprints:aggregate-daily          (yesterday)
    |   php artisan laraprints:aggregate-daily --date=2026-03-25  (backfill)
    */
    'analytics' => [

        // Whether to resolve country from IP address. Requires a geo driver.
        'geo_enabled' => true,

        // Driver: 'stevebauman' (stevebauman/location) or 'maxmind' (geoip2/geoip2).
        'geo_driver' => 'stevebauman',

        // Path to MaxMind GeoLite2-Country.mmdb (only used when geo_driver = 'maxmind').
        'maxmind_db_path' => null,

        // User agent parser: 'jenssegers' (jenssegers/agent) or 'hisorange' (hisorange/browser-detect).
        'ua_parser' => 'jenssegers',

        // How many days of session data to keep. null = keep forever.
        'sessions_prune_after_days' => null,

        // How many days of daily stats to keep. null = keep forever.
        'daily_stats_prune_after_days' => null,

    ],

];
