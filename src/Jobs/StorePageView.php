<?php

namespace Chrickell\Laraprints\Jobs;

use Chrickell\Laraprints\Models\PageView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class StorePageView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $visitId,
        public ?int $userId,
        public string $deviceType,
        public ?string $countryCode,
        public string $method,
        public string $currentPath,
        public ?array $currentParams,
        public ?string $referrerPath,
        public ?array $referrerParams,
        public ?string $domain = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {
        if ($connection = config('laraprints.queue.connection')) {
            $this->connection = $connection;
        }
        if ($queue = config('laraprints.queue.requests_queue')) {
            $this->queue = $queue;
        }
    }

    public function handle(): void
    {
        // Dedup: identical session+visit+path within the same minute is almost certainly
        // a job retry storm, not a genuine revisit. Skip without failing the job.
        $dedupeKey = 'laraprints:pv:' . md5($this->sessionId . ':' . $this->visitId . ':' . $this->currentPath) . ':' . now()->format('YmdHi');
        if (Cache::has($dedupeKey)) {
            return;
        }
        Cache::put($dedupeKey, 1, now()->addMinutes(2));

        $countryCode = $this->countryCode ?? $this->resolveCountry($this->ipAddress);
        $deviceType  = $this->deviceType !== 'unknown'
            ? ($this->resolveDeviceType($this->userAgent) ?? $this->deviceType)
            : $this->deviceType;

        PageView::create([
            'domain'          => $this->domain,
            'ip_address'      => $this->ipAddress,
            'user_agent'      => $this->userAgent,
            'session_id'      => $this->sessionId,
            'visit_id'        => $this->visitId,
            'user_id'         => $this->userId,
            'device_type'     => $deviceType,
            'country_code'    => $countryCode,
            'method'          => $this->method,
            'current_path'    => $this->currentPath,
            'current_params'  => $this->currentParams,
            'referrer_path'   => $this->referrerPath,
            'referrer_params' => $this->referrerParams,
        ]);
    }

    protected function resolveCountry(?string $ip): ?string
    {
        if (! $ip || ! config('laraprints.analytics.geo_enabled', true)) {
            return null;
        }

        $driver = config('laraprints.analytics.geo_driver', 'stevebauman');

        if ($driver === 'stevebauman' && class_exists(\Stevebauman\Location\Facades\Location::class)) {
            try {
                return \Stevebauman\Location\Facades\Location::get($ip)?->countryCode ?: null;
            } catch (\Throwable) {
                return null;
            }
        }

        if ($driver === 'maxmind' && class_exists(\GeoIp2\Database\Reader::class)) {
            $dbPath = config('laraprints.analytics.maxmind_db_path', storage_path('app/GeoLite2-Country.mmdb'));
            try {
                return (new \GeoIp2\Database\Reader($dbPath))->country($ip)->country->isoCode ?: null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    protected function resolveDeviceType(?string $ua): ?string
    {
        if (! $ua) {
            return null;
        }

        $driver = config('laraprints.analytics.ua_parser', 'jenssegers');

        if ($driver === 'jenssegers' && class_exists(\Jenssegers\Agent\Agent::class)) {
            $agent = new \Jenssegers\Agent\Agent();
            $agent->setUserAgent($ua);
            // enum only supports desktop/mobile/unknown — map tablet to mobile
            return $agent->isTablet() ? 'mobile' : ($agent->isMobile() ? 'mobile' : 'desktop');
        }

        if ($driver === 'hisorange' && class_exists(\hisorange\BrowserDetect\Parser::class)) {
            $result = (new \hisorange\BrowserDetect\Parser)->parse($ua);
            return $result->isTablet() ? 'mobile' : ($result->isMobile() ? 'mobile' : 'desktop');
        }

        return null;
    }
}
