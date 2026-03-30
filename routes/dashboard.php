<?php

use Chrickell\Laraprints\Http\Controllers\DashboardController;
use Chrickell\Laraprints\Http\Controllers\LaraprintsController;
use Illuminate\Support\Facades\Route;

$prefix     = config('laraprints.dashboard.route_prefix', 'laraprints');
$middleware = config('laraprints.dashboard.middleware', ['web']);

Route::prefix($prefix)
    ->middleware(array_merge($middleware, ['can:viewLaraprints']))
    ->group(function () {
        // New consolidated endpoints
        Route::get('stats',            [DashboardController::class, 'stats'])->name('laraprints.stats');
        Route::get('visitors',         [DashboardController::class, 'visitors'])->name('laraprints.visitors');
        Route::get('export',           [DashboardController::class, 'export'])->name('laraprints.export');
        Route::get('sessions/{session}', [DashboardController::class, 'session'])->name('laraprints.session');
        Route::get('visits/{visit}',   [DashboardController::class, 'visit'])->name('laraprints.visit');
        Route::get('page',             [DashboardController::class, 'page'])->name('laraprints.page');

        // Legacy endpoints — kept for backwards compatibility, deprecated
        Route::get('page-views', [LaraprintsController::class, 'pageViews'])->name('laraprints.page-views');
        Route::get('clicks',     [LaraprintsController::class, 'clicks'])->name('laraprints.clicks');
    });
