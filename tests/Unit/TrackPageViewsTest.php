<?php

namespace Chrickell\Laraprints\Tests\Unit;

use Chrickell\Laraprints\Http\Middleware\TrackPageViews;
use Chrickell\Laraprints\Jobs\StorePageView;
use Chrickell\Laraprints\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;

class TrackPageViewsTest extends TestCase
{
    private TrackPageViews $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TrackPageViews();
    }

    public function test_page_view_is_dispatched_for_get_request(): void
    {
        Queue::fake();

        $request = Request::create('/home', 'GET');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class);
    }

    public function test_page_view_is_not_dispatched_when_disabled(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.enabled', false);

        $request = Request::create('/home', 'GET');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertNotPushed(StorePageView::class);
    }

    public function test_page_view_is_not_dispatched_for_post_request_by_default(): void
    {
        Queue::fake();

        $request = Request::create('/submit', 'POST');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertNotPushed(StorePageView::class);
    }

    public function test_page_view_is_dispatched_for_configured_methods(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.methods', ['GET', 'POST']);

        $request = Request::create('/submit', 'POST');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class);
    }

    public function test_bots_are_excluded_by_default(): void
    {
        Queue::fake();

        $request = Request::create('/home', 'GET');
        $request->headers->set('User-Agent', 'Googlebot/2.1');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertNotPushed(StorePageView::class);
    }

    public function test_bots_are_tracked_when_ignore_bots_is_false(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.ignore_bots', false);

        $request = Request::create('/home', 'GET');
        $request->headers->set('User-Agent', 'Googlebot/2.1');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class);
    }

    public function test_excluded_paths_are_not_tracked(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.excluded_paths', ['api/*', 'admin/*']);

        $request = Request::create('/api/users', 'GET');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertNotPushed(StorePageView::class);
    }

    public function test_non_excluded_paths_are_tracked(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.excluded_paths', ['api/*']);

        $request = Request::create('/dashboard', 'GET');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class);
    }

    public function test_mobile_device_is_detected(): void
    {
        Queue::fake();

        $request = Request::create('/home', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class, function (StorePageView $job) {
            return $job->deviceType === 'mobile';
        });
    }

    public function test_desktop_device_is_detected(): void
    {
        Queue::fake();

        $request = Request::create('/home', 'GET');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class, function (StorePageView $job) {
            return $job->deviceType === 'desktop';
        });
    }

    public function test_referrer_is_captured(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.store_referrer', true);

        $request = Request::create('/home', 'GET');
        $request->headers->set('Referer', 'https://example.com/about');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class, function (StorePageView $job) {
            return $job->referrerPath === 'about';
        });
    }

    public function test_referrer_is_omitted_when_disabled(): void
    {
        Queue::fake();
        config()->set('laraprints.requests.store_referrer', false);

        $request = Request::create('/home', 'GET');
        $request->headers->set('Referer', 'https://example.com/about');
        $request->setLaravelSession(session()->driver());

        $this->middleware->handle($request, fn ($r) => response('ok'));

        Queue::assertPushed(StorePageView::class, function (StorePageView $job) {
            return $job->referrerPath === null;
        });
    }

    public function test_next_is_always_called(): void
    {
        config()->set('laraprints.requests.enabled', false);

        $request = Request::create('/home', 'GET');
        $request->setLaravelSession(session()->driver());

        $called = false;
        $this->middleware->handle($request, function ($r) use (&$called) {
            $called = true;
            return response('ok');
        });

        $this->assertTrue($called);
    }
}
