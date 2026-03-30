<?php

namespace Chrickell\Laraprints;

use Chrickell\Laraprints\Jobs\StoreEvent;
use Illuminate\Support\Facades\Auth;

class Laraprints
{
    /**
     * Track a named event, optionally with arbitrary properties.
     *
     * Can be called from a controller, job, listener, or anywhere in your app:
     *
     *   use Chrickell\Laraprints\Laraprints;
     *
     *   Laraprints::track('checkout_completed', ['plan' => 'pro', 'amount' => 99]);
     *   Laraprints::track('signup');
     *
     * Session / visit IDs are resolved automatically from the current request
     * session. When called outside an HTTP context (queues, CLI), pass them
     * explicitly via $sessionId and $visitId.
     */
    public static function track(
        string  $name,
        array   $properties = [],
        ?string $sessionId  = null,
        ?string $visitId    = null,
    ): void {
        if (! config('laraprints.events.enabled', true)) {
            return;
        }

        $request = app('request');

        if ($sessionId === null) {
            try {
                $sessionId = $request->session()->getId();
            } catch (\Throwable) {
                $sessionId = null;
            }
        }

        if ($visitId === null) {
            try {
                $key     = config('laraprints.requests.session_key', 'laraprints_visit_id');
                $visitId = $request->session()->get($key);
            } catch (\Throwable) {
                $visitId = null;
            }
        }

        $userId = null;
        if (config('laraprints.events.store_user_id', true)) {
            try {
                $userId = Auth::id();
            } catch (\Throwable) {
                $userId = null;
            }
        }

        $domain = null;
        try {
            $domain = $request->getHost();
        } catch (\Throwable) {}

        StoreEvent::dispatch(
            name:       $name,
            properties: $properties ?: null,
            sessionId:  $sessionId,
            visitId:    $visitId,
            userId:     $userId,
            domain:     $domain,
        );
    }
}
