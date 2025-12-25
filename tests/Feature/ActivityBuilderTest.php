<?php

namespace Dibakar\ActivityScope\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Dibakar\ActivityScope\Contracts\ActivityActor;
use Dibakar\ActivityScope\Contracts\ActivitySubject;
use Dibakar\ActivityScope\Events\ActivityLogged;
use Dibakar\ActivityScope\Exceptions\ActivityException;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Request;

class ActivityBuilderTest extends TestCase
{

    protected BuilderTestUser $user;
    protected BuilderTestPost $post;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('activityscope.auto_log', true);

        $app['db']->connection()->getSchemaBuilder()->create('builder_test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('builder_test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = BuilderTestUser::create(['name' => 'John Doe']);
        $this->post = BuilderTestPost::create(['title' => 'Sample Post']);

        $this->actingAs($this->user);
    }

    /** @test */
    public function test_it_can_log_a_simple_activity_and_auto_resolve_actor()
    {
        // No ->by() call, should auto-resolve to $this->user
        activity()->on($this->post)->did('created')->log();

        $this->assertDatabaseHas('activities', [
            'subject_type' => $this->post->getMorphClass(),
            'subject_id' => $this->post->id,
            'action' => 'created',
            'actor_type' => $this->user->getMorphClass(),
            'actor_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_it_supports_fluent_chaining()
    {
        $activity = activity()
            ->on($this->post)
            ->by($this->user)
            ->did('updated')
            ->status('warning')
            ->category('content')
            ->log();

        $this->assertEquals('updated', $activity->action);
        $this->assertEquals('warning', $activity->status);
        $this->assertEquals('content', $activity->category);
    }

    /** @test */
    public function test_it_can_log_by_system_job_or_guest()
    {
        // System
        $systemLog = activity()->bySystem()->did('cleanup')->log();
        $this->assertTrue($systemLog->isSystem());
        $this->assertNull($systemLog->actor_id);

        // Job
        $jobLog = activity()->byJob('ProcessVideo')->did('started')->log();
        $this->assertEquals('ProcessVideo', $jobLog->jobName());

        // Guest
        $guestLog = activity()->byGuest()->did('browsed')->log();
        $this->assertTrue($guestLog->isGuest());
    }

    /** @test */
    public function test_it_can_handle_multiple_subjects()
    {
        $posts = [
            BuilderTestPost::create(['title' => 'Post 1']),
            BuilderTestPost::create(['title' => 'Post 2']),
        ];

        activity()->onMany($posts)->did('deleted')->log();

        $activity = Activity::latest('id')->first();
        $this->assertArrayHasKey('subject_ids', $activity->meta);
        $this->assertCount(2, $activity->meta['subject_ids']);
        $this->assertEquals(BuilderTestPost::class, $activity->meta['subject_type']);
    }

    /** @test */
    public function test_it_can_manually_set_subject_label_and_id()
    {
        activity()
            ->subject('ExternalSystem')
            ->subjectId('ext_123')
            ->did('synced')
            ->log();

        $activity = Activity::latest('id')->first();
        $this->assertEquals('ExternalSystem', $activity->meta['subject_label']);
        $this->assertEquals('ext_123', $activity->meta['subject_id']);
    }

    /** @test */
    public function test_it_has_action_helpers()
    {
        activity()->created($this->post)->log();
        $this->assertDatabaseHas('activities', ['action' => 'created']);

        activity()->updated($this->post)->log();
        $this->assertDatabaseHas('activities', ['action' => 'updated']);

        activity()->deleted($this->post)->log();
        $this->assertDatabaseHas('activities', ['action' => 'deleted']);
    }

    /** @test */
    public function test_it_captures_metadata_helpers()
    {
        activity()
            ->oldNew('blue', 'red')
            ->changes(['color' => 'red'])
            ->context(['source' => 'api'])
            ->did('changed')
            ->log();

        $activity = Activity::latest('id')->first();
        $this->assertEquals('blue', $activity->meta['old']);
        $this->assertEquals('red', $activity->meta['new']);
        $this->assertEquals('red', $activity->meta['changes']['color']);
        $this->assertEquals('api', $activity->meta['source']);
    }

    /** @test */
    public function test_it_handles_security_and_ip_anonymization()
    {
        config(['activityscope.privacy.anonymize_ip' => true]);

        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.123',
            'HTTP_USER_AGENT' => 'Mozilla/5.0'
        ]);
        $this->app->instance('request', $request);

        activity()->security('login_failed')->log();

        $activity = Activity::latest('id')->first();
        $this->assertEquals('security:login_failed', $activity->action);
        $this->assertEquals('192.168.1.0', $activity->ip_address);
    }

    /** @test */
    public function test_it_supports_manual_ip_and_agent_overrides()
    {
        activity()
            ->ip('8.8.8.8')
            ->userAgent('CustomBot/1.0')
            ->did('probed')
            ->log();

        $this->assertDatabaseHas('activities', [
            'ip_address' => '8.8.8.8',
            'user_agent' => 'CustomBot/1.0',
        ]);
    }

    /** @test */
    public function test_it_scrubs_sensitive_metadata()
    {
        config(['activityscope.sensitive_keys' => ['password', 'card_number']]);

        activity()
            ->with([
                'password' => 'secret123',
                'user' => [
                    'email' => 'test@example.com',
                    'card_number' => '1234-5678'
                ]
            ])
            ->did('updated')
            ->log();

        $activity = Activity::latest('id')->first();
        $this->assertEquals('[REDACTED]', $activity->meta['password']);
        $this->assertEquals('[REDACTED]', $activity->meta['user']['card_number']);
        $this->assertEquals('test@example.com', $activity->meta['user']['email']);
    }

    /** @test */
    public function test_it_respects_the_enabled_config()
    {
        config(['activityscope.enabled' => false]);

        $result = activity()->did('test')->log();

        $this->assertNull($result);
        $this->assertDatabaseEmpty('activities');
    }

    /** @test */
    public function test_it_can_be_run_silently()
    {
        activity()->silent()->did('test')->log();

        $this->assertDatabaseEmpty('activities');
    }

    /** @test */
    public function test_it_dispatches_event_on_log()
    {
        Event::fake();

        activity()->did('test')->log();

        Event::assertDispatched(ActivityLogged::class);
    }

    /** @test */
    public function test_it_throws_exception_if_action_is_missing()
    {
        $this->expectException(ActivityException::class);
        activity()->log();
    }

    /** @test */
    public function test_it_can_set_custom_timestamp()
    {
        $past = now()->subDays(5);
        activity()->at($past)->did('historical')->log();

        $activity = Activity::latest('id')->first();
        $this->assertEquals($past->toDateTimeString(), $activity->created_at->toDateTimeString());
    }

    /** @test */
    public function test_it_supports_conditional_execution_with_when()
    {
        activity()
            ->when(true, fn($b) => $b->did('true_action'))
            ->when(false, fn($b) => $b->did('false_action'))
            ->log();

        $this->assertDatabaseHas('activities', ['action' => 'true_action']);
        $this->assertDatabaseMissing('activities', ['action' => 'false_action']);
    }

    /** @test */
    public function test_it_captures_correlation_ids()
    {
        activity()
            ->correlationId('corr-1')
            ->requestId('req-1')
            ->externalRef('ext-1')
            ->did('correlated')
            ->log();

        $activity = Activity::latest('id')->first();
        $this->assertEquals('corr-1', $activity->meta['correlation_id']);
        $this->assertEquals('req-1', $activity->meta['request_id']);
        $this->assertEquals('ext-1', $activity->meta['external_ref']);
    }
}

use Illuminate\Foundation\Auth\User as Authenticatable;

class BuilderTestUser extends Authenticatable implements ActivityActor
{
    protected $table = 'builder_test_users';
    protected $fillable = ['name'];
    public function activityLabel(): string
    {
        return 'User';
    }
    public function displayName(): string
    {
        return $this->name;
    }
}

class BuilderTestPost extends Model implements ActivitySubject
{
    protected $table = 'builder_test_posts';
    protected $fillable = ['title'];
    public function activityLabel(): string
    {
        return 'Post';
    }
    public function displayName(): string
    {
        return $this->title;
    }
}
