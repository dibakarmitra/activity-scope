<?php

namespace Dibakar\ActivityScope\Tests\Unit;

use Dibakar\ActivityScope\Tests\TestCase;
use Dibakar\ActivityScope\Support\NameResolver;
use Illuminate\Database\Eloquent\Model;
use Dibakar\ActivityScope\Contracts\ActivityActor;

class TestUser extends Model
{
    protected $guarded = [];
}

class ContractUser extends Model implements ActivityActor
{
    public function displayName(): string
    {
        return 'Contract Name';
    }
    public function getMorphClass()
    {
        return 'test-user-contract';
    }
}

class NameResolverTest extends TestCase
{
    /** @test */
    public function test_it_uses_interface_display_name()
    {
        $resolver = new NameResolver();
        $user = new ContractUser();

        $this->assertEquals('Contract Name', $resolver->resolveActor($user));
    }

    /** @test */
    public function test_it_uses_config_mapping()
    {
        config([
            'activityscope.mappings' => [
                TestUser::class => 'username'
            ]
        ]);

        $resolver = new NameResolver();
        $user = new TestUser(['username' => 'mapped_user']);

        $this->assertEquals('mapped_user', $resolver->resolveActor($user));
    }

    /** @test */
    public function test_it_returns_system_as_fallback_for_actor()
    {
        $resolver = new NameResolver();
        $user = new TestUser();

        $this->assertEquals('System', $resolver->resolveActor($user));
    }
}
