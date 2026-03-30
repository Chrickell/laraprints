<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Chrickell\Laraprints\Tests\TestCase;

class ClickRouteDisabledTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config()->set('laraprints.clicks.enabled', false);
    }

    public function test_click_endpoint_route_is_not_registered_when_clicks_are_disabled(): void
    {
        $this->postJson('/api/clicks', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'element'    => 'button',
            'path'       => '/home',
        ])->assertStatus(404);
    }
}
