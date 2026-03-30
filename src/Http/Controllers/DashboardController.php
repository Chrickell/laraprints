<?php

namespace Chrickell\Laraprints\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\DailyStat;
use Chrickell\Laraprints\Models\LpEvent;
use Chrickell\Laraprints\Models\PageView;
use Chrickell\Laraprints\Models\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function session(Request $request, string $sessionId): JsonResponse
    {
        $session = Session::where('session_id', $sessionId)->first();

        $pageViews = PageView::where('session_id', $sessionId)
            ->orderBy('viewed_at')
            ->get();

        $clicks = Click::where('session_id', $sessionId)
            ->orderBy('clicked_at')
            ->get();

        $firstPv = $pageViews->first();

        if (! $session) {
            $allTs   = $pageViews->pluck('viewed_at')
                ->merge($clicks->pluck('clicked_at'))
                ->filter()->sort()->values();
            $session = (object) [
                'session_id'    => $sessionId,
                'country'       => $firstPv?->country_code,
                'browser'       => null,
                'os'            => null,
                'device'        => $firstPv?->device_type,
                'entry_page'    => $firstPv?->current_path,
                'referrer'      => $firstPv?->referrer_path,
                'page_views'    => $pageViews->count(),
                'clicks'        => $clicks->count(),
                'duration'      => $allTs->count() > 1 ? (int) $allTs->first()->diffInSeconds($allTs->last()) : null,
                'first_seen_at' => $allTs->first()?->toIso8601String(),
                'last_seen_at'  => $allTs->last()?->toIso8601String(),
                '_live'         => true,
            ];
        }

        $isModel = $session instanceof Session;

        $visits = $pageViews->groupBy('visit_id')->map(function ($pvs, $visitId) use ($clicks) {
            $sorted      = $pvs->sortBy('viewed_at');
            $visitClicks = $clicks->where('visit_id', $visitId);
            return [
                'visit_id'   => $visitId,
                'pages'      => $pvs->count(),
                'clicks'     => $visitClicks->count(),
                'entry_page' => $sorted->first()?->current_path,
                'first_at'   => $sorted->first()?->viewed_at?->toIso8601String(),
                'last_at'    => $sorted->last()?->viewed_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'session' => [
                'session_id'    => $session->session_id,
                'country'       => $session->country,
                'browser'       => $session->browser,
                'os'            => $session->os,
                'device'        => $session->device,
                'entry_page'    => $session->entry_page,
                'referrer'      => $session->referrer,
                'page_views'    => $session->page_views,
                'clicks'        => $session->clicks,
                'duration'      => $session->duration,
                'first_seen_at' => $isModel ? $session->first_seen_at?->toIso8601String() : $session->first_seen_at,
                'last_seen_at'  => $isModel ? $session->last_seen_at?->toIso8601String() : $session->last_seen_at,
                'ip_address'    => $firstPv?->ip_address,
                'user_agent'    => $firstPv?->user_agent,
                'user_id'       => $firstPv?->user_id,
            ],
            'visits'     => $visits,
            'page_views' => $pageViews->map(fn ($pv) => [
                'id'           => $pv->id,
                'visit_id'     => $pv->visit_id,
                'path'         => $pv->current_path,
                'device_type'  => $pv->device_type,
                'ip_address'   => $pv->ip_address,
                'user_agent'   => $pv->user_agent,
                'country_code' => $pv->country_code,
                'referrer_path' => $pv->referrer_path,
                'viewed_at'    => $pv->viewed_at?->toIso8601String() ?? $pv->created_at?->toIso8601String(),
            ])->values(),
            'clicks' => $clicks->map(fn ($c) => [
                'id'            => $c->id,
                'visit_id'      => $c->visit_id,
                'element'       => $c->element,
                'element_id'    => $c->element_id,
                'element_class' => $c->element_class,
                'path'          => $c->path,
                'clicked_at'    => $c->clicked_at?->toIso8601String() ?? $c->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function visit(Request $request, string $visitId): JsonResponse
    {
        $pageViews = PageView::where('visit_id', $visitId)
            ->orderBy('viewed_at')
            ->get();

        $clicks = Click::where('visit_id', $visitId)
            ->orderBy('clicked_at')
            ->get();

        $firstPv   = $pageViews->first();
        $sessionId = $firstPv?->session_id;

        $session = $sessionId ? Session::where('session_id', $sessionId)->first() : null;

        $allTs = $pageViews->pluck('viewed_at')
            ->merge($clicks->pluck('clicked_at'))
            ->filter()->sort()->values();

        return response()->json([
            'visit' => [
                'visit_id'     => $visitId,
                'session_id'   => $sessionId,
                'user_id'      => $firstPv?->user_id,
                'device_type'  => $firstPv?->device_type,
                'ip_address'   => $firstPv?->ip_address,
                'user_agent'   => $firstPv?->user_agent,
                'country_code' => $firstPv?->country_code,
                'entry_page'   => $firstPv?->current_path,
                'referrer'     => $firstPv?->referrer_path,
                'page_views'   => $pageViews->count(),
                'clicks'       => $clicks->count(),
                'duration'     => $allTs->count() > 1 ? (int) $allTs->first()->diffInSeconds($allTs->last()) : null,
                'first_at'     => $allTs->first()?->toIso8601String(),
                'last_at'      => $allTs->last()?->toIso8601String(),
            ],
            'session' => $session ? [
                'session_id'    => $session->session_id,
                'country'       => $session->country,
                'browser'       => $session->browser,
                'os'            => $session->os,
                'device'        => $session->device,
                'page_views'    => $session->page_views,
                'clicks'        => $session->clicks,
                'first_seen_at' => $session->first_seen_at?->toIso8601String(),
                'last_seen_at'  => $session->last_seen_at?->toIso8601String(),
            ] : null,
            'page_views' => $pageViews->map(fn ($pv) => [
                'id'            => $pv->id,
                'path'          => $pv->current_path,
                'device_type'   => $pv->device_type,
                'ip_address'    => $pv->ip_address,
                'user_agent'    => $pv->user_agent,
                'country_code'  => $pv->country_code,
                'referrer_path' => $pv->referrer_path,
                'viewed_at'     => $pv->viewed_at?->toIso8601String() ?? $pv->created_at?->toIso8601String(),
            ])->values(),
            'clicks' => $clicks->map(fn ($c) => [
                'id'            => $c->id,
                'element'       => $c->element,
                'element_id'    => $c->element_id,
                'element_class' => $c->element_class,
                'path'          => $c->path,
                'clicked_at'    => $c->clicked_at?->toIso8601String() ?? $c->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function page(Request $request): JsonResponse
    {
        $request->validate([
            'path'  => 'required|string',
            'start' => 'nullable|date',
            'end'   => 'nullable|date',
        ]);

        $path  = ltrim($request->get('path'), '/');
        $start = Carbon::parse($request->get('start', now()->subDays(29)->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->get('end', now()->toDateString()))->endOfDay();

        $pageViews = PageView::whereBetween('created_at', [$start, $end])
            ->where('current_path', $path)
            ->orderByDesc('viewed_at')
            ->limit(200)
            ->get();

        $clicks = Click::whereBetween('created_at', [$start, $end])
            ->where('path', $path)
            ->orderByDesc('clicked_at')
            ->limit(200)
            ->get();

        $sessionIds = $pageViews->pluck('session_id')->unique()->filter()->values();
        $sessions   = Session::whereIn('session_id', $sessionIds)->get()->keyBy('session_id');

        $sessionSummaries = $sessionIds->map(function ($sid) use ($pageViews, $clicks, $sessions) {
            $pvs     = $pageViews->where('session_id', $sid);
            $cls     = $clicks->where('session_id', $sid);
            $session = $sessions->get($sid);
            $firstPv = $pvs->sortBy('viewed_at')->first();
            return [
                'session_id'     => $sid,
                'country'        => $session?->country ?? $firstPv?->country_code,
                'browser'        => $session?->browser,
                'os'             => $session?->os,
                'device'         => $session?->device ?? $firstPv?->device_type,
                'ip_address'     => $firstPv?->ip_address,
                'user_agent'     => $firstPv?->user_agent,
                'views_on_page'  => $pvs->count(),
                'clicks_on_page' => $cls->count(),
                'first_at'       => $pvs->min(fn ($p) => $p->viewed_at)?->toIso8601String(),
                'last_at'        => $pvs->max(fn ($p) => $p->viewed_at)?->toIso8601String(),
            ];
        })->values();

        $stats = [
            'total_views'     => $pageViews->count(),
            'unique_sessions' => $sessionIds->count(),
            'total_clicks'    => $clicks->count(),
            'desktop'         => $pageViews->where('device_type', 'desktop')->count(),
            'mobile'          => $pageViews->where('device_type', 'mobile')->count(),
        ];

        return response()->json([
            'path'       => '/' . $path,
            'stats'      => $stats,
            'sessions'   => $sessionSummaries,
            'page_views' => $pageViews->map(fn ($pv) => [
                'id'            => $pv->id,
                'session_id'    => $pv->session_id,
                'visit_id'      => $pv->visit_id,
                'user_id'       => $pv->user_id,
                'device_type'   => $pv->device_type,
                'ip_address'    => $pv->ip_address,
                'user_agent'    => $pv->user_agent,
                'country_code'  => $pv->country_code,
                'referrer_path' => $pv->referrer_path,
                'viewed_at'     => $pv->viewed_at?->toIso8601String() ?? $pv->created_at?->toIso8601String(),
            ])->values(),
            'clicks' => $clicks->map(fn ($c) => [
                'id'            => $c->id,
                'session_id'    => $c->session_id,
                'visit_id'      => $c->visit_id,
                'element'       => $c->element,
                'element_id'    => $c->element_id,
                'element_class' => $c->element_class,
                'clicked_at'    => $c->clicked_at?->toIso8601String() ?? $c->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }


    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'nullable|date',
            'end'   => 'nullable|date',
        ]);

        $start     = Carbon::parse($request->get('start', now()->subDays(29)->toDateString()))->startOfDay();
        $end       = Carbon::parse($request->get('end', now()->toDateString()))->endOfDay();
        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        // by_date — pre-aggregated, zero-filled for the full range
        $statsRows  = DailyStat::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->orderBy('date')
            ->get(['date', 'page_views', 'clicks']);

        $statsByDate  = $statsRows->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());
        $coveredDates = $statsByDate->keys()->all();

        $allDates     = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn ($d) => $d->toDateString())
            ->all();
        $missingDates = array_values(array_diff($allDates, $coveredDates));

        // For dates not yet aggregated, supplement with live queries
        if (! empty($missingDates)) {
            $livePv = PageView::whereBetween('created_at', [$start, $end])
                ->whereIn(DB::raw('DATE(created_at)'), $missingDates)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as page_views')
                ->groupByRaw('DATE(created_at)')
                ->get()
                ->keyBy('date');

            $liveClicks = Click::whereBetween('created_at', [$start, $end])
                ->whereIn(DB::raw('DATE(created_at)'), $missingDates)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as clicks')
                ->groupByRaw('DATE(created_at)')
                ->get()
                ->keyBy('date');

            foreach ($missingDates as $date) {
                $statsByDate[$date] = (object) [
                    'date'       => $date,
                    'page_views' => (int) ($livePv->get($date)?->page_views ?? 0),
                    'clicks'     => (int) ($liveClicks->get($date)?->clicks ?? 0),
                ];
            }
        }

        $byDate = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $day) {
            $key = $day->toDateString();
            $row = $statsByDate->get($key);
            $byDate[] = [
                'date'       => $key,
                'page_views' => $row ? (int) $row->page_views : 0,
                'clicks'     => $row ? (int) $row->clicks : 0,
            ];
        }

        // totals — pre-aggregated rows + live data for any missing dates
        $totalsRow = DailyStat::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->selectRaw('
                SUM(page_views)      as page_views,
                SUM(clicks)          as clicks,
                SUM(unique_sessions) as unique_sessions,
                SUM(desktop)         as desktop,
                SUM(mobile)          as mobile,
                SUM(unknown)         as unknown
            ')
            ->first();

        $totals = [
            'page_views'      => (int) ($totalsRow->page_views ?? 0),
            'unique_sessions' => (int) ($totalsRow->unique_sessions ?? 0),
            'clicks'          => (int) ($totalsRow->clicks ?? 0),
            'mobile'          => (int) ($totalsRow->mobile ?? 0),
            'desktop'         => (int) ($totalsRow->desktop ?? 0),
            'unknown'         => (int) ($totalsRow->unknown ?? 0),
        ];

        if (! empty($missingDates)) {
            $liveTotals = PageView::whereBetween('created_at', [$start, $end])
                ->whereIn(DB::raw('DATE(created_at)'), $missingDates)
                ->selectRaw("
                    COUNT(*) as page_views,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                    SUM(CASE WHEN device_type = 'mobile'  THEN 1 ELSE 0 END) as mobile,
                    SUM(CASE WHEN device_type NOT IN ('desktop','mobile') THEN 1 ELSE 0 END) as unknown
                ")
                ->first();

            $liveClickTotals = Click::whereBetween('created_at', [$start, $end])
                ->whereIn(DB::raw('DATE(created_at)'), $missingDates)
                ->selectRaw('COUNT(*) as clicks')
                ->first();

            $totals['page_views']      += (int) ($liveTotals->page_views ?? 0);
            $totals['unique_sessions'] += (int) ($liveTotals->unique_sessions ?? 0);
            $totals['desktop']         += (int) ($liveTotals->desktop ?? 0);
            $totals['mobile']          += (int) ($liveTotals->mobile ?? 0);
            $totals['unknown']         += (int) ($liveTotals->unknown ?? 0);
            $totals['clicks']          += (int) ($liveClickTotals->clicks ?? 0);
        }

        // previous_totals — prior period of equal length for delta comparisons
        $periodDays  = $start->diffInDays($end) + 1;
        $priorEnd    = $start->copy()->subDay()->endOfDay();
        $priorStart  = $priorEnd->copy()->subDays($periodDays - 1)->startOfDay();
        $priorStartDate = $priorStart->toDateString();
        $priorEndDate   = $priorEnd->toDateString();

        $priorTotalsRow = DailyStat::whereDate('date', '>=', $priorStartDate)
            ->whereDate('date', '<=', $priorEndDate)
            ->selectRaw('
                SUM(page_views)      as page_views,
                SUM(clicks)          as clicks,
                SUM(unique_sessions) as unique_sessions,
                SUM(desktop)         as desktop,
                SUM(mobile)          as mobile,
                SUM(unknown)         as unknown
            ')
            ->first();

        $previousTotals = [
            'page_views'      => (int) ($priorTotalsRow->page_views ?? 0),
            'unique_sessions' => (int) ($priorTotalsRow->unique_sessions ?? 0),
            'clicks'          => (int) ($priorTotalsRow->clicks ?? 0),
            'mobile'          => (int) ($priorTotalsRow->mobile ?? 0),
            'desktop'         => (int) ($priorTotalsRow->desktop ?? 0),
            'unknown'         => (int) ($priorTotalsRow->unknown ?? 0),
        ];

        $priorAllDates = collect(CarbonPeriod::create($priorStartDate, $priorEndDate))
            ->map(fn ($d) => $d->toDateString())->all();
        $priorAggregatedDates = DailyStat::whereDate('date', '>=', $priorStartDate)
            ->whereDate('date', '<=', $priorEndDate)
            ->pluck('date')
            ->map(fn ($d) => (string) $d)
            ->toArray();
        $priorMissingDates = array_values(array_diff($priorAllDates, $priorAggregatedDates));

        if (! empty($priorMissingDates)) {
            $priorLiveTotals = PageView::whereBetween('created_at', [$priorStart, $priorEnd])
                ->whereIn(DB::raw('DATE(created_at)'), $priorMissingDates)
                ->selectRaw("
                    COUNT(*) as page_views,
                    COUNT(DISTINCT session_id) as unique_sessions,
                    SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                    SUM(CASE WHEN device_type = 'mobile'  THEN 1 ELSE 0 END) as mobile,
                    SUM(CASE WHEN device_type NOT IN ('desktop','mobile') THEN 1 ELSE 0 END) as unknown
                ")
                ->first();

            $priorLiveClicks = Click::whereBetween('created_at', [$priorStart, $priorEnd])
                ->whereIn(DB::raw('DATE(created_at)'), $priorMissingDates)
                ->selectRaw('COUNT(*) as clicks')
                ->first();

            $previousTotals['page_views']      += (int) ($priorLiveTotals->page_views ?? 0);
            $previousTotals['unique_sessions'] += (int) ($priorLiveTotals->unique_sessions ?? 0);
            $previousTotals['desktop']         += (int) ($priorLiveTotals->desktop ?? 0);
            $previousTotals['mobile']          += (int) ($priorLiveTotals->mobile ?? 0);
            $previousTotals['unknown']         += (int) ($priorLiveTotals->unknown ?? 0);
            $previousTotals['clicks']          += (int) ($priorLiveClicks->clicks ?? 0);
        }

        // top_pages — raw page_views table
        $topPages = PageView::whereBetween('created_at', [$start, $end])
            ->selectRaw("
                current_path,
                COUNT(*) as count,
                SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                SUM(CASE WHEN device_type = 'mobile'  THEN 1 ELSE 0 END) as mobile
            ")
            ->groupBy('current_path')
            ->orderByDesc('count')
            ->limit(25)
            ->get();

        // top_referrers — raw page_views, domain only
        $topReferrers = PageView::whereBetween('created_at', [$start, $end])
            ->whereNotNull('referrer_path')
            ->where('referrer_path', '!=', '')
            ->selectRaw('referrer_path, COUNT(*) as count')
            ->groupBy('referrer_path')
            ->orderByDesc('count')
            ->limit(25)
            ->get()
            ->map(fn ($row) => [
                'referrer_path' => parse_url($row->referrer_path, PHP_URL_HOST) ?: $row->referrer_path,
                'count'         => (int) $row->count,
            ])
            ->values();

        // top_paths — raw clicks table
        $topPaths = Click::whereBetween('created_at', [$start, $end])
            ->selectRaw('path, COUNT(*) as count')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(25)
            ->get(['path', 'count']);

        // top_elements — raw clicks table
        $topElements = Click::whereBetween('created_at', [$start, $end])
            ->selectRaw('element, element_id, element_class, COUNT(*) as count')
            ->groupBy('element', 'element_id', 'element_class')
            ->orderByDesc('count')
            ->limit(25)
            ->get();

        // top_events — laraprints_events table
        $topEvents = config('laraprints.events.enabled', true)
            ? LpEvent::whereBetween('occurred_at', [$start, $end])
                ->selectRaw('name, COUNT(*) as count, COUNT(DISTINCT session_id) as unique_sessions')
                ->groupBy('name')
                ->orderByDesc('count')
                ->limit(25)
                ->get()
            : collect();

        return response()->json([
            'by_date'          => $byDate,
            'totals'           => $totals,
            'previous_totals'  => $previousTotals,
            'top_pages'        => $topPages,
            'top_referrers'    => $topReferrers,
            'top_paths'        => $topPaths,
            'top_elements'     => $topElements,
            'top_events'       => $topEvents,
        ]);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $request->validate([
            'type'  => 'nullable|in:page_views,sessions',
            'start' => 'nullable|date',
            'end'   => 'nullable|date',
        ]);

        $type  = $request->get('type', 'page_views');
        $start = Carbon::parse($request->get('start', now()->subDays(29)->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->get('end', now()->toDateString()))->endOfDay();

        $filename = "laraprints-{$type}-{$start->toDateString()}-to-{$end->toDateString()}.csv";

        return response()->streamDownload(function () use ($type, $start, $end) {
            $handle = fopen('php://output', 'w');

            if ($type === 'sessions') {
                fputcsv($handle, [
                    'session_id', 'country', 'browser', 'os', 'device',
                    'entry_page', 'referrer', 'page_views', 'clicks',
                    'duration_seconds', 'first_seen_at', 'last_seen_at',
                ]);

                Session::whereBetween('last_seen_at', [$start, $end])
                    ->orderByDesc('last_seen_at')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $s) {
                            fputcsv($handle, [
                                $s->session_id,
                                $s->country,
                                $s->browser,
                                $s->os,
                                $s->device,
                                $s->entry_page,
                                $s->referrer,
                                $s->page_views,
                                $s->clicks,
                                $s->duration,
                                $s->first_seen_at?->toIso8601String(),
                                $s->last_seen_at?->toIso8601String(),
                            ]);
                        }
                    });
            } else {
                fputcsv($handle, [
                    'id', 'session_id', 'visit_id', 'path', 'device_type',
                    'country_code', 'referrer_path', 'viewed_at',
                ]);

                PageView::whereBetween('created_at', [$start, $end])
                    ->orderByDesc('viewed_at')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $pv) {
                            fputcsv($handle, [
                                $pv->id,
                                $pv->session_id,
                                $pv->visit_id,
                                $pv->current_path,
                                $pv->device_type,
                                $pv->country_code,
                                $pv->referrer_path,
                                $pv->viewed_at?->toIso8601String() ?? $pv->created_at?->toIso8601String(),
                            ]);
                        }
                    });
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function visitors(Request $request): JsonResponse
    {
        $request->validate([
            'start'   => 'nullable|date',
            'end'     => 'nullable|date',
            'sort'    => 'nullable|string',
            'dir'     => 'nullable|in:asc,desc',
            'page'    => 'nullable|integer|min:1',
            'country' => 'nullable|string|size:2',
            'device'  => 'nullable|in:desktop,mobile,unknown',
            'browser' => 'nullable|string|max:64',
        ]);

        $start     = Carbon::parse($request->get('start', now()->subDays(29)->toDateString()))->startOfDay();
        $end       = Carbon::parse($request->get('end', now()->toDateString()))->endOfDay();
        $startDate = $start->toDateString();
        $endDate   = $end->toDateString();

        $allowedSorts = ['last_seen_at', 'first_seen_at', 'page_views', 'clicks', 'duration'];
        $sort = in_array($request->get('sort'), $allowedSorts, true)
            ? $request->get('sort')
            : 'last_seen_at';
        $dir     = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $page    = (int) $request->get('page', 1);
        $country = $request->get('country') ?: null;
        $device  = $request->get('device')  ?: null;
        $browser = $request->get('browser') ?: null;

        // Find dates not yet aggregated — same logic as stats()
        $allDates = collect(CarbonPeriod::create($startDate, $endDate))
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $aggregatedDates = DailyStat::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->pluck('date')
            ->map(fn ($d) => $d instanceof \Carbon\CarbonInterface ? $d->toDateString() : (string) $d)
            ->toArray();

        $missingDates = array_values(array_diff($allDates, $aggregatedDates));

        // Build live session objects for any un-aggregated dates
        $liveSessions = collect();

        if (! empty($missingDates)) {
            $pvRows = PageView::whereBetween('created_at', [$start, $end])
                ->whereIn(DB::raw('DATE(created_at)'), $missingDates)
                ->get(['session_id', 'created_at', 'device_type', 'referrer_path', 'current_path']);

            $clickRows = Click::whereBetween('created_at', [$start, $end])
                ->whereIn(DB::raw('DATE(created_at)'), $missingDates)
                ->get(['session_id', 'created_at']);

            $pvBySession     = $pvRows->groupBy('session_id');
            $clicksBySession = $clickRows->groupBy('session_id');

            $allSessionIds = $pvBySession->keys()->merge($clicksBySession->keys())->unique()->filter();

            // Skip any that were already aggregated into the sessions table
            $existingIds = Session::whereIn('session_id', $allSessionIds->all())->pluck('session_id');
            $newIds      = $allSessionIds->diff($existingIds);

            foreach ($newIds as $sessionId) {
                $pvs    = $pvBySession->get($sessionId, collect());
                $clicks = $clicksBySession->get($sessionId, collect());

                $allTimestamps = $pvs->pluck('created_at')
                    ->merge($clicks->pluck('created_at'))
                    ->filter()->sort()->values();

                $firstSeen = $allTimestamps->first();
                $lastSeen  = $allTimestamps->last();
                $duration  = ($firstSeen && $lastSeen)
                    ? (int) $firstSeen->diffInSeconds($lastSeen)
                    : null;

                $firstPv   = $pvs->first();
                $entryPage = $firstPv ? ltrim($firstPv->current_path, '/') : null;

                $referrer    = null;
                $firstRef    = $pvs->first(fn ($pv) => ! empty($pv->referrer_path));
                if ($firstRef) {
                    $host     = parse_url($firstRef->referrer_path, PHP_URL_HOST);
                    $referrer = $host ?: $firstRef->referrer_path;
                }

                $liveSessions->push([
                    'id'            => $sessionId,
                    'country'       => null,
                    'browser'       => null,
                    'os'            => null,
                    'device'        => $firstPv?->device_type ?? 'desktop',
                    'entry_page'    => $entryPage,
                    'referrer'      => $referrer,
                    'page_views'    => $pvs->count(),
                    'clicks'        => $clicks->count(),
                    'duration'      => $duration,
                    'first_seen_at' => $firstSeen?->toIso8601String(),
                    'last_seen_at'  => $lastSeen?->toIso8601String(),
                ]);
            }

            // Filter live sessions
            if ($country) $liveSessions = $liveSessions->filter(fn ($s) => $s['country'] === $country)->values();
            if ($device)  $liveSessions = $liveSessions->filter(fn ($s) => $s['device']  === $device)->values();
            if ($browser) $liveSessions = $liveSessions->filter(fn ($s) => str_contains(strtolower((string) ($s['browser'] ?? '')), strtolower($browser)))->values();

            // Sort live sessions to match the requested column/direction
            $liveSessions = $dir === 'desc'
                ? $liveSessions->sortByDesc(fn ($s) => $s[$sort] ?? '')->values()
                : $liveSessions->sortBy(fn ($s) => $s[$sort] ?? '')->values();
        }

        // Merge live sessions (prepended) with paginated DB sessions
        $perPage   = 25;
        $liveCount = $liveSessions->count();
        $dbTotal   = Session::whereBetween('last_seen_at', [$start, $end])
            ->when($country, fn ($q) => $q->where('country', $country))
            ->when($device,  fn ($q) => $q->where('device',  $device))
            ->when($browser, fn ($q) => $q->where('browser', 'like', "%{$browser}%"))
            ->count();
        $total     = $liveCount + $dbTotal;
        $offset    = ($page - 1) * $perPage;

        // Slice of live sessions for this page
        $liveSlice = $liveSessions->slice($offset, $perPage)->values();
        $remaining = $perPage - $liveSlice->count();

        // Fill the rest of the page from the DB
        $dbSlice = collect();
        if ($remaining > 0) {
            $dbOffset = max(0, $offset - $liveCount);
            $dbSlice  = Session::whereBetween('last_seen_at', [$start, $end])
                ->when($country, fn ($q) => $q->where('country', $country))
                ->when($device,  fn ($q) => $q->where('device',  $device))
                ->when($browser, fn ($q) => $q->where('browser', 'like', "%{$browser}%"))
                ->orderBy($sort, $dir)
                ->skip($dbOffset)
                ->take($remaining)
                ->get()
                ->map(fn ($s) => [
                    'id'            => $s->session_id,
                    'country'       => $s->country,
                    'browser'       => $s->browser,
                    'os'            => $s->os,
                    'device'        => $s->device,
                    'entry_page'    => $s->entry_page,
                    'referrer'      => $s->referrer,
                    'page_views'    => $s->page_views,
                    'clicks'        => $s->clicks,
                    'duration'      => $s->duration,
                    'first_seen_at' => $s->first_seen_at?->toIso8601String(),
                    'last_seen_at'  => $s->last_seen_at?->toIso8601String(),
                ]);
        }

        $items = $liveSlice->merge($dbSlice)->values();

        // Enrich with ip_address and user_agent from the first page view per session
        $sessionIds = $items->pluck('id')->filter()->values()->all();
        if (! empty($sessionIds)) {
            $pvEnrichment = PageView::whereIn('session_id', $sessionIds)
                ->select('session_id', 'ip_address', 'user_agent')
                ->orderBy('created_at')
                ->get()
                ->groupBy('session_id')
                ->map(fn ($pvs) => $pvs->first());

            $items = $items->map(function ($item) use ($pvEnrichment) {
                $pv = $pvEnrichment->get($item['id']);
                return array_merge($item, [
                    'ip_address' => $pv?->ip_address,
                    'user_agent' => $pv?->user_agent,
                ]);
            })->values();
        }

        $lastPage = max(1, (int) ceil($total / $perPage));

        return response()->json([
            'data' => $items,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $lastPage,
                'from'         => $total > 0 ? $offset + 1 : null,
                'to'           => $total > 0 ? $offset + $items->count() : null,
            ],
        ]);
    }
}
