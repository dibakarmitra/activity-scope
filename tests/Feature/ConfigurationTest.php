<?php

namespace Dibakar\ActivityScope\Tests\Feature;

use Dibakar\ActivityScope\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function test_it_does_not_log_if_disabled()
    {
        Config::set('activityscope.enabled', false);

        $result = activity()
            ->did('disabled-action')
            ->log();

        $this->assertNull($result);
        $this->assertDatabaseCount('activities', 0);
    }

    /** @test */
    public function test_it_anonymizes_ip_if_configured()
    {
        Config::set('activityscope.privacy.anonymize_ip', true);

        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
        $this->app->instance('request', $request);

        $activity = activity()
            ->did('login')
            ->ip()
            ->log();

        $this->assertEquals('192.168.1.0', $activity->ip_address);
    }

    /** @test */
    public function test_it_does_not_anonymize_ip_by_default()
    {
        Config::set('activityscope.privacy.anonymize_ip', false);

        $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
        $this->app->instance('request', $request);

        $activity = activity()
            ->did('login')
            ->ip()
            ->log();

        $this->assertEquals('192.168.1.100', $activity->ip_address);
    }
}
