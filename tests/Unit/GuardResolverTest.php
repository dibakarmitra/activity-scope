<?php

namespace Dibakar\ActivityScope\Tests\Unit;

use Dibakar\ActivityScope\Support\GuardResolver;
use Dibakar\ActivityScope\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class GuardResolverTest extends TestCase
{
    /** @test */
    public function test_it_uses_explicity_set_guard()
    {
        $resolver = new GuardResolver();
        $resolver->using('api');

        $this->assertEquals('api', $resolver->resolve());
    }

    /** @test */
    public function test_it_uses_default_auth_driver_if_available()
    {
        // Mock Auth::getDefaultDriver
        Auth::shouldReceive('getDefaultDriver')->andReturn('web');

        $resolver = new GuardResolver();
        $this->assertEquals('web', $resolver->resolve());
    }

    /** @test */
    public function test_it_falls_back_to_config_default()
    {
        Auth::shouldReceive('getDefaultDriver')->andReturn(null);
        Config::set('activityscope.actor.default_guard', 'custom_guard');

        $resolver = new GuardResolver();
        $this->assertEquals('custom_guard', $resolver->resolve());
    }
}
