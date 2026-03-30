<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Chrickell\Laraprints\Tests\TestCase;

class EventsRouteDisabledTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config()->set('laraprints.events.enabled', false);
    }

    public function test_events_endpoint_route_is_not_registered_when_events_are_disabled(): void
    {
        $this->postJson('/api/events', [
            'session_id' => 'abc123',
            'name'       => 'test_event',
        ])->assertStatus(404);
    }
}
