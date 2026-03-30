<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Carbon\Carbon;
use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\DailyStat;
use Chrickell\Laraprints\Models\PageView;
use Chrickell\Laraprints\Models\Session;
use Chrickell\Laraprints\Tests\TestCase;

class StatsAndVisitorsTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────────

    private function pv(string $sessionId, string $visitId, string $path = '/home', string $device = 'desktop', ?Carbon $at = null): PageView
    {
        return PageView::create([
            'session_id'   => $sessionId,
            'visit_id'     => $visitId,
            'device_type'  => $device,
            'current_path' => $path,
            'method'       => 'GET',
            'created_at'   => $at ?? now(),
            'updated_at'   => $at ?? now(),
        ]);
    }

    private function click(string $sessionId, string $visitId, ?Carbon $at = null): Click
    {
        return Click::create([
            'session_id' => $sessionId,
            'visit_id'   => $visitId,
            'element'    => 'button',
            'path'       => '/home',
            'created_at' => $at ?? now(),
            'updated_at' => $at ?? now(),
        ]);
    }

    private function dailyStat(string $date, array $overrides = []): DailyStat
    {
        return DailyStat::create(array_merge([
            'date'            => $date,
            'page_views'      => 0,
            'clicks'          => 0,
            'unique_sessions' => 0,
            'desktop'         => 0,
            'mobile'          => 0,
            'unknown'         => 0,
        ], $overrides));
    }

    private function dbSession(string $sessionId, array $overrides = []): Session
    {
        return Session::create(array_merge([
            'session_id'    => $sessionId,
            'device'        => 'desktop',
            'page_views'    => 1,
            'clicks'        => 0,
            'first_seen_at' => now()->subDay()->startOfDay(),
            'last_seen_at'  => now()->subDay()->setTime(12, 0),
        ], $overrides));
    }

    // ── /stats — structure ────────────────────────────────────────────────────

    public function test_stats_returns_correct_structure(): void
    {
        $this->getJson('/laraprints/stats')
            ->assertOk()
            ->assertJsonStructure([
                'by_date',
                'totals' => ['page_views', 'unique_sessions', 'clicks', 'mobile', 'desktop', 'unknown'],
                'top_pages',
                'top_referrers',
                'top_paths',
                'top_elements',
            ]);
    }

    public function test_stats_validates_date_params(): void
    {
        $this->getJson('/laraprints/stats?start=not-a-date')->assertStatus(422);
    }

    // ── /stats — live fallback ─────────────────────────────────────────────────

    public function test_stats_fetches_live_data_when_no_daily_stat_for_today(): void
    {
        $this->pv('s1', 'v1', '/home', 'desktop');
        $this->pv('s2', 'v2', '/about', 'mobile');
        $this->click('s1', 'v1');

        $today = today()->toDateString();
        $this->getJson("/laraprints/stats?start={$today}&end={$today}")
            ->assertOk()
            ->assertJsonPath('totals.page_views', 2)
            ->assertJsonPath('totals.clicks', 1)
            ->assertJsonPath('totals.unique_sessions', 2)
            ->assertJsonPath('totals.desktop', 1)
            ->assertJsonPath('totals.mobile', 1);
    }

    public function test_stats_uses_aggregated_data_when_daily_stat_exists(): void
    {
        $yesterday = today()->subDay()->toDateString();

        $this->dailyStat($yesterday, [
            'page_views'      => 100,
            'clicks'          => 50,
            'unique_sessions' => 40,
            'desktop'         => 30,
            'mobile'          => 10,
        ]);

        // Raw rows for the same day — should NOT be double-counted
        $this->pv('ignored', 'ignored', '/x', 'desktop', Carbon::parse($yesterday)->setTime(12, 0));

        $this->getJson("/laraprints/stats?start={$yesterday}&end={$yesterday}")
            ->assertOk()
            ->assertJsonPath('totals.page_views', 100)
            ->assertJsonPath('totals.clicks', 50)
            ->assertJsonPath('totals.unique_sessions', 40);
    }

    public function test_stats_merges_aggregated_and_live_data_across_date_range(): void
    {
        $yesterday = today()->subDay()->toDateString();

        $this->dailyStat($yesterday, [
            'page_views'      => 10,
            'clicks'          => 3,
            'unique_sessions' => 5,
            'desktop'         => 4,
            'mobile'          => 1,
        ]);

        // Today: live only (no DailyStat)
        $this->pv('live', 'v1', '/today', 'mobile');

        $this->getJson("/laraprints/stats?start={$yesterday}&end=" . today()->toDateString())
            ->assertOk()
            ->assertJsonPath('totals.page_views', 11)
            ->assertJsonPath('totals.clicks', 3)
            ->assertJsonPath('totals.unique_sessions', 6);
    }

    public function test_stats_by_date_zero_fills_days_with_no_activity(): void
    {
        $start = today()->subDays(2)->toDateString();
        $end   = today()->toDateString();

        $response  = $this->getJson("/laraprints/stats?start={$start}&end={$end}")->assertOk();
        $byDate    = $response->json('by_date');

        $this->assertCount(3, $byDate);
        foreach ($byDate as $day) {
            $this->assertSame(0, $day['page_views']);
            $this->assertSame(0, $day['clicks']);
        }
    }

    public function test_stats_today_appears_in_by_date_from_live_data(): void
    {
        $this->pv('s1', 'v1');

        $today    = today()->toDateString();
        $response = $this->getJson("/laraprints/stats?start={$today}&end={$today}")->assertOk();
        $byDate   = $response->json('by_date');

        $this->assertCount(1, $byDate);
        $this->assertSame($today, $byDate[0]['date']);
        $this->assertSame(1, $byDate[0]['page_views']);
    }

    // ── /visitors — structure ─────────────────────────────────────────────────

    public function test_visitors_returns_correct_meta_structure(): void
    {
        $this->getJson('/laraprints/visitors')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page', 'last_page', 'from', 'to'],
            ]);
    }

    public function test_visitors_returns_empty_result_when_no_data(): void
    {
        $today = today()->toDateString();
        $this->getJson("/laraprints/visitors?start={$today}&end={$today}")
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('data', []);
    }

    // ── /visitors — live fallback ─────────────────────────────────────────────

    public function test_visitors_synthesises_live_sessions_when_no_daily_stat_exists(): void
    {
        $this->pv('live-a', 'v1', '/home', 'desktop');
        $this->pv('live-a', 'v2', '/about', 'desktop');
        $this->click('live-a', 'v1');

        $today    = today()->toDateString();
        $response = $this->getJson("/laraprints/visitors?start={$today}&end={$today}")->assertOk();

        $response->assertJsonPath('meta.total', 1);

        $session = $response->json('data.0');
        $this->assertSame('live-a', $session['id']);
        $this->assertSame(2, $session['page_views']);
        $this->assertSame(1, $session['clicks']);
        $this->assertSame('desktop', $session['device']);
        $this->assertSame('home', $session['entry_page']);
    }

    public function test_visitors_resolves_referrer_for_live_sessions(): void
    {
        PageView::create([
            'session_id'    => 's1',
            'visit_id'      => 'v1',
            'device_type'   => 'desktop',
            'current_path'  => '/page',
            'referrer_path' => 'https://google.com/search?q=test',
            'method'        => 'GET',
        ]);

        $today    = today()->toDateString();
        $response = $this->getJson("/laraprints/visitors?start={$today}&end={$today}")->assertOk();

        $this->assertSame('google.com', $response->json('data.0.referrer'));
    }

    public function test_visitors_does_not_duplicate_sessions_already_in_sessions_table(): void
    {
        // Session already in the sessions table
        $this->dbSession('agg-sess', ['last_seen_at' => now()]);

        // Same session_id also has raw page_views for today (missing date)
        $this->pv('agg-sess', 'v1');

        $today    = today()->toDateString();
        $response = $this->getJson("/laraprints/visitors?start={$today}&end={$today}")->assertOk();

        $response->assertJsonPath('meta.total', 1);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('agg-sess', $response->json('data.0.id'));
    }

    // ── /visitors — merging ───────────────────────────────────────────────────

    public function test_visitors_merges_live_and_db_sessions(): void
    {
        $yesterday = today()->subDay();

        $this->dailyStat($yesterday->toDateString(), ['page_views' => 5, 'unique_sessions' => 1, 'desktop' => 1]);
        $this->dbSession('db-sess', ['last_seen_at' => $yesterday->setTime(12, 0)]);

        // Today: live only
        $this->pv('live-sess', 'v1', '/today', 'mobile');

        $response = $this->getJson(
            '/laraprints/visitors?start=' . $yesterday->toDateString() . '&end=' . today()->toDateString()
        )->assertOk();

        $response->assertJsonPath('meta.total', 2);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains('live-sess', $ids);
        $this->assertContains('db-sess', $ids);
    }

    public function test_visitors_live_sessions_are_sorted_among_themselves(): void
    {
        // Two live sessions today — different page view counts
        $this->pv('low-s', 'v1');
        foreach (range(1, 5) as $i) {
            $this->pv('high-s', "v{$i}");
        }

        $today    = today()->toDateString();
        $response = $this->getJson("/laraprints/visitors?start={$today}&end={$today}&sort=page_views&dir=desc")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame('high-s', $ids[0]);
        $this->assertSame('low-s', $ids[1]);
    }

    // ── /visitors — pagination ────────────────────────────────────────────────

    public function test_visitors_paginates_correctly_with_live_sessions_on_first_page(): void
    {
        $yesterday = today()->subDay();

        $this->dailyStat($yesterday->toDateString(), ['unique_sessions' => 30, 'desktop' => 30]);

        for ($i = 1; $i <= 30; $i++) {
            $this->dbSession("db-{$i}", ['last_seen_at' => $yesterday->setTime(12, 0)]);
        }

        // 3 live sessions today
        foreach (['live-1', 'live-2', 'live-3'] as $sid) {
            $this->pv($sid, $sid);
        }

        $start = $yesterday->toDateString();
        $end   = today()->toDateString();

        $page1 = $this->getJson("/laraprints/visitors?start={$start}&end={$end}&page=1")->assertOk()->json();
        $page2 = $this->getJson("/laraprints/visitors?start={$start}&end={$end}&page=2")->assertOk()->json();

        $this->assertSame(33, $page1['meta']['total']);
        $this->assertSame(2, $page1['meta']['last_page']);
        $this->assertCount(25, $page1['data']);
        $this->assertCount(8, $page2['data']);

        // All live sessions should appear on page 1
        $page1Ids = collect($page1['data'])->pluck('id')->all();
        $this->assertContains('live-1', $page1Ids);
        $this->assertContains('live-2', $page1Ids);
        $this->assertContains('live-3', $page1Ids);
    }

    public function test_visitors_pagination_meta_is_accurate(): void
    {
        $yesterday = today()->subDay();
        $this->dailyStat($yesterday->toDateString(), ['unique_sessions' => 5, 'desktop' => 5]);
        for ($i = 1; $i <= 5; $i++) {
            $this->dbSession("db-{$i}", ['last_seen_at' => $yesterday->setTime(12, 0)]);
        }

        $start    = $yesterday->toDateString();
        $end      = today()->toDateString();
        $response = $this->getJson("/laraprints/visitors?start={$start}&end={$end}&page=1")->assertOk();

        $meta = $response->json('meta');
        $this->assertSame(5, $meta['total']);
        $this->assertSame(1, $meta['current_page']);
        $this->assertSame(1, $meta['last_page']);
        $this->assertSame(1, $meta['from']);
        $this->assertSame(5, $meta['to']);
    }

    public function test_visitors_second_page_when_live_sessions_fill_first_page(): void
    {
        $yesterday = today()->subDay();

        // 26 live sessions today (spills onto page 2)
        for ($i = 1; $i <= 26; $i++) {
            $this->pv("live-{$i}", "v{$i}");
        }

        // 1 DB session from yesterday
        $this->dailyStat($yesterday->toDateString(), ['unique_sessions' => 1, 'desktop' => 1]);
        $this->dbSession('db-1', ['last_seen_at' => $yesterday->setTime(12, 0)]);

        $start = $yesterday->toDateString();
        $end   = today()->toDateString();

        $page1 = $this->getJson("/laraprints/visitors?start={$start}&end={$end}&page=1")->assertOk()->json();
        $page2 = $this->getJson("/laraprints/visitors?start={$start}&end={$end}&page=2")->assertOk()->json();

        $this->assertSame(27, $page1['meta']['total']);
        $this->assertCount(25, $page1['data']);
        $this->assertCount(2, $page2['data']); // 1 remaining live + 1 DB

        $page2Ids = collect($page2['data'])->pluck('id')->all();
        $this->assertContains('db-1', $page2Ids);
    }

    // ── /visitors — sorting ───────────────────────────────────────────────────

    public function test_visitors_sorting_is_applied_to_db_sessions(): void
    {
        $yesterday = today()->subDay();

        $this->dailyStat($yesterday->toDateString(), ['unique_sessions' => 2, 'desktop' => 2]);
        $this->dbSession('low-views', ['page_views' => 1, 'last_seen_at' => $yesterday->setTime(12, 0)]);
        $this->dbSession('high-views', ['page_views' => 99, 'last_seen_at' => $yesterday->setTime(12, 0)]);

        $start = $yesterday->toDateString();
        $end   = $yesterday->toDateString(); // yesterday only — no live sessions

        $response = $this->getJson("/laraprints/visitors?start={$start}&end={$end}&sort=page_views&dir=desc")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame('high-views', $ids[0]);
        $this->assertSame('low-views', $ids[1]);
    }

    public function test_visitors_rejects_invalid_sort_direction(): void
    {
        $this->getJson('/laraprints/visitors?dir=sideways')->assertStatus(422);
    }
}
