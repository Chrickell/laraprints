<?php

namespace Chrickell\Laraprints\Tests\Unit;

use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Models\PageView;
use Chrickell\Laraprints\Tests\TestCase;

class ModelPrunableTest extends TestCase
{
    public function test_page_view_prunable_returns_empty_query_when_days_is_null(): void
    {
        config()->set('laraprints.pruning.page_views_after_days', null);

        PageView::create([
            'session_id'   => 'sess-1',
            'visit_id'     => 'visit-1',
            'device_type'  => 'desktop',
            'method'       => 'GET',
            'current_path' => '/home',
            'viewed_at'    => now()->subDays(365),
        ]);

        $prunable = (new PageView())->prunable();
        $this->assertEquals(0, $prunable->count(), 'No records should be prunable when days is null');
    }

    public function test_page_view_prunable_returns_old_records(): void
    {
        config()->set('laraprints.pruning.page_views_after_days', 30);

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
            'viewed_at'    => now()->subDays(10),
        ]);

        $prunable = (new PageView())->prunable();
        $this->assertEquals(1, $prunable->count());
        $this->assertEquals('/old', $prunable->first()->current_path);
    }

    public function test_click_prunable_returns_empty_query_when_days_is_null(): void
    {
        config()->set('laraprints.pruning.clicks_after_days', null);

        Click::create([
            'session_id' => 'sess-1',
            'visit_id'   => 'visit-1',
            'element'    => 'button',
            'path'       => '/home',
            'clicked_at' => now()->subDays(365),
        ]);

        $prunable = (new Click())->prunable();
        $this->assertEquals(0, $prunable->count(), 'No records should be prunable when days is null');
    }

    public function test_click_prunable_returns_old_records(): void
    {
        config()->set('laraprints.pruning.clicks_after_days', 30);

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
            'element'    => 'a',
            'path'       => '/new',
            'clicked_at' => now()->subDays(10),
        ]);

        $prunable = (new Click())->prunable();
        $this->assertEquals(1, $prunable->count());
        $this->assertEquals('/old', $prunable->first()->path);
    }
}
