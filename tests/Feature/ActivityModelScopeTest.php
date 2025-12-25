<?php

namespace Dibakar\ActivityScope\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Dibakar\ActivityScope\Contracts\ActivityActor;
use Dibakar\ActivityScope\Contracts\ActivitySubject;
use Dibakar\ActivityScope\Models\Activity;
use Dibakar\ActivityScope\Tests\TestCase;

class ActivityModelScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Activity::truncate();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('activityscope.auto_log', true);

        $app['db']->connection()->getSchemaBuilder()->create('scope_test_users', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('scope_test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    /** @test */
    public function test_it_eager_loads_relations_by_default()
    {
        $user = new ScopeTestUser();
        $user->name = 'Test Actor';
        $user->save();

        $activity = activity()
            ->by($user)
            ->did('test_default_scope')
            ->log();

        // Refresh from DB using Model::find which triggers eager loading via $with property
        $retrieved = Activity::find($activity->id);

        $this->assertTrue($retrieved->relationLoaded('actor'));
        $this->assertTrue($retrieved->relationLoaded('subject'));
    }

    /** @test */
    public function test_it_filters_by_actor()
    {
        $user1 = new ScopeTestUser();
        $user1->name = 'U1';
        $user1->save();
        $user2 = new ScopeTestUser();
        $user2->name = 'U2';
        $user2->save();

        activity()->by($user1)->did('action1')->log();
        activity()->by($user2)->did('action2')->log();

        $this->assertEquals(1, Activity::by($user1)->count());
        $this->assertEquals(1, Activity::by($user2)->count());
        $this->assertEquals(1, Activity::by(ScopeTestUser::class, $user1->id)->count());
    }

    /** @test */
    public function test_it_filters_by_subject()
    {
        $post1 = new ScopeTestPost();
        $post1->title = 'P1';
        $post1->save();

        activity()->on($post1)->did('posted')->log();
        activity()->did('other')->log();

        $this->assertEquals(1, Activity::onSubject($post1)->count());
        $this->assertEquals(1, Activity::onSubject(ScopeTestPost::class, $post1->id)->count());
    }

    /** @test */
    public function test_it_filters_by_action()
    {
        activity()->did('login')->log();
        activity()->did('logout')->log();

        $this->assertEquals(1, Activity::did('login')->count());
        $this->assertEquals(2, Activity::did(['login', 'logout'])->count());
    }

    /** @test */
    public function test_it_filters_change_dates()
    {
        activity()->did('past')->at(now()->subDays(5))->log();
        activity()->did('recent')->at(now())->log();

        $this->assertEquals(
            1,
            Activity::between(now()->subDays(6), now()->subDays(4))->count()
        );
    }

    /** @test */
    public function test_it_filters_by_log_name()
    {
        activity()->name('system')->on(new ScopeTestPost())->did('booted')->log();
        activity()->name('auth')->on(new ScopeTestPost())->did('login')->log();

        Activity::inLog('system')->get()->each(function ($activity) {
            $this->assertEquals('system', $activity->log_name);
        });

        $this->assertEquals(1, Activity::inLog('system')->count());
        $this->assertEquals(2, Activity::inLog('system', 'auth')->count());
        $this->assertEquals(2, Activity::inLog(['system', 'auth'])->count());
    }

    /** @test */
    public function test_it_filters_by_property()
    {
        $post = new ScopeTestPost();
        $post->title = 'Test Title'; // Fix: Add required title
        $post->save();

        activity()
            ->on($post)
            ->did('updated')
            ->with(['ip' => '127.0.0.1', 'role' => 'admin'])
            ->log();

        activity()
            ->on($post)
            ->did('viewed')
            ->with(['ip' => '10.0.0.1'])
            ->log();

        $this->assertEquals(1, Activity::withProperty('ip', '127.0.0.1')->count());
        $this->assertEquals(1, Activity::withProperty('role', 'admin')->count());
        $this->assertEquals(0, Activity::withProperty('role', 'editor')->count());
    }
}

// Local Helpers
use Dibakar\ActivityScope\Traits\HasActivities;

class ScopeTestUser extends Model implements ActivityActor
{
    use HasActivities;
    protected $table = 'scope_test_users';
    protected $guarded = [];
    public function displayName(): string
    {
        return $this->name ?? 'User';
    }
    public function activityLabel(): string
    {
        return 'User';
    }
}

class ScopeTestPost extends Model implements ActivitySubject
{
    protected $table = 'scope_test_posts';
    protected $guarded = [];
    public function activityLabel(): string
    {
        return 'Post';
    }
    public function displayName(): string
    {
        return $this->title;
    }
}
