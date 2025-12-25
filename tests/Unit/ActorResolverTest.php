<?php

namespace Dibakar\ActivityScope\Tests\Unit;

use Dibakar\ActivityScope\Support\ActorResolver;
use Dibakar\ActivityScope\Support\GuardResolver;
use Dibakar\ActivityScope\Tests\TestCase;
use Mockery;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class ActorResolverTest extends TestCase
{
    /** @test */
    public function test_it_returns_null_if_auto_resolve_is_disabled()
    {
        Config::set('activityscope.actor.auto_resolve', false);

        $guardResolver = Mockery::mock(GuardResolver::class);
        $resolver = new ActorResolver($guardResolver);

        $this->assertNull($resolver->resolve());
    }

    /** @test */
    public function test_it_resolves_authenticated_user()
    {
        $user = Mockery::mock(Authenticatable::class);

        $guardResolver = Mockery::mock(GuardResolver::class);
        $guardResolver->shouldReceive('user')->once()->andReturn($user);

        $resolver = new ActorResolver($guardResolver);

        $this->assertSame($user, $resolver->resolve());
    }

    /** @test */
    public function test_it_returns_null_if_no_user_and_no_system_actor_configured()
    {
        Config::set('activityscope.actor.require_system_actor', false);

        $guardResolver = Mockery::mock(GuardResolver::class);
        $guardResolver->shouldReceive('user')->once()->andReturn(null);

        $resolver = new ActorResolver($guardResolver);

        $this->assertNull($resolver->resolve());
    }
}
