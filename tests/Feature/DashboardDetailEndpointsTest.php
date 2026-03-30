<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\PageView;
use Chrickell\Laraprints\Models\Session;
use Chrickell\Laraprints\Tests\TestCase;

class DashboardDetailEndpointsTest extends TestCase
{
    public function test_session_endpoint_returns_live_fallback_when_session_record_does_not_exist(): void
    {
        PageView::create([
            'session_id'   => 'sess-live',
            'visit_id'     => 'visit-1',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/home',
            'viewed_at'    => now()->subMinute(),
        ]);
        PageView::create([
            'session_id'   => 'sess-live',
            'visit_id'     => 'visit-2',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/pricing',
            'viewed_at'    => now(),
        ]);
        Click::create([
            'session_id' => 'sess-live',
            'visit_id'   => 'visit-1',
            'element'    => 'button',
            'path'       => '/home',
            'clicked_at' => now(),
        ]);

        $this->getJson('/laraprints/sessions/sess-live')
            ->assertOk()
            ->assertJsonPath('session.session_id', 'sess-live')
            ->assertJsonPath('session.page_views', 2)
            ->assertJsonPath('session.clicks', 1)
            ->assertJsonCount(2, 'visits');
    }

    public function test_visit_endpoint_returns_visit_and_related_session_data(): void
    {
        Session::create([
            'session_id'    => 'sess-a',
            'device'        => 'desktop',
            'page_views'    => 2,
            'clicks'        => 1,
            'first_seen_at' => now()->subMinute(),
            'last_seen_at'  => now(),
        ]);

        PageView::create([
            'session_id'   => 'sess-a',
            'visit_id'     => 'visit-a',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/docs',
            'viewed_at'    => now()->subMinute(),
        ]);
        PageView::create([
            'session_id'   => 'sess-a',
            'visit_id'     => 'visit-a',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/docs/getting-started',
            'viewed_at'    => now(),
        ]);
        Click::create([
            'session_id' => 'sess-a',
            'visit_id'   => 'visit-a',
            'element'    => 'a',
            'path'       => '/docs',
            'clicked_at' => now(),
        ]);

        $this->getJson('/laraprints/visits/visit-a')
            ->assertOk()
            ->assertJsonPath('visit.visit_id', 'visit-a')
            ->assertJsonPath('visit.page_views', 2)
            ->assertJsonPath('visit.clicks', 1)
            ->assertJsonPath('session.session_id', 'sess-a')
            ->assertJsonCount(2, 'page_views')
            ->assertJsonCount(1, 'clicks');
    }

    public function test_page_endpoint_returns_stats_and_session_summary_for_requested_path(): void
    {
        PageView::create([
            'session_id'   => 'sess-path',
            'visit_id'     => 'visit-1',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => 'pricing',
            'viewed_at'    => now()->subMinute(),
        ]);
        PageView::create([
            'session_id'   => 'sess-path',
            'visit_id'     => 'visit-2',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => 'pricing',
            'viewed_at'    => now(),
        ]);
        PageView::create([
            'session_id'   => 'sess-other',
            'visit_id'     => 'visit-3',
            'device_type'  => 'mobile',
            'method'       => 'GET',
            'current_path' => 'other',
            'viewed_at'    => now(),
        ]);
        Click::create([
            'session_id' => 'sess-path',
            'visit_id'   => 'visit-1',
            'element'    => 'button',
            'path'       => 'pricing',
            'clicked_at' => now(),
        ]);

        $today = now()->toDateString();
        $this->getJson("/laraprints/page?path=/pricing&start={$today}&end={$today}")
            ->assertOk()
            ->assertJsonPath('path', '/pricing')
            ->assertJsonPath('stats.total_views', 2)
            ->assertJsonPath('stats.unique_sessions', 1)
            ->assertJsonPath('stats.total_clicks', 1)
            ->assertJsonCount(1, 'sessions');
    }

    public function test_export_endpoint_streams_page_views_csv(): void
    {
        PageView::create([
            'session_id'   => 'sess-export',
            'visit_id'     => 'visit-export',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/export-me',
            'viewed_at'    => now(),
        ]);

        $today = now()->toDateString();
        $response = $this->get("/laraprints/export?type=page_views&start={$today}&end={$today}");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('session_id,visit_id,path,device_type', $csv);
        $this->assertStringContainsString('sess-export,visit-export,/export-me,desktop', $csv);
    }
}
