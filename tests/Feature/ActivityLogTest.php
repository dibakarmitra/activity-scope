<?php

namespace Dibakar\ActivityScope\Tests\Feature;

use Dibakar\ActivityScope\Tests\TestCase;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Exceptions\ActivityException;
use Illuminate\Support\Facades\Event;
use Dibakar\ActivityScope\Events\ActivityLogged;

class ActivityLogTest extends TestCase
{
    /** @test */
    public function test_it_can_create_activity()
    {
        $activity = activity()
            ->did('test-action')
            ->log();

        $this->assertNotNull($activity);
        $this->assertEquals('test-action', $activity->action);
        $this->assertDatabaseHas('activities', ['action' => 'test-action']);
    }

    /** @test */
    public function test_it_validates_action_is_required()
    {
        $this->expectException(ActivityException::class);

        activity()->log();
    }

    /** @test */
    public function test_it_logs_custom_status_and_category()
    {
        activity()
            ->did('payment')
            ->status('failed', 'insufficient funds')
            ->category('billing')
            ->log();

        $this->assertDatabaseHas('activities', [
            'action' => 'payment',
            'status' => 'failed',
            'category' => 'billing',
        ]);

        $activity = Activity::where('action', 'payment')->first();
        $this->assertEquals('insufficient funds', $activity->meta['reason']);
    }

    /** @test */
    public function test_it_respects_silent_mode()
    {
        $result = activity()
            ->did('something')
            ->silent()
            ->log();

        $this->assertNull($result);
        $this->assertDatabaseCount('activities', 0);
    }

    /** @test */
    public function test_it_automatically_sets_success_status()
    {
        activity()->did('login')->log();

        $this->assertDatabaseHas('activities', [
            'action' => 'login',
            'status' => 'success'
        ]);
    }

    /** @test */
    public function test_it_scrubs_sensitive_metadata()
    {
        $activity = activity()
            ->did('update_profile')
            ->with([
                'name' => 'John Doe',
                'password' => 'secret123',
                'nested' => [
                    'token' => 'abc-123'
                ]
            ])
            ->log();

        $meta = $activity->meta;
        $this->assertEquals('John Doe', $meta['name']);
        $this->assertEquals('[REDACTED]', $meta['password']);
        $this->assertEquals('[REDACTED]', $meta['nested']['token']);
    }

    /** @test */
    public function test_it_dispatches_activity_logged_event()
    {
        Event::fake();

        $activity = activity()
            ->did('event_test')
            ->log();

        Event::assertDispatched(ActivityLogged::class, function ($event) use ($activity) {
            return $event->activity->id === $activity->id;
        });
    }
}
