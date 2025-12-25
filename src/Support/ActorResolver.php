<?php

namespace Dibakar\ActivityScope\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

class ActorResolver
{
    public function __construct(private GuardResolver $guardResolver) {}

    public function resolve(): Model|Authenticatable|null
    {
        if (!config('activityscope.actor.auto_resolve', true)) {
            return null;
        }

        if ($user = $this->guardResolver->user()) {
            return $user;
        }

        if (app()->runningInConsole() || config('activityscope.actor.require_system_actor', false)) {
            return $this->resolveSystemActor();
        }

        return null;
    }

    protected function resolveSystemActor(): Model|Authenticatable|null
    {
        $systemActorId = config('activityscope.actor.system_actor_id');
        $userModel = config('auth.providers.users.model');
        
        if (!$systemActorId || !$userModel || !class_exists($userModel)) {
            return null;
        }

        try {
            return $userModel::find($systemActorId);
        } catch (Throwable $th) {
            return null;
        }
    }
}