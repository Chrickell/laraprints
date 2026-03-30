<?php

namespace Chrickell\Laraprints\Http\Controllers;

use Carbon\Carbon;
use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LaraprintsController extends Controller
{
    public function pageViews(Request $request): JsonResponse
    {
        $request->validate([
            'start'  => 'nullable|date',
            'end'    => 'nullable|date',
            'domain' => 'nullable|string',
        ]);

        $start = Carbon::parse($request->get('start', now()->subDays(29)->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->get('end', now()->toDateString()))->endOfDay();

        $base = PageView::byDateRange($start, $end);

        if ($domain = $request->get('domain')) {
            $base = $base->where('domain', $domain);
        }

        $total = (clone $base)->count();

        $uniqueSessions = (clone $base)->distinct('session_id')->count('session_id');

        $byDevice = (clone $base)
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->pluck('count', 'device_type');

        $byDate = (clone $base)
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get(['date', 'count']);

        $topPages = (clone $base)
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

        $topReferrers = (clone $base)
            ->whereNotNull('referrer_path')
            ->where('referrer_path', '!=', '')
            ->selectRaw('referrer_path, COUNT(*) as count')
            ->groupBy('referrer_path')
            ->orderByDesc('count')
            ->limit(15)
            ->get(['referrer_path', 'count']);

        return response()->json([
            'total'          => $total,
            'unique_sessions' => $uniqueSessions,
            'desktop'        => (int) ($byDevice->get('desktop') ?? 0),
            'mobile'         => (int) ($byDevice->get('mobile') ?? 0),
            'unknown'        => (int) ($byDevice->get('unknown') ?? 0),
            'by_date'        => $byDate,
            'top_pages'      => $topPages,
            'top_referrers'  => $topReferrers,
        ]);
    }

    public function clicks(Request $request): JsonResponse
    {
        $request->validate([
            'start'  => 'nullable|date',
            'end'    => 'nullable|date',
            'domain' => 'nullable|string',
        ]);

        $start = Carbon::parse($request->get('start', now()->subDays(29)->toDateString()))->startOfDay();
        $end   = Carbon::parse($request->get('end', now()->toDateString()))->endOfDay();

        $base = Click::whereBetween('clicked_at', [$start, $end]);

        if ($domain = $request->get('domain')) {
            $base = $base->where('domain', $domain);
        }

        $total = (clone $base)->count();

        $byDate = (clone $base)
            ->selectRaw('DATE(clicked_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get(['date', 'count']);

        $topPaths = (clone $base)
            ->selectRaw('path, COUNT(*) as count')
            ->groupBy('path')
            ->orderByDesc('count')
            ->limit(25)
            ->get(['path', 'count']);

        $topElements = (clone $base)
            ->selectRaw('element, element_class, element_id, path, COUNT(*) as count')
            ->groupBy('element', 'element_class', 'element_id', 'path')
            ->orderByDesc('count')
            ->limit(25)
            ->get();

        return response()->json([
            'total'        => $total,
            'by_date'      => $byDate,
            'top_paths'    => $topPaths,
            'top_elements' => $topElements,
        ]);
    }
}
