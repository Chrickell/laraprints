<?php

use Chrickell\Laraprints\Jobs\StoreClick;
use Chrickell\Laraprints\Jobs\StoreEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

if (config('laraprints.events.enabled', true)) {
    Route::post(config('laraprints.events.route', '/api/events'), function () {
        if (config('laraprints.events.only_authenticated', false) && ! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        request()->validate([
            'session_id'  => 'required|string|max:255',
            'visit_id'    => 'nullable|string|max:255',
            'name'        => 'required|string|max:100',
            'properties'  => 'nullable|array',
        ]);

        StoreEvent::dispatch(
            name:       request()->input('name'),
            properties: request()->input('properties') ?: null,
            sessionId:  request()->input('session_id'),
            visitId:    request()->input('visit_id'),
            userId:     config('laraprints.events.store_user_id', true) && Auth::check()
                ? Auth::id()
                : null,
            domain:     request()->getHost(),
        );

        return response()->json(['success' => true]);
    })->middleware('throttle:60,1');
}

if (config('laraprints.clicks.enabled', true)) {
    Route::post(config('laraprints.clicks.route', '/api/clicks'), function () {
        if (config('laraprints.clicks.only_authenticated', false) && ! Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        request()->validate([
            'session_id' => 'required|string|max:255',
            'visit_id'   => 'required|string|max:255',
            'element'    => 'required|string|max:50',
            'class'      => 'nullable|string|max:255',
            'id'         => 'nullable|string|max:255',
            'style'      => 'nullable|string',
            'path'       => 'required|string|max:255',
        ]);

        StoreClick::dispatch(
            sessionId:    request()->input('session_id'),
            visitId:      request()->input('visit_id'),
            userId:       config('laraprints.clicks.store_user_id', true) && Auth::check()
                ? Auth::id()
                : null,
            element:      request()->input('element'),
            elementClass: request()->input('class'),
            elementId:    request()->input('id'),
            elementStyle: request()->input('style'),
            path:         request()->input('path'),
            domain:       request()->getHost(),
        );

        return response()->json(['success' => true]);
    })->middleware('throttle:60,1');
}
