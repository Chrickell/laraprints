<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Chrickell\Laraprints\Jobs\StoreEvent;
use Chrickell\Laraprints\Models\LpEvent;
use Chrickell\Laraprints\Tests\TestCase;
use Illuminate\Support\Facades\Queue;

class EventsTrackingTest extends TestCase
{
    public function test_event_is_queued_via_endpoint(): void
    {
        Queue::fake();

        $this->postJson('/api/events', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'name'       => 'checkout_completed',
            'properties' => ['plan' => 'pro', 'amount' => 49],
        ])->assertOk()
            ->assertJson(['success' => true]);

        Queue::assertPushed(StoreEvent::class, function (StoreEvent $job) {
            return $job->sessionId === 'abc123'
                && $job->visitId === 'visit456'
                && $job->name === 'checkout_completed'
                && $job->properties === ['plan' => 'pro', 'amount' => 49];
        });
    }

    public function test_event_endpoint_requires_session_id(): void
    {
        $this->postJson('/api/events', [
            'name' => 'signup',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['session_id']);
    }

    public function test_event_endpoint_requires_name(): void
    {
        $this->postJson('/api/events', [
            'session_id' => 'abc123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_event_endpoint_returns_401_when_only_authenticated_and_guest(): void
    {
        config()->set('laraprints.events.only_authenticated', true);

        $this->postJson('/api/events', [
            'session_id' => 'abc123',
            'name'       => 'signup',
        ])->assertStatus(401);

        $this->assertDatabaseCount('laraprints_events', 0);
    }

    public function test_store_event_job_persists_event_payload(): void
    {
        $job = new StoreEvent(
            name: 'feature_used',
            properties: ['feature' => 'export'],
            sessionId: 'abc123',
            visitId: 'visit456',
            userId: null,
            domain: 'example.test',
        );

        $job->handle();

        $stored = LpEvent::query()->first();

        $this->assertNotNull($stored);
        $this->assertSame('feature_used', $stored->name);
        $this->assertSame(['feature' => 'export'], $stored->properties);
        $this->assertSame('abc123', $stored->session_id);
        $this->assertSame('visit456', $stored->visit_id);
        $this->assertSame('example.test', $stored->domain);
    }
}
