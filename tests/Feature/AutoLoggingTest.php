<?php

namespace Dibakar\ActivityScope\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Dibakar\ActivityScope\Contracts\ActivitySubject;
use Dibakar\ActivityScope\Tests\TestCase;
use Dibakar\ActivityScope\Traits\HasActivities;
use Dibakar\ActivityScope\Traits\LogsActivity;
use Dibakar\ActivityScope\Models\Activity;

class AutoLoggingTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('activityscope.auto_log', true);

        $app['db']->connection()->getSchemaBuilder()->create('test_posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });
    }

    /** @test */
    public function test_it_automatically_logs_created_event()
    {
        TestPost::create(['title' => 'Hello World']);

        $this->assertDatabaseHas('activities', [
            'subject_type' => TestPost::class,
            'action' => 'created',
        ]);
    }

    /** @test */
    public function test_it_automatically_logs_updated_event_with_changes()
    {
        $post = TestPost::create(['title' => 'Old Title']);

        $post->update(['title' => 'New Title']);

        $this->assertDatabaseHas('activities', [
            'subject_type' => TestPost::class,
            'action' => 'updated',
        ]);

        $activity = Activity::where('action', 'updated')->first();
        $this->assertArrayHasKey('title', $activity->meta['changes']);
        $this->assertEquals('New Title', $activity->meta['changes']['title']);
    }

    /** @test */
    public function test_it_automatically_logs_deleted_event()
    {
        $post = TestPost::create(['title' => 'To be deleted']);
        $post->delete();

        $this->assertDatabaseHas('activities', [
            'subject_type' => TestPost::class,
            'action' => 'deleted',
        ]);
    }
    /** @test */
    public function test_it_works_independently_without_has_activities_trait()
    {
        StandalonePost::create(['title' => 'Standalone']);

        $this->assertDatabaseHas('activities', [
            'subject_type' => StandalonePost::class,
            'action' => 'created',
        ]);
    }
}

class TestPost extends Model implements ActivitySubject
{
    use HasActivities, LogsActivity;

    protected $table = 'test_posts';
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

class StandalonePost extends Model implements ActivitySubject
{
    use LogsActivity; // No HasActivities

    protected $table = 'test_posts';
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
