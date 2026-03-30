<?php

namespace Chrickell\Laraprints\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class CheckInstall extends Command
{
    protected $signature = 'laraprints:check';

    protected $description = 'Check the Laraprints installation and configuration';

    public function handle(): int
    {
        $this->components->info('Laraprints Health Check');
        $this->newLine();

        $failed = 0;

        $failed += $this->checkConfig();
        $failed += $this->checkDatabase();
        $failed += $this->checkMiddleware();
        $failed += $this->checkQueue();
        $failed += $this->checkGeo();
        $failed += $this->checkUaParser();
        $failed += $this->checkGate();
        $failed += $this->checkPruning();
        $this->checkNotifications();

        $this->newLine();

        if ($failed === 0) {
            $this->components->info('All checks passed.');
        } else {
            $this->components->warn("{$failed} check(s) need attention — see warnings above.");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function checkConfig(): int
    {
        $published = file_exists(config_path('laraprints.php'));
        $this->check('Config published', $published, 'Run: php artisan vendor:publish --tag=laraprints-config');

        return $published ? 0 : 1;
    }

    protected function checkDatabase(): int
    {
        $failed  = 0;
        $conn    = config('laraprints.database.connection');
        $connLabel = $conn ?? '(default)';

        $tables = ['laraprints_page_views', 'laraprints_clicks', 'laraprints_daily_stats', 'laraprints_sessions', 'laraprints_events'];

        foreach ($tables as $table) {
            try {
                $exists = Schema::connection($conn)->hasTable($table);
                $this->check("Table: {$table}", $exists, "Run: php artisan migrate", "connection {$connLabel}");
                if (! $exists) {
                    $failed++;
                }
            } catch (\Throwable $e) {
                $this->checkFail("Table: {$table}", "Cannot connect to database ({$connLabel}): " . $e->getMessage());
                $failed++;
            }
        }

        return $failed;
    }

    protected function checkMiddleware(): int
    {
        $enabled  = config('laraprints.requests.enabled', true);
        $autoReg  = config('laraprints.requests.auto_register_middleware', false);

        if (! $enabled) {
            $this->warn("  <fg=yellow>SKIP</>  Request tracking is disabled (requests.enabled = false)");
            return 0;
        }

        if ($autoReg) {
            $this->check('Middleware auto-registration', true, '', 'auto_register_middleware = true');
        } else {
            $this->components->twoColumnDetail(
                '  Middleware registration',
                '<fg=cyan>MANUAL</> — add track.requests to your routes or set auto_register_middleware = true'
            );
        }

        return 0;
    }

    protected function checkQueue(): int
    {
        $connection = config('laraprints.queue.connection');
        $label      = $connection ?? config('queue.default', 'sync');

        $isSafe = $label !== 'sync';
        $this->check(
            "Queue driver ({$label})",
            $isSafe,
            'Using sync driver — jobs run inline and may slow requests. Set queue.connection in config/laraprints.php'
        );

        return $isSafe ? 0 : 1;
    }

    protected function checkGeo(): int
    {
        if (! config('laraprints.analytics.geo_enabled', true)) {
            $this->components->twoColumnDetail('  Geo resolution', '<fg=gray>DISABLED</>');
            return 0;
        }

        $driver = config('laraprints.analytics.geo_driver', 'stevebauman');

        if ($driver === 'stevebauman') {
            $installed = class_exists(\Stevebauman\Location\Facades\Location::class);
            $this->check(
                'Geo driver (stevebauman/location)',
                $installed,
                'Run: composer require stevebauman/location'
            );
            return $installed ? 0 : 1;
        }

        if ($driver === 'maxmind') {
            $installed = class_exists(\GeoIp2\Database\Reader::class);
            $this->check('Geo driver (geoip2/geoip2)', $installed, 'Run: composer require geoip2/geoip2');
            if (! $installed) return 1;

            $dbPath = config('laraprints.analytics.maxmind_db_path', storage_path('app/GeoLite2-Country.mmdb'));
            $dbExists = file_exists($dbPath);
            $this->check("MaxMind DB ({$dbPath})", $dbExists, 'Download GeoLite2-Country.mmdb from maxmind.com');
            return $dbExists ? 0 : 1;
        }

        $this->checkFail('Geo driver', "Unknown driver '{$driver}'. Valid values: stevebauman, maxmind");
        return 1;
    }

    protected function checkUaParser(): int
    {
        $driver = config('laraprints.analytics.ua_parser', 'jenssegers');

        if ($driver === 'jenssegers') {
            $installed = class_exists(\Jenssegers\Agent\Agent::class);
            $this->check('UA parser (jenssegers/agent)', $installed, 'Run: composer require jenssegers/agent');
            return $installed ? 0 : 1;
        }

        if ($driver === 'hisorange') {
            $installed = class_exists(\hisorange\BrowserDetect\Parser::class);
            $this->check('UA parser (hisorange/browser-detect)', $installed, 'Run: composer require hisorange/browser-detect');
            return $installed ? 0 : 1;
        }

        $this->checkFail('UA parser', "Unknown driver '{$driver}'. Valid values: jenssegers, hisorange");
        return 1;
    }

    protected function checkGate(): int
    {
        $providerPublished = class_exists(\App\Providers\LaraprintsServiceProvider::class);
        $gateCustomised    = $providerPublished && Gate::has('viewLaraprints');

        if ($gateCustomised) {
            $this->check('Authorization gate (viewLaraprints)', true, '', 'custom provider active');
            return 0;
        }

        $isLocal = app()->environment('local');
        if ($isLocal) {
            $this->components->twoColumnDetail(
                '  Authorization gate',
                '<fg=yellow>FALLBACK</> — local env only. Publish provider for production: php artisan vendor:publish --tag=laraprints-provider'
            );
            return 0;
        }

        $this->checkFail('Authorization gate', 'Fallback gate only allows local access. Publish the service provider and configure access.');
        return 1;
    }

    protected function checkPruning(): int
    {
        $pvDays    = config('laraprints.pruning.page_views_after_days');
        $clickDays = config('laraprints.pruning.clicks_after_days');
        $sessDays  = config('laraprints.analytics.sessions_prune_after_days');
        $statDays  = config('laraprints.analytics.daily_stats_prune_after_days');

        if ($pvDays || $clickDays || $sessDays || $statDays) {
            $this->components->twoColumnDetail('  Pruning', '<fg=green;options=bold>CONFIGURED</>');
        } else {
            $this->components->twoColumnDetail(
                '  Pruning',
                '<fg=yellow>NOT SET</> — records will grow indefinitely. Set pruning.*_after_days in config/laraprints.php'
            );
        }

        return 0;
    }

    protected function checkNotifications(): void
    {
        $url       = config('laraprints.notifications.spike_webhook_url');
        $threshold = config('laraprints.notifications.spike_threshold');

        if ($url && $threshold) {
            $this->components->twoColumnDetail(
                '  Spike notifications',
                "<fg=green;options=bold>CONFIGURED</> — fires when page views exceed {$threshold}/day"
            );
        } else {
            $this->components->twoColumnDetail(
                '  Spike notifications',
                '<fg=gray>NOT SET</> — set notifications.spike_webhook_url + spike_threshold to enable'
            );
        }
    }

    protected function check(string $label, bool $ok, string $fix = '', string $note = ''): void
    {
        $status = $ok
            ? '<fg=green;options=bold>OK</>'
            : '<fg=red;options=bold>FAIL</>';

        $detail = $status;
        if (! $ok && $fix) {
            $detail .= " — {$fix}";
        } elseif ($ok && $note) {
            $detail .= " — {$note}";
        }

        $this->components->twoColumnDetail("  {$label}", $detail);
    }

    protected function checkFail(string $label, string $message): void
    {
        $this->components->twoColumnDetail("  {$label}", "<fg=red;options=bold>FAIL</> — {$message}");
    }
}
