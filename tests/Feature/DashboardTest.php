<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\PageView;
use Chrickell\Laraprints\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class DashboardTest extends TestCase
{
    // ── Gate Authorization ────────────────────────────────────────────────────

    public function test_dashboard_is_forbidden_when_gate_denies(): void
    {
        Gate::define('viewLaraprints', fn () => false);

        $this->getJson('/laraprints/page-views')->assertStatus(403);
        $this->getJson('/laraprints/clicks')->assertStatus(403);
    }

    public function test_dashboard_is_accessible_when_gate_passes(): void
    {
        Gate::define('viewLaraprints', fn ($user = null) => true);

        $this->getJson('/laraprints/page-views')->assertStatus(200);
        $this->getJson('/laraprints/clicks')->assertStatus(200);
    }

    // ── Page Views ────────────────────────────────────────────────────────────

    public function test_page_views_endpoint_returns_correct_structure(): void
    {
        $response = $this->getJson('/laraprints/page-views');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'unique_sessions',
                     'desktop',
                     'mobile',
                     'unknown',
                     'by_date',
                     'top_pages',
                     'top_referrers',
                 ]);
    }

    public function test_page_views_endpoint_returns_correct_counts(): void
    {
        PageView::create([
            'session_id'   => 'sess-1',
            'visit_id'     => 'visit-1',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/home',
            'viewed_at'    => now(),
        ]);
        PageView::create([
            'session_id'   => 'sess-2',
            'visit_id'     => 'visit-2',
            'device_type'  => 'mobile',
            'method'       => 'GET',
            'current_path' => '/about',
            'viewed_at'    => now(),
        ]);

        $response = $this->getJson('/laraprints/page-views');

        $response->assertStatus(200)
                 ->assertJson([
                     'total'          => 2,
                     'unique_sessions' => 2,
                     'desktop'        => 1,
                     'mobile'         => 1,
                 ]);
    }

    public function test_page_views_endpoint_respects_date_range(): void
    {
        PageView::create([
            'session_id'   => 'sess-old',
            'visit_id'     => 'visit-old',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/old',
            'viewed_at'    => now()->subDays(60),
        ]);
        PageView::create([
            'session_id'   => 'sess-new',
            'visit_id'     => 'visit-new',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/new',
            'viewed_at'    => now(),
        ]);

        $start = now()->subDays(7)->toDateString();
        $end   = now()->toDateString();

        $response = $this->getJson("/laraprints/page-views?start={$start}&end={$end}");

        $response->assertStatus(200)
                 ->assertJson(['total' => 1]);
    }

    public function test_page_views_endpoint_validates_date_format(): void
    {
        $response = $this->getJson('/laraprints/page-views?start=not-a-date');

        $response->assertStatus(422);
    }

    public function test_page_views_top_pages_lists_most_viewed(): void
    {
        foreach (range(1, 5) as $i) {
            PageView::create([
                'session_id'   => "sess-{$i}",
                'visit_id'     => "visit-{$i}",
                'device_type'  => 'desktop',
                'method'       => 'GET',
                'current_path' => '/popular',
                'viewed_at'    => now(),
            ]);
        }
        PageView::create([
            'session_id'   => 'sess-lone',
            'visit_id'     => 'visit-lone',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/lonely',
            'viewed_at'    => now(),
        ]);

        $response = $this->getJson('/laraprints/page-views');
        $topPages = $response->json('top_pages');

        $this->assertEquals('/popular', $topPages[0]['current_path']);
        $this->assertEquals(5, $topPages[0]['count']);
    }

    // ── Clicks ────────────────────────────────────────────────────────────────

    public function test_clicks_endpoint_returns_correct_structure(): void
    {
        $response = $this->getJson('/laraprints/clicks');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'total',
                     'by_date',
                     'top_paths',
                     'top_elements',
                 ]);
    }

    public function test_clicks_endpoint_returns_correct_counts(): void
    {
        Click::create([
            'session_id' => 'sess-1',
            'visit_id'   => 'visit-1',
            'element'    => 'button',
            'path'       => '/home',
            'clicked_at' => now(),
        ]);
        Click::create([
            'session_id' => 'sess-2',
            'visit_id'   => 'visit-2',
            'element'    => 'a',
            'path'       => '/about',
            'clicked_at' => now(),
        ]);

        $response = $this->getJson('/laraprints/clicks');

        $response->assertStatus(200)
                 ->assertJson(['total' => 2]);
    }

    public function test_clicks_endpoint_respects_date_range(): void
    {
        Click::create([
            'session_id' => 'sess-old',
            'visit_id'   => 'visit-old',
            'element'    => 'button',
            'path'       => '/old',
            'clicked_at' => now()->subDays(60),
        ]);
        Click::create([
            'session_id' => 'sess-new',
            'visit_id'   => 'visit-new',
            'element'    => 'button',
            'path'       => '/new',
            'clicked_at' => now(),
        ]);

        $start = now()->subDays(7)->toDateString();
        $end   = now()->toDateString();

        $response = $this->getJson("/laraprints/clicks?start={$start}&end={$end}");

        $response->assertStatus(200)
                 ->assertJson(['total' => 1]);
    }
}
