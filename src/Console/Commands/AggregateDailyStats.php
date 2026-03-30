<?php

namespace Chrickell\Laraprints\Console\Commands;

use Carbon\Carbon;
use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\DailyStat;
use Chrickell\Laraprints\Models\PageView;
use Chrickell\Laraprints\Models\Session;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AggregateDailyStats extends Command
{
    protected $signature = 'laraprints:aggregate-daily
                            {--date= : YYYY-MM-DD, defaults to yesterday}';

    protected $description = 'Roll up daily stats and upsert session data into pre-aggregated tables';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::yesterday()->toDateString();

        $this->components->info("Aggregating Laraprints stats for {$date}...");

        $lock = Cache::lock("laraprints:aggregate-daily:{$date}", 3600);

        if (! $lock->get()) {
            $this->components->warn("Aggregation for {$date} is already running — skipping.");
            return self::SUCCESS;
        }

        try {
            $this->aggregateDailyStats($date);
            $this->aggregateSessions($date);
            $this->fireSpikeWebhook($date);
            $this->components->info('Done.');
        } finally {
            $lock->release();
        }

        return self::SUCCESS;
    }

    protected function aggregateDailyStats(string $date): void
    {
        $pvStats = PageView::whereDate('created_at', $date)
            ->selectRaw("
                COUNT(*) as page_views,
                COUNT(DISTINCT session_id) as unique_sessions,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile,
                SUM(CASE WHEN device_type NOT IN ('desktop','mobile') THEN 1 ELSE 0 END) as unknown
            ")
            ->first();

        $clickStats = Click::whereDate('created_at', $date)
            ->selectRaw('COUNT(*) as clicks')
            ->first();

        DailyStat::upsert(
            [[
                'date'            => $date,
                'page_views'      => (int) ($pvStats->page_views ?? 0),
                'clicks'          => (int) ($clickStats->clicks ?? 0),
                'unique_sessions' => (int) ($pvStats->unique_sessions ?? 0),
                'desktop'         => (int) ($pvStats->desktop ?? 0),
                'mobile'          => (int) ($pvStats->mobile ?? 0),
                'unknown'         => (int) ($pvStats->unknown ?? 0),
            ]],
            ['date'],
            ['page_views', 'clicks', 'unique_sessions', 'desktop', 'mobile', 'unknown']
        );

        $this->components->twoColumnDetail('Daily stats', '<fg=green;options=bold>DONE</>');
    }

    protected function aggregateSessions(string $date): void
    {
        $pvSessions    = PageView::whereDate('created_at', $date)->distinct()->pluck('session_id');
        $clickSessions = Click::whereDate('created_at', $date)->distinct()->pluck('session_id');

        $activeSessions = $pvSessions->merge($clickSessions)->unique()->filter()->values();

        if ($activeSessions->isEmpty()) {
            $this->components->twoColumnDetail('Sessions', '<fg=yellow;options=bold>NONE (no activity)</>');
            return;
        }

        $uaParser    = $this->resolveUaParser();
        $geoResolver = $this->resolveGeoResolver();

        $bar = $this->output->createProgressBar($activeSessions->count());
        $bar->start();

        foreach ($activeSessions as $sessionId) {
            $this->upsertSession($sessionId, $uaParser, $geoResolver);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->components->twoColumnDetail(
            'Sessions (' . $activeSessions->count() . ')',
            '<fg=green;options=bold>DONE</>'
        );
    }

    protected function upsertSession(string $sessionId, ?callable $uaParser, ?callable $geoResolver): void
    {
        $pageViews = PageView::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['created_at', 'device_type', 'referrer_path', 'current_path', 'ip_address', 'user_agent', 'country_code']);

        $clicks = Click::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['created_at']);

        $allTimestamps = $pageViews->pluck('created_at')
            ->merge($clicks->pluck('created_at'))
            ->filter()
            ->sort();

        $firstSeen = $allTimestamps->first();
        $lastSeen  = $allTimestamps->last();
        $duration  = ($firstSeen && $lastSeen)
            ? (int) $firstSeen->diffInSeconds($lastSeen)
            : null;

        $firstPageView = $pageViews->first();

        $entryPage = $firstPageView ? ltrim($firstPageView->current_path, '/') : null;

        $referrer       = null;
        $firstReferrer  = $pageViews->first(fn ($pv) => ! empty($pv->referrer_path));
        if ($firstReferrer) {
            $host     = parse_url($firstReferrer->referrer_path, PHP_URL_HOST);
            $referrer = $host ?: $firstReferrer->referrer_path;
        }

        $ip = $firstPageView?->ip_address;
        $ua = $firstPageView?->user_agent;

        $browser = null;
        $os      = null;
        $device  = 'desktop';

        if ($ua && $uaParser) {
            [$browser, $os, $device] = $uaParser($ua);
        } elseif ($firstPageView) {
            $device = $firstPageView->device_type === 'mobile' ? 'mobile' : 'desktop';
        }

        $country = $pageViews->first(fn ($pv) => ! empty($pv->country_code))?->country_code;
        if (! $country && $ip && $geoResolver) {
            $country = $geoResolver($ip);
        }

        $existing = Session::where('session_id', $sessionId)->exists();

        $data = [
            'last_seen_at' => $lastSeen,
            'duration'     => $duration,
            'page_views'   => $pageViews->count(),
            'clicks'       => $clicks->count(),
        ];

        if (! $existing) {
            $data['first_seen_at'] = $firstSeen;
            $data['entry_page']    = $entryPage;
            $data['country']       = $country;
            $data['browser']       = $browser;
            $data['os']            = $os;
            $data['device']        = $device;
            $data['referrer']      = $referrer;
        }

        Session::updateOrCreate(['session_id' => $sessionId], $data);
    }

    protected function fireSpikeWebhook(string $date): void
    {
        $webhookUrl = config('laraprints.notifications.spike_webhook_url');
        $threshold  = config('laraprints.notifications.spike_threshold');

        if (! $webhookUrl || ! $threshold) {
            return;
        }

        $stat = DailyStat::where('date', $date)->first();
        $pageViews = (int) ($stat?->page_views ?? 0);

        if ($pageViews < (int) $threshold) {
            return;
        }

        $payload = json_encode([
            'date'       => $date,
            'page_views' => $pageViews,
            'threshold'  => (int) $threshold,
        ]);

        try {
            $ch = curl_init($webhookUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);
            curl_exec($ch);
            curl_close($ch);

            $this->components->twoColumnDetail(
                'Spike webhook',
                "<fg=green;options=bold>FIRED</> — {$pageViews} page views exceeded threshold {$threshold}"
            );
        } catch (\Throwable $e) {
            $this->components->warn("Spike webhook failed: " . $e->getMessage());
        }
    }

    protected function resolveUaParser(): ?callable
    {
        $driver = config('laraprints.analytics.ua_parser', 'jenssegers');

        if ($driver === 'jenssegers') {
            if (! class_exists(\Jenssegers\Agent\Agent::class)) {
                $this->components->warn(
                    'jenssegers/agent is not installed — user agent parsing skipped. Run: composer require jenssegers/agent'
                );
                return null;
            }

            $agent = new \Jenssegers\Agent\Agent();

            return function (string $ua) use ($agent): array {
                $agent->setUserAgent($ua);

                return [
                    $agent->browser() ?: null,
                    $agent->platform() ?: null,
                    $agent->isTablet() ? 'tablet' : ($agent->isMobile() ? 'mobile' : 'desktop'),
                ];
            };
        }

        if ($driver === 'hisorange') {
            if (! class_exists(\hisorange\BrowserDetect\Parser::class)) {
                $this->components->warn(
                    'hisorange/browser-detect is not installed — user agent parsing skipped. Run: composer require hisorange/browser-detect'
                );
                return null;
            }

            return function (string $ua): array {
                $result = (new \hisorange\BrowserDetect\Parser)->parse($ua);

                return [
                    $result->browserName() ?: null,
                    $result->osName() ?: null,
                    $result->isTablet() ? 'tablet' : ($result->isMobile() ? 'mobile' : 'desktop'),
                ];
            };
        }

        return null;
    }

    protected function resolveGeoResolver(): ?callable
    {
        if (! config('laraprints.analytics.geo_enabled', true)) {
            return null;
        }

        $driver = config('laraprints.analytics.geo_driver', 'stevebauman');

        if ($driver === 'stevebauman') {
            if (! class_exists(\Stevebauman\Location\Facades\Location::class)) {
                $this->components->warn(
                    'stevebauman/location is not installed — IP geolocation skipped. Run: composer require stevebauman/location'
                );
                return null;
            }

            return function (string $ip): ?string {
                try {
                    $location = \Stevebauman\Location\Facades\Location::get($ip);
                    return $location?->countryCode ?: null;
                } catch (\Throwable) {
                    return null;
                }
            };
        }

        if ($driver === 'maxmind') {
            if (! class_exists(\GeoIp2\Database\Reader::class)) {
                $this->components->warn(
                    'geoip2/geoip2 is not installed — IP geolocation skipped. Run: composer require geoip2/geoip2'
                );
                return null;
            }

            $dbPath = config(
                'laraprints.analytics.maxmind_db_path',
                storage_path('app/GeoLite2-Country.mmdb')
            );

            try {
                $reader = new \GeoIp2\Database\Reader($dbPath);
            } catch (\Throwable) {
                $this->components->warn(
                    "MaxMind database not found at {$dbPath} — IP geolocation skipped."
                );
                return null;
            }

            return function (string $ip) use ($reader): ?string {
                try {
                    $record = $reader->country($ip);
                    return $record->country->isoCode ?: null;
                } catch (\Throwable) {
                    return null;
                }
            };
        }

        return null;
    }
}
