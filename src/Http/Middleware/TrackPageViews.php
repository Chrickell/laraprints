<?php

namespace Chrickell\Laraprints\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Chrickell\Laraprints\Jobs\StorePageView;

class TrackPageViews
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldTrack($request)) {
            $this->track($request);
        }

        return $next($request);
    }

    protected function shouldTrack(Request $request): bool
    {
        if (! config('laraprints.requests.enabled', true)) {
            return false;
        }

        if ($this->isAssetRequest($request)) {
            return false;
        }

        $allowedMethods = array_map('strtoupper', config('laraprints.requests.methods', ['GET']));
        if (! in_array(strtoupper($request->method()), $allowedMethods, true)) {
            return false;
        }

        if (config('laraprints.requests.only_authenticated', false) && ! auth()->check()) {
            return false;
        }

        if (auth()->check() && $this->isAdmin(auth()->user())) {
            return false;
        }

        if (config('laraprints.requests.ignore_bots', true) && $this->isBot($request->userAgent())) {
            return false;
        }

        if (config('laraprints.requests.respect_dnt', false) && $request->headers->get('DNT') === '1') {
            return false;
        }

        foreach (config('laraprints.requests.excluded_paths', ['api/*']) as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    protected function track(Request $request): void
    {
        if (! config('laraprints.requests.track_page_views', true)) {
            return;
        }

        $sessionId = $request->session()->getId();
        $visitId   = $this->getOrCreateVisitId($request);

        $parsedCurrent = $this->parsePath($request->fullUrl());

        $referrerPath   = null;
        $referrerParams = null;
        if (config('laraprints.requests.store_referrer', true) && $request->headers->get('referer')) {
            $parsed         = $this->parsePath($request->headers->get('referer'));
            $referrerPath   = $parsed['path'];
            $referrerParams = $parsed['params'];
        }

        $storeIp        = config('laraprints.requests.store_ip_address', true);
        $storeUa        = config('laraprints.requests.store_user_agent', true);
        $storeDevice    = config('laraprints.requests.store_device_type', true);

        StorePageView::dispatch(
            sessionId:      $sessionId,
            visitId:        $visitId,
            userId:         config('laraprints.requests.store_user_id', true) && auth()->check()
                                ? auth()->id()
                                : null,
            deviceType:     $storeDevice ? $this->detectDeviceType($request->userAgent()) : 'unknown',
            countryCode:    null,
            method:         $request->method(),
            currentPath:    $parsedCurrent['path'],
            currentParams:  config('laraprints.requests.store_params', true)
                                ? $parsedCurrent['params']
                                : null,
            referrerPath:   $referrerPath,
            referrerParams: $referrerParams,
            domain:         $request->getHost(),
            ipAddress:      $storeIp ? $request->ip() : null,
            userAgent:      $storeUa ? $request->userAgent() : null,
        );
    }

    protected function isAdmin($user): bool
    {
        $rule = config('laraprints.requests.exclude_admins', false);

        if ($rule === false) {
            return false;
        }

        if (is_callable($rule)) {
            return (bool) $rule($user);
        }

        // Default boolean true — check common is_admin attribute
        return (bool) ($user->is_admin ?? false);
    }

    protected function getOrCreateVisitId(Request $request): string
    {
        $key = config('laraprints.requests.session_key', 'laraprints_visit_id');

        if (! $request->session()->has($key)) {
            $request->session()->put($key, (string) Str::uuid());
        }

        return $request->session()->get($key);
    }

    protected function parsePath(string $url): array
    {
        $parsed = parse_url($url);
        $path   = ltrim($parsed['path'] ?? '/', '/') ?: '/';

        $params = null;
        if (! empty($parsed['query'])) {
            parse_str($parsed['query'], $params);
        }

        return ['path' => $path, 'params' => $params ?: null];
    }

    protected function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'unknown';
        }

        return preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)
            ? 'mobile'
            : 'desktop';
    }

    protected function isAssetRequest(Request $request): bool
    {
        if (! config('laraprints.requests.exclude_assets', true)) {
            return false;
        }

        return (bool) preg_match(
            '/\.(css|js|map|ico|png|jpg|jpeg|gif|svg|webp|avif|woff|woff2|ttf|eot|otf|pdf|zip|mp4|mp3|webm|avi|mov)$/i',
            $request->path()
        );
    }

    protected function isBot(?string $userAgent): bool
    {
        if (! $userAgent) {
            return false;
        }

        return (bool) preg_match(
            '/bot|crawl|slurp|spider|mediapartners|google|baidu|bing|msn|duckduckbot|teoma|yandex|facebookexternalhit|ia_archiver|semrush|ahrefsbot|mj12bot/i',
            $userAgent
        );
    }
}
