<?php

namespace Chrickell\Laraprints\Tests\Feature;

use Chrickell\Laraprints\Models\Click;
use Chrickell\Laraprints\Tests\TestCase;

class ClickTrackingTest extends TestCase
{
    public function test_click_is_stored_via_endpoint(): void
    {
        $this->withSession(['tracking_session_id' => 'test-session']);

        $response = $this->postJson('/api/clicks', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'element'    => 'button',
            'class'      => 'btn btn-primary',
            'id'         => 'submit-btn',
            'style'      => 'color: red',
            'path'       => '/checkout',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('laraprints_clicks', [
            'session_id'    => 'abc123',
            'visit_id'      => 'visit456',
            'element'       => 'button',
            'element_class' => 'btn btn-primary',
            'element_id'    => 'submit-btn',
            'path'          => '/checkout',
        ]);
    }

    public function test_click_endpoint_requires_session_id(): void
    {
        $response = $this->postJson('/api/clicks', [
            'visit_id' => 'visit456',
            'element'  => 'button',
            'path'     => '/home',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['session_id']);
    }

    public function test_click_endpoint_requires_element(): void
    {
        $response = $this->postJson('/api/clicks', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'path'       => '/home',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['element']);
    }

    public function test_click_endpoint_requires_path(): void
    {
        $response = $this->postJson('/api/clicks', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'element'    => 'button',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['path']);
    }

    public function test_click_endpoint_optional_fields_are_nullable(): void
    {
        $response = $this->postJson('/api/clicks', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'element'    => 'button',
            'path'       => '/home',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('laraprints_clicks', [
            'element_class' => null,
            'element_id'    => null,
        ]);
    }

    public function test_click_endpoint_returns_401_when_only_authenticated_and_guest(): void
    {
        config()->set('laraprints.clicks.only_authenticated', true);

        $response = $this->postJson('/api/clicks', [
            'session_id' => 'abc123',
            'visit_id'   => 'visit456',
            'element'    => 'button',
            'path'       => '/home',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseCount('laraprints_clicks', 0);
    }

}
